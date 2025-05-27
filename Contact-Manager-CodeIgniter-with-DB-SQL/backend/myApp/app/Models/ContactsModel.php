<?php

namespace App\Models;

use CodeIgniter\Model;

class ContactsModel extends Model
{
    public function display(){   
        $db = \Config\Database::connect();
        $sqlPath = WRITEPATH . 'sql/display.sql';
        $sql = file_get_contents($sqlPath);
        return $db->query($sql)->getResultArray();
        
    }
    
    public function edit(){
        $db = \Config\Database::connect();
        $sqlPath = WRITEPATH . 'sql/edit.sql';
        $sql = file_get_contents($sqlPath);

        $incomingData = file_get_contents("php://input");
        $data = json_decode($incomingData, true);

        $db->query($sql, [$data['name'], $data['email'], $data['phone'], $data['id']]);
    }

    public function new(){
        $db = \Config\Database::connect();
        $sqlPath = WRITEPATH . 'sql/new.sql';
        $sql = file_get_contents($sqlPath);

        $incomingData = file_get_contents("php://input");
        $data = json_decode($incomingData, true);

        $db->query($sql, [$data['name'], $data['email'],$data['phone']]);
    }

    public function deleteCon(){
        $db = \Config\Database::connect();
        $sqlPath = WRITEPATH . 'sql/delete.sql';
        $sql = file_get_contents($sqlPath);

        $incomingData = file_get_contents("php://input");
        $data = json_decode($incomingData, true);

        $db->query($sql, $data['id']);
    }
}