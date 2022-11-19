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

class MakananController extends Controller
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


    public function InsertMakanan(Request $request){
        $validator = Validator::make($request->all(), [
            'nama_makanan' =>'required|string|min:5|max:30',
            'harga' => 'required',
            'deskripsi' => 'required|string|min:5|max:100',
            'image' => 'required|mimes:jpg,png|max:1024|min:10'
        ]);
       
        if ($validator->fails()) {
            return response()->json(["success" => false, "message" => $validator->errors()], 400);
           
        }

        $this->dbMakanan = $this->database->getReference('MELCOSH/MAKANAN');
        $tmpValue = $this->dbMakanan->getValue();
        $value = array();
        foreach (array_keys($tmpValue) as $key) {
            $tmpValue[$key]['id'] = $key;
            array_push($value,$tmpValue[$key]);
         }
         if ($tmpValue[$key]['nama_makanan'] == $request->get('nama_makanan')) {
            
            return response()->json([
                'status' => 'failed', 
                'message'=> 'produk yang diinputkan sudah ada'   
            ],400);
            
        }

            $dbMakanan = $this->database->getReference('MELCOSH/MAKANAN')->push([
                'nama_makanan' => $request->get('nama_makanan'),
                 'harga' => $request->get('harga'),
                 'deskripsi' => $request->get('deskripsi'),
                 'Jumlah_stok' => $request->get('Jumlah_stok'),
                 'flag_stok' => 'Y'
                 
                
            ]);
            $expiresAt = new \DateTime('tomorrow');
            $image = $request->file('image');  
            $firebase_storage_path = 'Makanan/';
            $idMakanan = $dbMakanan->getKey();
            $localfolder = public_path('firebase-temp-uploads') .'/';
            $extension = $image->getClientOriginalExtension();
            $file      = $idMakanan. '.' . $extension;
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
                $gambar =  $this->storage->getBucket()->object('Members/'.$idMakanan.'.jpg');
                $image = $gambar->signedUrl($expiresAt);
              }
              $this->dbMakanan = $this->database->getReference('MELCOSH/MAKANAN/'.$idMakanan)->UPDATE([
                'gambar' => $image
              ]);

                    return response()->json([
                        'status' => 'success', 
                        'message'=>'insert makanan berhasil'   
                    ],200);
        

    }

    public function GetMakanan(){
        $this->dbMakanan = $this->database->getReference('MELCOSH/MAKANAN');
        $tmpValue = $this->dbMakanan->getValue();
        $value = array();
        foreach (array_keys($tmpValue) as $key) {
            if ($tmpValue[$key]['flag_stok'] == 'Y') {
                $tmpValue[$key]['id'] = $key;
            unset($tmpValue[$key]['deskripsi']);
            unset($tmpValue[$key]['harga']);
            unset($tmpValue[$key]['flag_stok']);
            unset($tmpValue[$key]['Jumlah_stok']);
            array_push($value,$tmpValue[$key]);   
            }  
                
         }
         return response()->json([
            'status' => 'success', 
            'message'=> 'get data makanan berhasil',
            'data' => $value
        ],200);

    }

    public function GetDetailMakanan(Request $request){
        $this->dbMakanan = $this->database->getReference('MELCOSH/MAKANAN/'.$request->get('id_produk'));
        $value = $this->dbMakanan->getSnapshot()->getvalue();
        return response()->json([
            'status' => 'success', 
            'message'=> 'get detail makanan berhasil',
            'data' => $value
        ],200);
       
    }

    public function updateProduk(Request $request)
    {
        $produk = $request->get('produk');
        $id_produk = $request->get('id_produk');
        $jumlah = $request->get('jumlah_stok');

        $db = $this->database->getReference('MELCOSH/'.$produk.'/'.$id_produk)->UPDATE([              
            'Jumlah_stok' => $jumlah,
            'flag_stok' => 'Y'
        ]);

        return response()->json([
            'status' => 'success', 
            'message'=> 'update jumlah stok berhasil'
        ],200);

        // $this->dbMakanan = $this->database->getReference('MELCOSH/MAKANAN');
        // $tmpValue = $this->dbMakanan->getValue();
        // $value = array();
        // foreach (array_keys($tmpValue) as $key) {
        //     if ($tmpValue[$key]['flag_stok'] == 'Y') {
        //     array_push($value,$tmpValue[$key]);   

        //     }  
                
        //  }

    }
}
