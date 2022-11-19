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
use Kreait\Firebase\Database\Reference;

class KeranjangController extends Controller
{
    protected $database;
    protected $auth;
    public function __construct(){
        $factory = (new Factory)
        ->withServiceAccount(__DIR__.'/melcosh-a4d4b-firebase-adminsdk-p6qdf-104fcb504f.json')
        ->withDatabaseUri('https://melcosh-a4d4b-default-rtdb.firebaseio.com/');
        $this->database = $factory->createDatabase();
        $this->auth = $factory->createAuth();
    }

    public function InsertKeranjang(Request $request){
        // $id_user=$request->get('iduser');  
        $id_produk = $request->get('id_produk');
        
        try {
            if(empty($request->bearerToken())){
                return "Beare token harus ada";
            }
            $verifiedIdToken = $this->auth->verifyIdToken($request->bearerToken());
            $uid = $verifiedIdToken->claims()->get('sub');
       
        $this->dbMakanan = $this->database->getReference('MELCOSH/MAKANAN/'.$id_produk);
        $valuemakanan = $this->dbMakanan->getSnapshot()->getvalue();

        if ($valuemakanan == NULL) {
            $this->dbMinuman = $this->database->getReference('MELCOSH/MINUMAN/'.$id_produk);
            $valueMinuman = $this->dbMinuman->getSnapshot()->getvalue();

                if ($valueMinuman == NULL) {
                    $this->dbMerch = $this->database->getReference('MELCOSH/MERCHANDISE/'.$id_produk);
                    $valueMecrh = $this->dbMerch->getSnapshot()->getvalue();

                        if ($valueMecrh == NULL) {
                                $this->dbWB = $this->database->getReference('MELCOSH/WHOLEBEAN/'.$id_produk);
                                $valuewb = $this->dbWB->getSnapshot()->getvalue();

                                $this->db = $this->database->getReference('MELCOSH/Keranjang/'.$uid);
                                $tmpValue = $this->db->getValue();
                                $value = array();
                                if (isset(  $tmpValue)){
                                    foreach (array_keys($tmpValue) as $key) {            
                                        if($tmpValue[$key]['nama_produk'] == $valuewb['nama_wb']){
                                            if ($valuewb[$key]['jumlah_stok']=='0') {
                                                $db = $this->database->getReference('MELCOSH/WHOLEBEAN/'.$id_produk)->UPDATE([
                            
                                                    'flag_stok' => 'N'
                                                    
                                                ]);
                                                return response()->json([
                                                    'status' => 'failed', 
                                                    'message'=> 'maaf stok sudah habis'   
                                                ],400);
                                            }
                                            $db = $this->database->getReference('MELCOSH/Keranjang/'.$uid.'/'.$key)->UPDATE([
                                            
                                                'nama_produk' => $valuewb['nama_wb'],
                                                'harga' => $valuewb['harga'],
                                                'jumlah' => $tmpValue[$key]['jumlah']+1
                                                
                                            ]);
                                            $db = $this->database->getReference('MELCOSH/WholWHOLEBEAN/'.$id_produk)->UPDATE([
                            
                                                'Jumlah_stok' => $valuewb['Jumlah_stok']-1
                                                
                                            ]);
                                            return response()->json([
                                                'status' => 'success', 
                                                'message'=>'insert keranjang berhasil'   
                                            ],200);
                                       
                                };
                                 }
                            } 
                                $db = $this->database->getReference('MELCOSH/Keranjang/'.$uid)->PUSH([
                                    
                                        'nama_produk' => $valuewb['nama_wb'],
                                        'harga' => $valuewb['harga'],
                                        'jumlah' => '1'
                                      
                                    
                                
                                ]);
                                $db = $this->database->getReference('MELCOSH/WHOLEBEAN/'.$id_produk)->UPDATE([
                            
                                    'Jumlah_stok' => $valuewb['Jumlah_stok']-1
                                    
                                ]);
                                return response()->json([
                                    'status' => 'success', 
                                    'message'=>'insert keranjang berhasil'   
                                ],200);

                         }else {
                            $this->db = $this->database->getReference('MELCOSH/Keranjang/'.$uid);
                            $tmpValue = $this->db->getValue();
                            $value = array();
                            if (isset(  $tmpValue)){
                                foreach (array_keys($tmpValue) as $key) {            
                                    if($tmpValue[$key]['nama_produk'] == $valueMecrh['nama_merch']){
                                        if ($valueMecrh[$key]['Jumlah_stok']=='0') {
                                            $db = $this->database->getReference('MELCOSH/MERCHANDISE/'.$id_produk)->UPDATE([
                            
                                                'flag_stok' => 'N'
                                                
                                            ]);
                                            return response()->json([
                                                'status' => 'failed', 
                                                'message'=> 'maaf stok sudah habis'   
                                            ],400);
                                        }
                                        $db = $this->database->getReference('MELCOSH/Keranjang/'.$uid.'/'.$key)->UPDATE([
                                        
                                            'nama_produk' => $valueMecrh['nama_merch'],
                                            'harga' => $valueMecrh['harga'],
                                            'jumlah' => $tmpValue[$key]['jumlah']+1
                                            
                                        ]);
                                        $db = $this->database->getReference('MELCOSH/MERCHANDISE/'.$id_produk)->UPDATE([
                            
                                            'Jumlah_stok' => $valueMecrh['Jumlah_stok']-1
                                            
                                        ]);
                                        return response()->json([
                                            'status' => 'success', 
                                            'message'=>'insert keranjang berhasil'   
                                        ],200);
                                    }
                                }
                            };
                             

                            $db = $this->database->getReference('MELCOSH/Keranjang/'.$uid)->PUSH([
                                
                                    'nama_produk' => $valueMecrh['nama_merch'],
                                    'harga' => $valueMecrh['harga'],
                                    'jumlah' => '1'
                                    
                            ]);
                            $db = $this->database->getReference('MELCOSH/MERCHANDISE/'.$id_produk)->UPDATE([
                            
                                'Jumlah_stok' => $valueMecrh['Jumlah_stok']-1
                                
                            ]);
                            return response()->json([
                                'status' => 'success', 
                                'message'=>'insert keranjang berhasil'   
                            ],200);
                        }

                }else {
                    $this->db = $this->database->getReference('MELCOSH/Keranjang/'.$uid);
                    $tmpValue = $this->db->getValue();
                    $value = array();
                    if (isset(  $tmpValue)){
                        foreach (array_keys($tmpValue) as $key) {            
                            if($tmpValue[$key]['nama_produk'] == $valueMinuman['nama_minuman']){
                                if ($valueMinuman['Jumlah_stok']=='0') {
                                    $db = $this->database->getReference('MELCOSH/MINUMAN/'.$id_produk)->UPDATE([
                            
                                        'flag_stok' => 'N'
                                        
                                    ]);
                                    return response()->json([
                                        'status' => 'failed', 
                                        'message'=> 'maaf stok sudah habis'   
                                    ],400);
                                }
                                $db = $this->database->getReference('MELCOSH/Keranjang/'.$uid.'/'.$key)->UPDATE([
                                
                                        'nama_produk' => $valueMinuman['nama_minuman'],
                                        'harga' => $valueMinuman['harga'],
                                        'jumlah' => $tmpValue[$key]['jumlah']+1
                                    
                                ]);
                                $db = $this->database->getReference('MELCOSH/MINUMAN/'.$id_produk)->UPDATE([
                            
                                    'jumlah_stok' => $valueMinuman['Jumlah_stok']-1
                                    
                                ]);
                                return response()->json([
                                    'status' => 'success', 
                                    'message'=>'insert keranjang berhasil'   
                                ],200);
                            }
                        } 
                    };
                    

                    $db = $this->database->getReference('MELCOSH/Keranjang/'.$uid)->PUSH([
                        
                                'nama_produk' => $valueMinuman['nama_minuman'],
                                'harga' => $valueMinuman['harga'],
                                'jumlah' => '1'
                              
                    ]);
                    $db = $this->database->getReference('MELCOSH/MINUMAN/'.$id_produk)->UPDATE([
                            
                        'Jumlah_stok' => $valueMinuman['Jumlah_stok']-1
                        
                    ]);
                    return response()->json([
                       'status' => 'success', 
                       'message'=>'insert keranjang berhasil'   
                   ],200);
                }                
        }else { 



            $this->db = $this->database->getReference('MELCOSH/Keranjang/'.$uid);
                    $tmpValue = $this->db->getValue();
                    $value = array();
                if (isset($tmpValue )){
                    foreach (array_keys($tmpValue) as $key) {            
                        if($tmpValue[$key]['nama_produk'] == $valuemakanan['nama_makanan']){
                            if ($valuemakanan['Jumlah_stok']=='0') {
                                $db = $this->database->getReference('MELCOSH/MAKANAN/'.$id_produk)->UPDATE([
                            
                                    'flag_stok' => 'N'
                                    
                                ]);
                                return response()->json([
                                    'status' => 'failed', 
                                    'message'=> 'maaf stok sudah habis'   
                                ],400);
                            }
                            $db = $this->database->getReference('MELCOSH/Keranjang/'.$uid.'/'.$key)->UPDATE([
                            
                                'nama_produk' => $valuemakanan['nama_makanan'],
                                'harga' => $valuemakanan['harga'],
                                'jumlah' => $tmpValue[$key]['jumlah']+1
                                
                                
                            ]);
                            $db = $this->database->getReference('MELCOSH/MAKANAN/'.$id_produk)->UPDATE([
                            
                                'Jumlah_stok' => $valuemakanan['Jumlah_stok']-1
                                
                            ]);
                            
                            return response()->json([
                                'status' => 'success', 
                                'message'=>'insert keranjang berhasil'   
                            ],200);
                        }
                    } 
                };
                 
            
            $db = $this->database->getReference('MELCOSH/Keranjang/'.$uid)->PUSH([
                    
                'nama_produk' => $valuemakanan['nama_makanan'],
                'harga' => $valuemakanan['harga'],
                'jumlah' => '1'
            
            ]);
            $db = $this->database->getReference('MELCOSH/MAKANAN/'.$id_produk)->UPDATE([
                            
                'Jumlah_stok' => $valuemakanan['Jumlah_stok']-1
                
            ]);
            return response()->json([
                'status' => 'success', 
                'message'=>'insert keranjang berhasil'   
            ],200);
        }   
    } catch  (FailedToVerifyToken $e) {
        return 'The token is invalid: '.$e->getMessage();
    }

    } 
    

