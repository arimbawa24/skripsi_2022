<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Kreait\Firebase\Factory;
use Kreait\Firebase\ServiceAccount;
use \Kreait\Firebase\Database;
use Google\Cloud\Firestore\FirestoreClient;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Session;
use \Kreait\Firebase\Contract\Auth;
use \Kreait\Firebase\Contract\Firestore;
use Kreait\Firebase\Exception\Auth\FailedToVerifyToken;


class AuthController extends Controller
{
    protected $auth;
    protected $databsae;
    public function __construct(){
        $factory = (new Factory)
        ->withServiceAccount(__DIR__.'/firebase.json')
        ->withDatabaseUri('https://melcosh-a4d4b-default-rtdb.firebaseio.com/');
        $this->auth = $factory->createAuth();
        $this->database = $factory->createDatabase();
        // $this->firestore = $factory->createFirestore();
    }

    public function registrasi(Request $request){

        $validator = Validator::make($request->all(), [
            // 'firtsname' =>'required',
            'email' => 'required|string|email|max:100|',
            'password' => 'required|string|min:6',
            // 'phone' => 'required|numeric|max:13',
        ]);

        if ($validator->fails()) {
            return response()->json(["success" => false, "message" => $validator->errors()], 400);
        }
        else{
            try {
                $newUser = $this->auth->createUserWithEmailAndPassword($request->get('email'),$request->get('password'));
                $uid = $newUser->uid;
                $stuRef = app('firebase.firestore')->database()->collection('Members')->Document($uid);
                $stuRef->set([
                    'firtsname' => $request->get('firstname'),
                    'lastname' => $request->get('lastname'),
                    'birtday' => $request->get('birtday'),
                    'phone' => $request->get('phone'),
                    'email' => $request->get('email'),
                    'password' =>  bcrypt($request->get('password')),
                    'flagMember' => 'Y',
                ]);
                    return response()->json([
                        'status' => 'succes',
                        'message'=>'registrasi berhasil'   
                    ],200);
                }catch (\Exception $e ) {
                    return response()->json([
                        'status' => 'error',
                        'message'=>'email sudah digunakan'
                    
                    ],400);
                }   
        }
    
          
    }

    public function login(Request $request){


        // //get id member
        // $stuRef = app('firebase.firestore')->database()->collection('Members')->documents();   
        //     foreach($stuRef as $stu) { 
        //         if($stu->exists()){  
        //             $id = ''.$stu->id();
        //             $email = ''.$stu->data()['email'];
        //             $password = ''.$stu->data()['password'];
        //         }  
        //     }

        $validator = Validator::make($request->all(), [
                // 'firtsname' =>'required',
                'email' => 'required|string|email|max:100|',
                'password' => 'required|string|min:6',
                // 'phone' => 'required|numeric|max:13',
        ]);

        if ($validator->fails()) {
            return response()->json(["success" => false, "message" => $validator->errors()], 400);
        }
        else{
             
                $signInResult =$this->auth->signInWithEmailAndPassword($request->get('email'),$request->get('password'));
                // $uid = $this->signInResult->data()['localId'];
                // $customToken = $this->auth->createCustomToken($uid)->toString();
                Session::put('firebaseUserId', $signInResult->data()['localId']);
                Session::put('idToken', $signInResult->data()['idToken']);
                Session::save();
               
                return response()->json([
                    'success' => true,
                    'message' => 'LOGIN BERHASIL',
                    'userId' => Session::get('firebaseUserId'),
                    'token'=> $signInResult->asTokenResponse()
                    
                ], 200);

                
            }
        // }else{
        //         return response()->json([
        //             'status' => 'error',
        //             'message'=>'email/password salah'
            
        //         ],400);
            
        // }
    }
    

    public function updateProfile(Request $request){

    }
    public function getProfile(){
    $uid = Session::get('firebaseUserId');
        
     
        // // $firestore =$factory->createFirestore();
        // // $db = $firestore-> app('firebase.firestore')->database()->collection('Members');
        // $data = $stuRef;

        $stuRef = app('firebase.firestore')->database()->collection('Members')->documents($uid);  
        print_r('Total records: '.$stuRef->size());  
        foreach($stuRef as $stu) {  
          if($stu->exists()){  
           print_r('Members id= '.$stu->id());  
           print_r(' firtsname = '.$stu->data()['firtsname']); 
           print_r(' latsname = '.$stu->data()['lastname']); 
           print_r(' birtday = '.$stu->data()['birtday']); 
           print_r(' phone = '.$stu->data()['phone']); 
           print_r(' email = '.$stu->data()['email']); 
           print_r(' password = '.$stu->data()['password']); 
           print_r(' flag_member = '.$stu->data()['flagMember']);  
       
          }  
        }  

        //  return response()->json([
        //     'status' => 'succes',
        //     'message'=>'get data berhasil',
        //     'data'=> [''.$stu->data()['firtsname'],
        //               ''.$stu->data()['lastname']]
            
        // ],200);
        // $factory = (new Factory)
        // ->withServiceAccount(__DIR__.'/firebase.json')
        // ->withDatabaseUri('https://melcosh-a4d4b-default-rtdb.firebaseio.com/');

        // $firestore = $factory->createFirestore();
        // $stuRef = $firestore->database()->collection('Members');
        // $data =  $stuRef->documents($uid);
        

        // $firestore = app('firebase.firestore');
        // $reference = $firestore->database()->getReference('Members');
        // $snapshot = $reference->getSnapshot();
        // $value = $snapshot->getValue();

        // $serviceAccount = ServiceAccount::fromJsonFile(__DIR__ . 'firebase.json' . env('GOOGLE_SERVICES_ACCOUNT'));
        // $factory = (new Factory)->withServiceAccount($serviceAccount);
        // $firestore = $factory->createFirestore();
        // $database = $firestore->database();
        // $room = $database->collection('Members');
        // $conversation = $room->document();
        // $data = $sturef->size();

        
    
    
       
      
       
    }
    protected function createNewToken($customToken)
    {   
        $customToken = $this->auth->createCustomToken($id)->toString();
        return response()->json([
            'success' => true,
            'message' => 'LOGIN BERHASIL',
            'data' => array(
                'access_token' => $customToken,
                'token_type' => 'bearer',
                'user' => $id,
                'expires_in' => 3600,
        
            )
        ], 200);
    }
}
