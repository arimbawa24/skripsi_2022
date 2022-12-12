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
use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Messaging\CloudMessage;
use App\Mail\sendNotification;
use Illuminate\Support\Facades\Mail;

 

class NotificationController extends Controller
{
    public function __construct(){
        $factory = (new Factory)
        ->withServiceAccount(__DIR__.'/melcosh-a4d4b-firebase-adminsdk-p6qdf-104fcb504f.json')
        ->withDatabaseUri('https://melcosh-a4d4b-default-rtdb.firebaseio.com/');
        $this->firestore = $factory->createFirestore();
        $this->auth = $factory->createAuth();
        $this->storage = $factory->createStorage();
        $this->messaging = $factory->createMessaging();
    }

    public function sendNotification(Request $request)
{
    $students = app('firebase.firestore')->database()->collection('Members')->documents();  
    $dataemail = array();
    foreach($students as $stu ) {  
      if($stu->exists() && $stu['role'] == 'member' ){  
        array_push($dataemail,$stu['email']);
       
      }  
      
    }  
    

    $mailObj = new \stdClass();
            $mailObj->subject = 'PROMO TERBARU';
            $mailObj->text = 'halo member setia Kedai Kopi Melcosh <br> ada promo baru di Kedai Kopi Melcosh. Yaitu '.$request->get('nama_promo').
            '<br>'.$request->get('deskirpsi').'<br> Hanya dengan menggunakan promo ini  kamu bisa mendapatkan potonga sebesar '.$request->get('potongan').
            '% dan point sebesar'.$request->get('point'). ' point <br> Jadi Tunggu apa lagi ayo datang ke Kedai Kopi Melcosh Terdekat';
               
     Mail::to($dataemail)->send(new sendNotification($mailObj));

     return response()->json([
                    'status' => 'success', 
                    'message'=> 'email berhasil dikirim',
                ],200);
}

public function birthday(Type $var = null)
{
  $students = app('firebase.firestore')->database()->collection('Members')->documents();  
  $databirhtday = array();
  foreach($students as $stu ) {  
    if($stu->exists() && $stu['role'] == 'member' && strtotime($stu['birtday']) == strtotime(date('d-m')) ){  
      array_push($databirhtday,$stu['email']);
     
    }  

  
  }  
  return $databirhtday;
}



}