    public function getKeranjang(Request $request){
        try {
            if(empty($request->bearerToken())){
                return "Beare token harus ada";
            }
            $verifiedIdToken = $this->auth->verifyIdToken($request->bearerToken());
            $uid = $verifiedIdToken->claims()->get('sub');
            
        $this->db = $this->database->getReference('MELCOSH/Keranjang/'.$uid);
        $tmpValue = $this->db->getValue();
        $value = array();

        if (isset($tmpValue)) {
            foreach (array_keys($tmpValue) as $key) {
                $tmpValue[$key]['id'] = $key;
                  array_push($value,$tmpValue[$key]);
                  
              }
              return response()->json([
                  'status' => 'success', 
                  'message'=>'get keranjang berhasil',
                  'data' =>  $value  
              ],200);
        } else{
            return response()->json([
                'status' => 'failed', 
                'message'=>'Belum Menambahkan produk kedalam keranjang', 
            ],200);
        }
        
       

     } catch  (FailedToVerifyToken $e) {
        return 'The token is invalid: '.$e->getMessage();
    }
    }

    public function checkout(Request $request){
        
        try {
            if(empty($request->bearerToken())){
                return "Beare token harus ada";
            }
            $verifiedIdToken = $this->auth->verifyIdToken($request->bearerToken());
            $uid = $verifiedIdToken->claims()->get('sub');
      
        // return count($request->produk);
        $this->db = $this->database->getReference('MELCOSH/Keranjang/'.$uid);
        $tmpValue = $this->db->getValue();
        $produk = array();

        foreach ($request->produk as $produk_key) {
            
            foreach (array_keys($tmpValue) as $key) {
                # code...
                if($key == $produk_key){
                    $tmpProduk = [
                        'nama_produk' => $tmpValue[$key]['nama_produk'],
                        'harga' => $tmpValue[$key]['harga'],
                        'jumlah' => $tmpValue[$key]['jumlah'],
                        'id_produk' =>$key

                    ];
                    array_push($produk,$tmpProduk);
                    $db = $this->database->getReference('MELCOSH/Keranjang/'.$uid.'/'.$key)->REMOVE();

                }
            }
        }
        $db = $this->database->getReference('MELCOSH/Transaksi/'.$uid)->PUSH([
                        
            'list_produk' => $produk,
            'status'  => 'proses',
            'dt_added' => date('l, d-m-Y'),
            'exp_date' => date('l, d-m-Y'),
            'kode_bayar' => 'mlcs'. random_int(100000, 999999),
            'kode_promo'=> $request->get('kode_promo'),
            'potongan' =>  $request->get('potongan'),
            'poin' => $request->get('point')
        ]);

        return response()->json([
            'status' => 'success', 
            'message'=>'checkout berhasil'   
            ],200);
        } catch  (FailedToVerifyToken $e) {
            return 'The token is invalid: '.$e->getMessage();
        }
    }
    

}