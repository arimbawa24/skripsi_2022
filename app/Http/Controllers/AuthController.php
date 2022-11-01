<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Kreait\Firebase;
use Kreait\Firebase\Factory;
use Kreait\Firebase\ServiceAccount;
use Kreait\Firebase\Database;
use Kreait\Firebase\Contract\Auth;
use Kreait\Firebase\Contract\Firestore;
use Kreait\Firebase\Exception\Auth\FailedToVerifyToken;
use Kreait\Firebase\Contract\Storage;
use Lcobucci\JWT\JwtFacade;
use Google\Cloud\Firestore\FirestoreClient;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Session;



class AuthController extends Controller
{
    protected $auth;
    protected $coba;
    protected $databsae;
    protected $firestore;
    protected $storage;


    public function __construct(){
        $factory = (new Factory)
        ->withServiceAccount(__DIR__.'/melcosh-a4d4b-firebase-adminsdk-p6qdf-104fcb504f.json')
        ->withDatabaseUri('https://melcosh-a4d4b-default-rtdb.firebaseio.com/');
        $this->firestore = $factory->createFirestore();
        $this->auth = $factory->createAuth();
        $this->storage = $factory->createStorage();
    }

    public function registrasi(Request $request){
        // dd($request);
        $validator = Validator::make($request->all(), [
            'firstname' =>'required|string|min:3|max:8',
            'lastname' => 'required|string|min:5|max:15',
            'email' => 'required|string|email',
            'password' => 'required|string|min:8|max:16',
            'phone' => 'required|numeric|digits_between:11,13',
            'image' => 'required|mimes:jpg,png|max:1024|min:10'
            
            
            
        ]);

        if ($validator->fails()) {
            return response()->json(["success" => false, "message" =>$validator->errors()], 400);
        }
        else{
            try {
                $newUser = $this->auth->createUserWithEmailAndPassword($request->get('email'),$request->get('password'));
                $uid = $newUser->uid;
                $stuRef = app('firebase.firestore')->database()->collection('Members')->Document($uid);
                $stuRef->set([
                    'firstname' => $request->get('firstname'),
                    'lastname' => $request->get('lastname'),
                    'birtday' => $request->get('birtday'),
                    'phone' => $request->get('phone'),
                    'email' => $request->get('email'),
                    'password' =>  bcrypt($request->get('password')),
                    'role' => 'member',
                    'Point' => '0',
                    'dt_added' => date('l, d-m-Y'),
                    'dt_update' => date('l, d-m-Y'),
                    
                ]);
                    $image = $request->file('image'); //image file from frontend
                    $firebase_storage_path = 'Members/';
                   
                    $localfolder = public_path('firebase-temp-uploads') .'/';
                    $extension = $image->getClientOriginalExtension();
                    $file      = $uid. '.' . $extension;
                    if ($image->move($localfolder, $file)) {
                    $uploadedfile = fopen($localfolder.$file, 'r');
                    $gambar= $this->storage->getBucket()->upload($uploadedfile, ['name' => $firebase_storage_path . $file]);
                    //will remove from local laravel folder
                    unlink($localfolder . $file);
                    }
        
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

        $validator = Validator::make($request->all(), [
                // 'firtsname' =>'required',
                'email' => 'required|string|email',
                'password' => 'required|string|min:6',
                // 'phone' => 'required|numeric|max:13',
        ]);
        

        if ($validator->fails()) {
            return response()->json(["success" => false, "message" => $validator->errors()], 400);
        }
        else{
            
            try {
                $signInResult = $this->auth->signInWithEmailAndPassword($request->get('email'),$request->get('password'));

                $database = $this->firestore->database();
                $collectionReference = $database->collection('Members');
                $documentReference = $collectionReference->document($signInResult->firebaseUserId());
                $snapshot = $documentReference->snapshot();

                Session::put('firebaseUserId', $signInResult->firebaseUserId());
                Session::put('idToken', $signInResult->idToken());
                Session::save();

                $role = $snapshot['role'];


            
                return response()->json([
                    'success' => true,
                    'message' => 'LOGIN BERHASIL',
                    'userId' => Session::get('firebaseUserId'),
                    'role' => $role,
                    'token'=> $signInResult->asTokenResponse()
                    
                ], 200);
            } catch (\Kreait\Firebase\Auth\SignIn\FailedToSignIn $e) {

                switch ($e->getMessage()){
                    case 'INVALID_PASSWORD':
                        return response()->json([
                            'success' => false,
                            'message' => 'Password anda salah',

                        ], 401);
                        break;
                        case 'EMAIL_NOT_FOUND':
                            return response()->json([
                                'success' => false,
                                'message' =>'Email anda salah',
                                
                            ], 401);
                            break;
                    default;
                    return response()->json([
                        'success' => false,
                        'message' => $e->getMessage(),
                        
                    ], 401);
                }
              
               
            }catch(Ecxeption $u){
                return response()->json([
                    'success' => false,
                    'message' => 'terjadi kesalahan',
                    
                ], 500);
                
            }

        }       
        
    }

    public function updateProfile(Request $request){      
        try{
            if(empty($request->bearerToken())){
                return "Beare token harus ada";
            }
            $verifiedIdToken = $this->auth->verifyIdToken($request->bearerToken());
            $uid = $verifiedIdToken->claims()->get('sub');

        $database = $this->firestore->database();
        $collectionReference = $database->collection('Members');
        $documentReference = $collectionReference->document($uid)->update([
            ['path' => 'firstname', 'value' =>  $request->get('firstname')],
            ['path' => 'lastname', 'value' =>  $request->get('lastname')],
            ['path' => 'birtday', 'value' =>  $request->get('birtday')],
            ['path' => 'phone', 'value' =>  $request->get('phone')],
            ['path' => 'dt_update', 'value' => date('l, d-m-Y')]
        ]); 
        $gambar= $this->storage->getBucket()->object('Members/'.$uid.'.png')->delete();

        $image = $request->file('image'); //image file from frontend  
        $firebase_storage_path = 'Members/';
        $localfolder = public_path('firebase-temp-uploads') .'/';
        $extension = $image->getClientOriginalExtension();

        $file = $uid. '.' . $extension;
        if ($image->move($localfolder, $file)) {
            $uploadedfile = fopen($localfolder.$file, 'r');
            $gambar= $this->storage->getBucket()->upload($uploadedfile, ['name' => $firebase_storage_path . $file]);
            //will remove from local laravel folder
            unlink($localfolder . $file);
            }
        return response()->json([
            'status' => 'succes',
            'message'=>'update data berhasil'   
        ],200);
        } catch (FailedToVerifyToken $e) {
            return 'The token is invalid: '.$e->getMessage();
        } 
    }

    public function getProfile(Request $request){
        try {
            if(empty($request->bearerToken())){
                return "Beare token harus ada";
            }
            $verifiedIdToken = $this->auth->verifyIdToken($request->bearerToken());
            $uid = $verifiedIdToken->claims()->get('sub');

            $database = $this->firestore->database();

            $collectionReference = $database->collection('Members');
            $documentReference = $collectionReference->document($uid);
            $snapshot = $documentReference->snapshot();

            // $extension = $uid->getClientOriginalExtension();
            // $file      = $uid. '.' . $extension;
            $expiresAt = new \DateTime('tomorrow');
            $imageReference =  $this->storage->getBucket()->object('Members/'.$uid.'.png');
            if ($imageReference->exists()) { 
                $image = $imageReference->signedUrl($expiresAt);
            } else {
                $imageReference =  $this->storage->getBucket()->object('Members/'.$uid.'.jpg');
                $image = $imageReference->signedUrl($expiresAt);
            }
        

            return response()->json([
                'status' => 'succes',
                'message'=>'get data berhasil',
                'data'=> [  'id_user' => $uid,
                            'firstname' => $snapshot['firstname'],
                            'lastname' => $snapshot['lastname'],
                            'birtday' =>$snapshot['birtday'],
                            'phone' => $snapshot['phone'],
                            'email' => $snapshot['email'],
                            'image' =>$image,
                            'dt_added '  =>$snapshot['dt_added']
                        ]
                
            ],200);
        } catch (FailedToVerifyToken $e) {
            return 'The token is invalid: '.$e->getMessage();
        }

    }

    public function Logout(Request $request)
    {
        $userId = $request->get('userId');  
        $token = $request->get('token');
      if (isset($userId)){
            $this->auth->revokeRefreshTokens($userId);
            return 'logout berhasil';
        } else {
           return 'User belum login';
        }
    }

    public function changePassword (Request $request){

        $validator = Validator::make($request->all(), [
            'password1' => 'required|string|min:8',

        ]);

        $password1=$request->get('password1');
        $password2=$request->get('password2');
        

        try {
            if(empty($request->bearerToken())){
                return "Beare token harus ada";
            }
            $verifiedIdToken = $this->auth->verifyIdToken($request->bearerToken());
            $uid = $verifiedIdToken->claims()->get('sub');
            
        if ($validator->fails()) {
            return response()->json(["success" => false, "message" => $validator->errors()], 400);
        }
        else{

            if ($password1 == $password2) {
                $updatedUser = $this->auth->changeUserPassword($uid, $password1);
                $database = $this->firestore->database();
                $collectionReference = $database->collection('Members');
                $documentReference = $collectionReference->document($uid)->update([
                    ['path' => 'password', 'value' =>  $password1],
                
                ]); 
    
                 return response()->json([
                      'status' => 'succes', 
                      'message'=> 'update password berhasil',
                    ],200);
                
            }else {
                return response()->json([
                    'status' => 'failed', 
                    'message'=> 'password tidak sama',
                  ],400);
            }
            
            }
             } catch (FailedToVerifyToken $e) {
        return 'The token is invalid: '.$e->getMessage();
     }
    }
 
}
        
        
