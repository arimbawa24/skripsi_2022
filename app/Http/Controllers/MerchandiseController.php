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

class MerchandiseController extends Controller
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


    public function InsertMerch(Request $request){
        $validator = Validator::make($request->all(), [
            'nama_merch' =>'required|string|min:5|max:30',
            'harga' => 'required',
            'deskripsi' => 'required|string|min:5|max:100',
            'image' => 'required|mimes:jpg,png|max:1024|min:10'
        ]);

        if ($validator->fails()) {
            return response()->json(["success" => false, "message" => $validator->errors()], 400);
        }else {
            $this->dbMerch = $this->database->getReference('MELCOSH/MERCHANDISE');
            $tmpValue = $this->dbMerch->getValue();
            $value = array();

            if(isset($tmpValue)){
                foreach (array_keys($tmpValue) as $key) {
                    $tmpValue[$key]['id'] = $key;
                    array_push($value,$tmpValue[$key]);
                 }
                 if ($tmpValue[$key]['nama_merch'] == $request->get('nama_merch')) {
                    return response()->json([
                        'status' => 'false', 
                        'message'=> 'produk yang diinputkan sudah ada'   
                    ],400);
                    
                }
            }
            
                $dbMerch= $this->database->getReference('MELCOSH/MERCHANDISE')->push([
                    'nama_merch' => $request->get('nama_merch'),
                    'harga' => $request->get('harga'),
                    'deskripsi' => $request->get('deskripsi'),
                    'Jumlah_stok' => $request->get('Jumlah_stok'),
                    'flag_stok' => 'Y'
                    ]);
                    $expiresAt = new \DateTime('tomorrow');
                $image = $request->file('image');  //image file from frontend
                $firebase_storage_path = 'Merchandise/';
                $idMerch = $dbMerch->getKey();
                $localfolder = public_path('firebase-temp-uploads') .'/';
                $extension = $image->getClientOriginalExtension();
                $file      = $idMerch. '.' . $extension;
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
                    $gambar =  $this->storage->getBucket()->object('Merchandise/'.$idMerch.'.jpg');
                    $image = $gambar->signedUrl($expiresAt);
                  }
                  $this->dbMerch = $this->database->getReference('MELCOSH/MERCHANDISE/'.$idMerch)->UPDATE([
                    'gambar' => $image
                  ]);
    
    
                        return response()->json([
                            'status' => 'succes', 
                            'message'=>'insert Merchandise berhasil'   
                        ],200);
        }
        
    }

    public function GetMerch(){
        $this->dbMerch = $this->database->getReference('MELCOSH/MERCHANDISE');
        $tmpValue = $this->dbMerch->getValue();
        $value = array();
        foreach (array_keys($tmpValue) as $key) {
            if ($tmpValue[$key]['flag_stok'] == 'Y') {
                $tmpValue[$key]['id'] = $key;
            unset($tmpValue[$key]['deskripsi']);
            unset($tmpValue[$key]['harga']);
            unset($tmpValue[$key]['flag_stok']);
            array_push($value,$tmpValue[$key]);   
            }  
        }
        return response()->json([
            'status' => 'succes', 
            'message'=>' get data merchandise berhasil',
            'data' =>   $value  
        ],200);
        
       
    }
       
    

    public function GetDetailMerch(Request $request){
        $this->dbMerch = $this->database->getReference('MELCOSH/MERCHANDISE/'.$request->get('id_produk'));
        $value = $this->dbMerch->getSnapshot()->getvalue();
        return response()->json([
            'status' => 'succes', 
            'message'=>' get detail merchandise berhasil',
            'data' =>   $value  
        ],200);
    }
}
