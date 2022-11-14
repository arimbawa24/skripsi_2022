<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Kreait\Firebase\Factory;
use Kreait\Firebase\ServiceAccount;
use \Kreait\Firebase\Database;
use Google\Cloud\Firestore\FirestoreClient;
use Kreait\Firebase\Contract\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Session;

class PromoController extends Controller
{
    protected $databsae;
    protected $db;
    protected $storage;
    
    public function __construct(){
        $factory = (new Factory)
        ->withServiceAccount(__DIR__.'/melcosh-a4d4b-firebase-adminsdk-p6qdf-104fcb504f.json')
        ->withDatabaseUri('https://melcosh-a4d4b-default-rtdb.firebaseio.com/');
        $this->database = $factory->createDatabase();
        $this->storage = $factory->createStorage();
    }


    public function InsertPromo(Request $request){
        $this->dbWB = $this->database->getReference('MELCOSH/WholeBean');
            $tmpValue = $this->dbWB->getValue();
            $value = array();
            if(isset($tmpValue)){
                foreach (array_keys($tmpValue) as $key) {
                    $tmpValue[$key]['id'] = $key;
                    array_push($value,$tmpValue[$key]);
                 }
                 if ($tmpValue[$key]['nama_wb'] == $request->get('nama_wb')) {
                    return response()->json([
                        'status' => 'false', 
                        'message'=> 'produk yang diinputkan sudah ada'   
                    ],400);
                    
                }
            }
            
        $dbPrommo = $this->database->getReference('MELCOSH/Promo')->PUSH([
            'nama_promo' => $request->get('nama_promo'),
            'kode_promo' => $request->get('kode_promo'),
            'deskripsi' => $request->get('deskripsi'),
            'potongan' => $request->get('potongan'),
            'minimal' => $request->get('minimal'),
            'exp_date' => date($request->get('exp_date')),
            'is_active' => 'Y',
            'point' => $request->get('point'),
        ]); 
        $expiresAt = new \DateTime('tomorrow');
                $image = $request->file('image');  //image file from frontend
                $firebase_storage_path = 'Promo/';
                $idPromo = $dbPrommo->getKey();
                $localfolder = public_path('firebase-temp-uploads') .'/';
                $extension = $image->getClientOriginalExtension();
                $file      = $idPromo. '.' . $extension;
                if ($image->move($localfolder, $file)) {
                  $uploadedfile = fopen($localfolder.$file, 'r');
                  $gambar= $this->storage->getBucket()->upload($uploadedfile, ['name' => $firebase_storage_path . $file]);
                  //will remove from local laravel folder
                  unlink($localfolder . $file);
                  Session::flash('message', 'Succesfully Uploaded');
                }
                if ($gambar->exists()) { 
                    $image = $gambar->signedUrl($expiresAt);
                  } else {
                    $gambar =  $this->storage->getBucket()->object('Promo/'.$idPromo.'.jpg');
                    $image = $gambar->signedUrl($expiresAt);
                  }
                  $this->dbPrommo = $this->database->getReference('MELCOSH/Promo/'.$idPromo)->UPDATE([
                    'gambar' => $image
                  ]);
    
    
                        return response()->json([
                            'status' => 'success', 
                            'message'=>'insert promo berhasil'   
                        ],200);
        }
        
    

    public function GetPromo(){
        $this->dbPrommo = $this->database->getReference('MELCOSH/Promo');
        $tmpValue = $this->dbPrommo->getValue();
        // return $tmpValue;
        $value = array();
        foreach (array_keys($tmpValue) as $key) {
  
            if(strtotime(date('d-m-Y')) > strtotime($tmpValue[$key]['exp_date']) ){

                $db = $this->database->getReference('MELCOSH/Transaksi/'.$id_user.'/'.$key)->UPDATE([
                            
                    'is_active' => 'N',
                    
                ]);
                
            }
        }
        $this->dbPrommo = $this->database->getReference('MELCOSH/Promo');
        $tmpValue = $this->dbPrommo->getValue();
        $value = array();
        foreach (array_keys($tmpValue) as $key) {
            $tmpValue[$key]['id'] = $key;
            unset($tmpValue[$key]['deskripsi']);
            unset($tmpValue[$key]['minimal']);
            unset($tmpValue[$key]['exp_date']);
            unset($tmpValue[$key]['is_active']);
            array_push($value,$tmpValue[$key]);
            
        }
        return response()->json([
            'status' => 'success', 
            'message'=>'get promo berhasil',
            'data' => $value  
        ],200);
       
    }

    public function GetDetailPromo(Request $request){
        $this->dbPrommo = $this->database->getReference('MELCOSH/Promo/-'.$request->get('id_promo'));
        $value = $this->dbPrommo->getSnapshot()->getvalue();
        return response()->json([
            'status' => 'success', 
            'message'=>'get detail promo berhasil',
            'data' => $value  
        ],200);
       
    }

    public function ValidasiPromo(Request $request){
        $this->dbPrommo = $this->database->getReference('MELCOSH/Promo');
        $tmpValue = $this->dbPrommo->getValue();
        $value = array();
        foreach (array_keys($tmpValue) as $key) {
            $tmpValue[$key]['id'] = $key;
          
            array_push($value,$tmpValue[$key]);
            if ($tmpValue[$key]['minmal'] == $request->get('total') ) {
                return response()->json([
                    'status' => 'success', 
                    'message'=>'kode bisa digunakan'   
                ],200);
            }else {
                return response()->json([
                    'status' => 'failed', 
                    'message'=>'tidak memenuhi syarat minimal'   
                ],500);
            }
        }

    }

}
