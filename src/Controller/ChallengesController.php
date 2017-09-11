<?php
namespace App\Controller;

use App\Controller\AppController;
use Cake\Core\Configure;
use Cake\Routing\Router;
use Cake\Collection\Collection;

/**
 * Challenges Controller
 *
 * @property \App\Model\Table\ChallengesTable $Challenges
 *
 * @method \App\Model\Entity\Challenge[] paginate($object = null, array $settings = [])
 */
class ChallengesController extends AppController
{

    /**
     * Index method
     *
     * @return \Cake\Http\Response|void
     */
    public function index()
    {
        
        $this->paginate = [
            'contain' => ['ChallengeTypes']
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

        $this->set('challenge', $challenge);
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
            // pr($this->request->data); die;
            if($this->request->data['challenge_type_id'] == 3 || $this->request->data['challenge_type_id'] == 4){
                $this->request->data['details'] = null;
                $this->request->data['response'] = null;
            }
            $challenge = $this->Challenges->patchEntity($challenge, $this->request->getData());
            // pr($challenge); die;
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
        //If old image is available, unlink the path(and delete the image) and and  upload image from "upload" folder in webroot.
        $oldImageName = $challenge->image_name;
        $path = Configure::read('ImageUpload.uploadPathForChallengeImages');
        if ($this->request->is(['patch', 'post', 'put'])) {
            // pr($this->request->data); die;
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

    // public function triviaWinner(){
        
    //     $this->loadModel('UserChallengeResponses');
    //     $selectedWinneres = $this->UserChallengeResponses->find()
    //                                                      ->contain('FbPracticeInformation')
    //                                                      ->all()
    //                                                      ->groupBy('fb_practice_information_id')
    //                                                      ->toArray();

    //     $data = [];
    //     foreach ($selectedWinneres as $key => $value) {

    //         $getRandomWinner = array_rand($value);
    //         $result = $value[$getRandomWinner];
    //         $data[] = [
    //                     'user_id' => $result->user_id,
    //                     'fb_practice_information_id'=> $result->fb_practice_information_id,
    //                     'challenge_id'=>$result->challenge_id   
    //                 ];
    //     }
         
    //     $this->loadModel('ChallengeWinners');
    //     $triviaWinner = $this->ChallengeWinners->newEntities($data);
    //     $triviaWinner = $this->ChallengeWinners->patchEntities($triviaWinner, $data);

    //     if($this->ChallengeWinners->saveMany($triviaWinner)){
    //             pr('here');die;
    //     }   
    // }

     public function triviaWinner(){
        $this->loadModel('UserChallengeResponses');
        // active challenge id

        // find active challenge from challenge table and then find users from user challenge response table corresponding to active challenge id
 
        $userResponses = $this->UserChallengeResponses->findByChallengeId($challengeId)
                                             ->where(['status' => 1])
                                             ->groupBy('fb_practice_information_id')
                                             ->toArray();

        $this->loadModel('ChallengeWinners'); 
        $challengeWinners = $this->ChallengeWinners->find()
                                                   ->select(['fb_practice_information_id','identifier_value','identifier_type','created'])
                                                   ->groupBy('fb_practice_information_id')
                                                   ->toArray();

            // pr($challengeWinners);die;
        // $triviaWinner = [];                                            
        foreach ($userResponses as $key => $response) {
            $winnerArray = isset($challengeWinners[$key]) ? $challengeWinners[$key] : null;
            // pr($winnerArray); die;
            if(!$winnerArray){
                //$triviaWinner[$key]= select random winner
            }else{
                foreach ($response as $value) {
                    foreach ($winnerArray as $winner) {
                        // pr($winner);
                        if($value->identifier_type === $winner->identifier_type &&  $value->identifier_value === $winner->identifier_value){
                            $triviaWinner[] = $winner;
                        }else{
                            //isme vo winner declare kiya jayega jiski entry abhi tk challenge winner mein nahi ho rakhi kisi practice k corresponding.
                        } 
                    }
                }
            }
        }

        // pr($triviaWinner); die;
        // //pr(asort($triviaWinner)); die;
        // $win = $triviaWinner;
        // asort($win);
        // foreach($win as $x => $value) {
        //     echo "Key=" . $value->identifier_value . ", Value=" . $value->created;
        //     echo "<br>";
        // }
    }

    public function activeChallenge(){
        $this->viewBuilder()->layout('facebookuser');
        $activeChallenge = $this->Challenges->find()
                                            ->where(['is_active IS NOT' => 0])
                                            ->first();
        $image_url = Router::url('/', true);
        $image_url = $image_url.$activeChallenge->image_path.'/'.$activeChallenge->image_name;
        $url = Router::url(['controller'=>'Challenges','action'=>'activeChallenge'],true);
        $activeChallenge->url = $url;
        $activeChallenge->image_url = $image_url;                                
        $this->set(compact('activeChallenge'));
        $this->set('_serialize', ['activeChallenge']);
    }
}
