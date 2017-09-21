<?php
namespace App\Controller;

use App\Controller\AppController;
use Cake\Core\Configure;
use Cake\Routing\Router;
use Cake\Cache\Cache;
use Cake\Collection\Collection;
use Cake\I18n\Time;
use Cake\Network\Exception\NotFoundException;
use Cake\I18n\Date;
use Cake\Utility\Text;
/**
 * Challenges Controller
 *
 * @property \App\Model\Table\ChallengesTable $Challenges
 *
 * @method \App\Model\Entity\Challenge[] paginate($object = null, array $settings = [])
 */
class ChallengesController extends AppController
{
 public function initialize()
 {
    parent::initialize();
    $this->Auth->allow(['userFbPosts']);
}

    /**
     * Index method
     *
     * @return \Cake\Http\Response|void
     */
    public function index()
    {

        $this->paginate = [
        'contain' => ['ChallengeTypes'],
        'conditions' => ['user_id' => $this->Auth->user('id')]
        ];
        $challenges = $this->paginate($this->Challenges);

        $this->set(compact('challenges'));
        $this->set('_serialize', ['challenges']);
    }

    /**
     * View method
     *
     * @param string|null $id Challenge id.
     * @return \Cake\Http\Response|void
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view($id = null)
    {
        $challenge = $this->Challenges->get($id, [
            'contain' => ['ChallengeTypes', 'UserChallengeResponses']
            ]);
        $slug = strtolower(Text::slug($challenge->name));
        $url = Router::url(['controller'=>$slug.'/challenge','?'=>['chId'=>$id]],true);
        $this->set('challenge', $challenge);
        $this->set('url', $url);
        $this->set('_serialize', ['challenge']);
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null Redirects on successful add, renders view otherwise.
     */
    public function add()
    {
        $challenge = $this->Challenges->newEntity();
        if ($this->request->is('post')) {
            if($this->request->data['challenge_type_id'] == 3 || $this->request->data['challenge_type_id'] == 4){
                $this->request->data['details'] = null;
                $this->request->data['response'] = null;
            }
            $this->request->data['user_id'] = $this->Auth->user('id');
            // Converting end-time into UTC Timezone.
            $dateTime = $this->request->data['end_time'];
            $new_date = new Time($dateTime);
            $this->request->data['end_time'] = $new_date;
            $challenge = $this->Challenges->patchEntity($challenge, $this->request->getData());
            if ($this->Challenges->save($challenge)) {
                $this->Flash->success(__('The challenge has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The challenge could not be saved. Please, try again.'));
        }
        $challengeTypes = $this->Challenges->ChallengeTypes->find('list', ['limit' => 200]);
        $this->set(compact('challenge', 'challengeTypes'));
        $this->set('_serialize', ['challenge']);
    }

    /**
     * Edit method
     *
     * @param string|null $id Challenge id.
     * @return \Cake\Http\Response|null Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Network\Exception\NotFoundException When record not found.
     */
    public function edit($id = null)
    {
        $challenge = $this->Challenges->get($id, [
            'contain' => []
            ]);
        $date = new \DateTime($challenge->end_time);
        $date = $date->format('m/d/Y H:i A');

        // pr($date);die;
        //If old image is available, unlink the path(and delete the image) and and  upload image from "upload" folder in webroot.
        $oldImageName = $challenge->image_name;
        $path = Configure::read('ImageUpload.uploadPathForChallengeImages');
        if ($this->request->is(['patch', 'post', 'put'])) {
            $challenge = $this->Challenges->patchEntity($challenge, $this->request->getData());
            if ($this->Challenges->save($challenge)) {
                $this->Flash->success(__('The challenge has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The challenge could not be saved. Please, try again.'));
        }
        $challengeTypes = $this->Challenges->ChallengeTypes->find('list', ['limit' => 200]);
        $this->set(compact('challenge', 'challengeTypes','date'));
        $this->set('_serialize', ['challenge']);
    }

    /**
     * Delete method
     *
     * @param string|null $id Challenge id.
     * @return \Cake\Http\Response|null Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete($id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $challenge = $this->Challenges->get($id);
        if ($this->Challenges->delete($challenge)) {
            $this->Flash->success(__('The challenge has been deleted.'));
        } else {
            $this->Flash->error(__('The challenge could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }

    public function activeChallenge(){

        $this->viewBuilder()->layout('facebookuser');
        $chId  = (isset($this->request->query['chId']))?$this->request->query['chId']:null;
        $pageId  = (isset($this->request->query['p']))?$this->request->query['p']:null;
        if(!$pageId){
            $this->Flash->error(__('Invalid Request'));   
            return $this->redirect(['action' => 'error']);
        }

        if(!$chId){
            $activeChallenge = $this->Challenges->find()
            ->where(['is_active' => 1])
            ->first();    
        }else{

            $activeChallenge = $this->Challenges->findById($chId)
            ->first();

            // if(!$activeChallenge->is_active){
            //     return $this->redirect(['action' => 'winner', '?'=>['chId' => $chId, 'p' => $pageId]]);
            // }  
            
        }
        if(!$activeChallenge->is_active){
            return $this->redirect(['action' => 'winner', 'chId' => $chId, 'p' => $pageId]);
        }else{
            $image_url = Router::url('/', true);
            $image_url = $image_url.$activeChallenge->image_path.'/'.$activeChallenge->image_name;
            $slug = strtolower(Text::slug($activeChallenge->name));
            $url = Router::url(['controller'=>$slug.'/challenge','?'=>['chId' => $chId, 'p' => $pageId]],true);
            $activeChallenge->url = $url;
            $activeChallenge->image_url = $image_url;
            
            $this->set(compact('activeChallenge'));
            $this->set(compact('pageId'));
            $this->set('_serialize', ['activeChallenge','pageId']);    
        }
        
    }

    public function challengeWinners(){
        $this->loadModel('ChallengeWinners');
        $getExistingWinners = $this->ChallengeWinners->find()->contain(['FbPracticeInformation'])->all();
        
        $this->set(compact('getExistingWinners'));
        $this->set('_serialize', ['getExistingWinners']);
    }

    public function responseSubmitted(){
        $this->viewBuilder()->setLayout('trivia_winner');
    }

    // public function userFbPosts(){

    //     $this->loadComponent('FbGraphApi'); 
    //     $getFbPages = $this->FbGraphApi->getPages(true);
    //     // pr($getFbPages['response']);die;
    //     $data = [];

    //     foreach ($getFbPages['response'] as $key => $value) {
    //         $data[] = [ 
    //                     'page_token'=> $value->access_token,
    //                     'page_id' => $value->id,
    //                     'page_name' => $value->name,
    //                     'user_id' =>$this->Auth->User('id'),
    //                     'status' => $getFbPages['status']
    //                    ];
    //     }

    //     $this->loadModel('FbPracticeInformation');
    //     $fbPageInfo = $this->FbPracticeInformation->newEntities($data);
    //     $fbPageInfo = $this->FbPracticeInformation->patchEntities($fbPageInfo, $data);
    //     if ($this->FbPracticeInformation->saveMany($fbPageInfo)) {
    //             pr($this->FbPracticeInformation->saveMany($fbPageInfo));die;
    //         }else{
    //             pr('There is an error while saving data.');
    //         }

    //     $response = $this->FbGraphApi->postOnFb($data['fb_page_identifier'],$data['message'],$pageToken[$data['fb_page_identifier']]);

    // }

    public function isAuthorized($user)
    {

        return parent::isAuthorized($user);
    }

    public function winner(){
        $this->viewBuilder()->layout('facebookuser');
        $challengeId = (isset($this->request->query['chId']))?$this->request->query['chId']:null;
        $pageId = (isset($this->request->query['p']))?$this->request->query['p']:null;
        $this->loadModel('FbPracticeInformation');
        $this->loadModel('ChallengeWinners');
        if(!$challengeId || !$pageId){
            return $this->redirect(['action' => 'error']);
        }
        $fbPracticeInfoId = $this->FbPracticeInformation->findByPageId($pageId)->first()->get('id');

        $challengeWinner = $this->ChallengeWinners->findByChallengeId($challengeId)
        ->contain(['Challenges'])
        ->where([
            'fb_practice_information_id' => $fbPracticeInfoId
            ])
        ->first();
        if(!$challengeWinner){
            die(' no one played the game');
        }                                                                                 
        $activeChallenge = $challengeWinner->challenge;

        $this->_createImage($challengeWinner);                                
        $this->set(compact('activeChallenge', 'challengeWinner'));
        $this->set('_serialize', ['challenge', 'challengeWinner']);
    }

    private function _createImage($challengeDetails){
        if($challengeDetails){
            $activeChallenge = $challengeDetails->challenge;
           try {
              // Create a new SimpleImage object
                  $image = new \claviska\SimpleImage();
                  $image
                ->fromFile(WWW_ROOT.'/challenge_images/'.$activeChallenge->image_name)                     // load image.jpg
                ->autoOrient()                              // adjust orientation based on exif data
                ->text('Winner of '.$activeChallenge->name.' is ',['color'=> $activeChallenge->image_details['text-color'], 
                'anchor'=> $activeChallenge->image_details['text-position'],
                'size'=> $activeChallenge->image_details['text-font-size'],
                'yOffset'=>-80,
                'fontFile'=>WWW_ROOT.'fonts/Futura-Std-Book.ttf'])
                ->text(ucfirst($challengeDetails->identifier_value),['color'=> $activeChallenge->image_details['text-color'], 
                'anchor'=> $activeChallenge->image_details['text-position'],
                'yOffset'=>50,
                'shadow'=>['x'=>2,'y'=>10,'color'=>$activeChallenge->image_details['text-shadow-color']],
                'size'=> $activeChallenge->image_details['text-font-size']*2,
                'fontFile'=>WWW_ROOT.'fonts/Futura-Std-Book.ttf'])  
                ->toScreen();                               
            } catch(Exception $err) {
              echo $err->getMessage();
            }
        }
    }
}
