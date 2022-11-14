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

class LocationController extends Controller
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


    public function InsertLocation(Request $request){

        $this->dbLocation = $this->database->getReference('MELCOSH/Location');
        $tmpValue = $this->dbLocation->getValue();
        $value = array();
        if(isset($tmpValue)){
            foreach (array_keys($tmpValue) as $key) {
                $tmpValue[$key]['id'] = $key;
                array_push($value,$tmpValue[$key]);
             }
             if ($tmpValue[$key]['nama_lokasi'] == $request->get('nama_lokasi')) {
                return response()->json([
                    'status' => 'false', 
                    'message'=> 'lokasi yang diinputkan sudah ada'   
                ],400);
                
            }
        }

        $dbLocation = $this->database->getReference('MELCOSH/Location')->PUSH([
            'nama_lokasi' => $request->get('nama_lokasi'),
            'longitude' => $request->get('longitude'),
            'latitude' => $request->get('latitude'),
            'detail_alamat' => $request->get('detail_alamat'),
          
        ]); 
        $expiresAt = new \DateTime('tomorrow');
                $image = $request->file('image');  //image file from frontend
                $firebase_storage_path = 'Lokasi/';
                $idlokasi = $dbLocation->getKey();
                $localfolder = public_path('firebase-temp-uploads') .'/';
                $extension = $image->getClientOriginalExtension();
                $file      = $idlokasi. '.' . $extension;
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
                    $gambar =  $this->storage->getBucket()->object('Location/'.$idlokasi.'.jpg');
                    $image = $gambar->signedUrl($expiresAt);
                  }
                  $this->dbLocation = $this->database->getReference('MELCOSH/Location/'.$idlokasi)->UPDATE([
                    'gambar' => $image
                  ]);
    
    
                        return response()->json([
                            'status' => 'success', 
                            'message'=>'insert Location berhasil'   
                        ],200);
        }
        
    

    public function GetLocation(){
        $this->dbLocation = $this->database->getReference('MELCOSH/Location');
        $tmpValue = $this->dbLocation->getValue();
        $value = array();
        foreach (array_keys($tmpValue) as $key) {
            $tmpValue[$key]['id'] = $key;
            unset($tmpValue[$key]['longitude']);
            unset($tmpValue[$key]['latitude']);
            array_push($value,$tmpValue[$key]);
        }

        return response()->json([
            'status' => 'success', 
            'message'=>'get location berhasil',
            'data'=> $value  
        ],200);
       
    }

    public function GetDetailLocation(Request $request){
        $this->dbLocation = $this->database->getReference('MELCOSH/Location/'.$request->get('id_lokasi'));
        $value = $this->dbLocation->getSnapshot()->getvalue();
        return response()->json([
            'status' => 'success', 
            'message'=>'get detail location berhasil',
            'data'=> $value  
        ],200);
       
    }

}
