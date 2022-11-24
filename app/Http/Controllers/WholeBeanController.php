<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Kreait\Firebase\Factory;
use Kreait\Firebase\ServiceAccount;
use \Kreait\Firebase\Database;
use Google\Cloud\Firestore\FirestoreClient;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Session;
use Kreait\Firebase\Contract\Storage;

class WholeBeanController extends Controller
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

    public function InsertWB(Request $request){
        $validator = Validator::make($request->all(), [
            'nama_wb' =>'required|string|min:5|max:20',
            'harga' => 'required',
            'deskripsi' => 'required|string|min:10|max:100',
            'image' => 'required|mimes:jpg,png|max:1024|min:10',
            
        ]);
        if ($validator->fails()) {
            return response()->json(["success" => false, "message" => $validator->errors()], 400);
        }else {
            $this->dbWB = $this->database->getReference('MELCOSH/WHOLEBEAN');
            $tmpValue = $this->dbWB->getValue();
            $value = array();
            if(isset($tmpValue)){
                foreach (array_keys($tmpValue) as $key) {
                    $tmpValue[$key]['id'] = $key;
                    array_push($value,$tmpValue[$key]);
                 }
                 if ($tmpValue[$key]['nama_wb'] == $request->get('nama_wb')) {
                    return response()->json([
                        'status' => 'failed', 
                        'message'=> 'produk yang diinputkan sudah ada'   
                    ],400);
                    
                }
            }
            
        $dbWB = $this->database->getReference('MELCOSH/WHOLEBEAN')->push([
            'nama_wb' => $request->get('nama_wb'),
            'harga' => $request->get('harga'),
            'deskripsi' => $request->get('deskripsi'),
            'Jumlah_stok' => $request->get('Jumlah_stok'),
            'flag_stok' => 'Y'
        ]);
        $expiresAt = new \DateTime('tomorrow');
                $image = $request->file('image');  //image file from frontend
                $firebase_storage_path = 'WHOLEBEAN/';
                $idWb = $dbWB->getKey();
                $localfolder = public_path('firebase-temp-uploads') .'/';
                $extension = $image->getClientOriginalExtension();
                $file      = $idWb. '.' . $extension;
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
                    $gambar =  $this->storage->getBucket()->object('WHOLEBEAN/'.$idWb.'.jpg');
                    $image = $gambar->signedUrl($expiresAt);
                  }
                  $this->dbWB = $this->database->getReference('MELCOSH/WHOLEBEAN/'.$idWb)->UPDATE([
                    'gambar' => $image
                  ]);
    
    
                        return response()->json([
                            'status' => 'success', 
                            'message'=>'insert Wholebean berhasil'   
                        ],200);
        }
    }

    public function GetWB(){
        $this->dbWB = $this->database->getReference('MELCOSH/WHOLEBEAN');
        $tmpValue = $this->dbWB->getValue();
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
            'status' => 'success', 
            'message'=>'get data wholebean berhasil',
            'data' => $value   
        ],200);
         
       
    }

    public function GetDetailWB(Request $request){
        $this->dbWB = $this->database->getReference('MELCOSH/WHOLEBEAN/'.$request->get('id_produk'));
        $value = $this->dbWB->getSnapshot()->getvalue();
        return response()->json([
            'status' => 'success', 
            'message'=>'get detail wholebean berhasil',
            'data' => $value   
        ],200);
       
    }
}
