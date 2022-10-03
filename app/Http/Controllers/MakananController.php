<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Kreait\Firebase\Factory;
use Kreait\Firebase\ServiceAccount;
use \Kreait\Firebase\Database;
use Google\Cloud\Firestore\FirestoreClient;

class MakananController extends Controller
{
    protected $databsae;
    protected $db;
    public function __construct(){
        $factory = (new Factory)
        ->withServiceAccount(__DIR__.'/firebase.json')
        ->withDatabaseUri('https://melcosh-a4d4b-default-rtdb.firebaseio.com/');
        $this->auth = $factory->createAuth();
        $this->database = $factory->createDatabase();
        // $this->firestore = $factory->createFirestore();
    }


    public function InsertMakanan(request $Request){
        $dbMakanan = $this->database->getReference('MELCOSH/MAKANAN')->push()
        ->set([
            'MKN01' => [
                'nama_makanan' => 'Nasi GORENG',
                 'HARGA' => '15.000',
            ]
        ]);
    }

    public function GetMakanan(){
        $this->dbMakanan = $this->database->getReference('MELCOSH/MAKANAN');
        $value = $this->dbMakanan->getSnapshot()->getvalue();
        dd($value);
        // $reference = $this->database->getReference('Makanan/');
        // $snapshot = $reference->getSnapshot();
        // $value = $snapshot->getValue();  
        // dd($value);
    }
}
