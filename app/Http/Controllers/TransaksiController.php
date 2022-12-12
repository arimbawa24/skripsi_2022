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
class TransaksiController extends Controller
{
    protected $database;
    protected $auth;
    public function __construct(){
        $factory = (new Factory)
        ->withServiceAccount(__DIR__.'/melcosh-a4d4b-firebase-adminsdk-p6qdf-104fcb504f.json')
        ->withDatabaseUri('https://melcosh-a4d4b-default-rtdb.firebaseio.com/');
        $this->database = $factory->createDatabase();
        $this->auth = $factory->createAuth();
        $this->firestore = $factory->createFirestore();
    }

    public function getTransaksiUser(Request $request){
        try {
            if(empty($request->bearerToken())){
                return "Beare token harus ada";
            }
            $verifiedIdToken = $this->auth->verifyIdToken($request->bearerToken());
            $uid = $verifiedIdToken->claims()->get('sub');
        
        $this->db = $this->database->getReference('MELCOSH/Transaksi/'.$uid);
        $tmpValue = $this->db->getValue();
        $value = array();
        
        foreach (array_keys($tmpValue) as $key) {
            
            if(strtotime(date('l, d-m-Y')) > strtotime($tmpValue[$key]['exp_date']) && $tmpValue[$key]['status'] == 'proses' ){
                
                $db = $this->database->getReference('MELCOSH/Transaksi/'.$uid.'/'.$key)->UPDATE([        
                    'status' => 'expired',
                ]);      
            
            }
            
        }  
        
        $this->db = $this->database->getReference('MELCOSH/Transaksi/'.$uid);
        $tmpValue = $this->db->getValue();
        return response()->json([
            'status' => 'success', 
            'message'=>'get data transaksi user berhasil',
            'data' => $tmpValue
        ],200);
      
    } catch (FailedToVerifyToken $e) {
        return 'The token is invalid: '.$e->getMessage();
     }
}

    public function GetKodeTransaksi(Request $request){
        $kode_bayar = $request->get('kode_bayar');
        $uid = $request->get('uid');

        $this->db = $this->database->getReference('MELCOSH/Transaksi/');
        $tmpValue = $this->db->getValue();
        $transaksi = array();
        
    
        foreach (array_keys($tmpValue) as $key) {
            foreach (array_keys($tmpValue[$key]) as $key2) {
                // return $tmpValue[$key][$key2]['kode_bayar'];
                array_push($transaksi,['user_id'=> $key,'transaksi_user'=> $key2,'kode_bayar' => $tmpValue[$key][$key2]['kode_bayar']]);
            }
        
        }
       
        $data_transaksi = array();
    

        foreach ($transaksi as $trnks) {
    
           if ($trnks['kode_bayar'] == $kode_bayar) {
            
            $this->db = $this->database->getReference('MELCOSH/Transaksi/'.$trnks['user_id'].'/'.$trnks['transaksi_user']);
            $tmpValue1 = $this->db->getValue();

            $tmpValue1['user_id']=$trnks['user_id'];
            $tmpValue1['transaksi_user']=$trnks['transaksi_user'];
          
           }
       
        }

            return response()->json([
                'status' => 'success', 
                'message'=>'get data kode bayar berhasil',
                'data' => $tmpValue1  
            ],200);
        }

        public function bayar(Request $request){
            $this->db = $this->database->getReference('MELCOSH/Transaksi/'.$request->get('user_id').'/'.$request->get('transaksi_id'))->UPDATE([
                'status' => 'selesai'
            ]);

            $this->db = $this->database->getReference('MELCOSH/Transaksi/'.$request->get('user_id').'/'.$request->get('transaksi_id'));
            $tmpValue = $this->db->getValue();
           
                foreach ($tmpValue['list_produk'] as $produk) {
                    $id_produk = $produk['id_produk'];
                    $jumlah = $produk['jumlah'];

                    $this->dbMakanan = $this->database->getReference('MELCOSH/MAKANAN/'.$id_produk);
                    $valuemakanan = $this->dbMakanan->getSnapshot()->getvalue();
                    
                    if ($valuemakanan == NULL) {
                        $this->dbMinuman = $this->database->getReference('MELCOSH/MINUMAN/'.$id_produk);
                        $valueMinuman = $this->dbMinuman->getSnapshot()->getvalue();
                        if ($valueMinuman == NULL) {
                            $this->dbMerch = $this->database->getReference('MELCOSH/MERCHANDISE/'.$id_produk);
                            $valueMecrh = $this->dbMerch->getSnapshot()->getvalue();
                            if ($valueMecrh == NULL) {
                            $db = $this->database->getReference('MELCOSH/WholWHOLEBEAN/'.$id_produk)->UPDATE([
                                        'Jumlah_stok' => $valuewb['Jumlah_stok'] - $jumlah
                                                    
                                        ]);
                            }else {
                                $db = $this->database->getReference('MELCOSH/MERCHANDISE/'.$id_produk)->UPDATE([
                                    'Jumlah_stok' => $valueMecrh['Jumlah_stok'] - $jumlah
                                            
                                ]);
                                }
                            }else {
                                $db = $this->database->getReference('MELCOSH/MINUMAN/'.$id_produk)->UPDATE([
                                    'Jumlah_stok' => $valueMinuman['Jumlah_stok'] - $jumlah
                                            
                                ]);
                            }

                        }else { 
                            $db = $this->database->getReference('MELCOSH/MAKANAN/'.$id_produk)->UPDATE([
                                'Jumlah_stok' => $valuemakanan['Jumlah_stok'] - $jumlah
                                        
                            ]);
                        }   
                    }

            $database = $this->firestore->database();
            $collectionReference = $database->collection('Members');
            $documentReference = $collectionReference->document($request->get('user_id'));
            $snapshot = $documentReference->snapshot();

            $point= $snapshot['Point']+ $request->get('point');

            $database = $this->firestore->database();
            $collectionReference = $database->collection('Members');
            $documentReference = $collectionReference->document($request->get('user_id'))->update([
                ['path' => 'Point', 'value' => $point ],
            ]); 

            return response()->json([
                'status' => 'success', 
                'message'=>'pembayaran berhasil', 
            ],200);

        }
        
    public function GetHistoryTransaksi(){
        $this->db = $this->database->getReference('MELCOSH/Transaksi/');
        $tmpValue = $this->db->getValue();
        $transaksi = array();
        foreach (array_keys($tmpValue) as $key) {
            foreach (array_keys($tmpValue[$key]) as $key2) {
                array_push($transaksi,$tmpValue[$key][$key2]);
            }
        }
        return response()->json([
            'status' => 'success', 
            'message'=>'get data history transaksi berhasil',
            'data' =>$transaksi
        ],200);

    }
    
public function tukarPoint(Request $request)
{
    
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
        $point = $snapshot['Point'];

        if ($point < 1000) {
            return response()->json([
                'status' => 'failed', 
                'message'=>'minimal point 1000 untuk melakukan penukaran',
                'data' =>$transaksi
            ],400);
           
        } else { 

            $database = $this->firestore->database();
             $collectionReference = $database->collection('Members');
            $documentReference = $collectionReference->document($uid)->update([
            ['path' => 'Point', 'value' => $point - $request->get('point')],
             ]); 
        }
        return response()->json([
            'status' => 'success', 
            'message'=>'tukar point berhasil',
            'data' =>$transaksi
        ],400);
    } catch (FailedToVerifyToken $e) {
        return 'The token is invalid: '.$e->getMessage();
    }
}

}
