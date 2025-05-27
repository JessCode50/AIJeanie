<?php

namespace App\Models;

use CodeIgniter\Model;

class ContactsModel extends Model
{
    public function display(){       
        $db = \Config\Database::connect();
        $builder = $db->table('contacts');
        $query = $builder->get();
        
        return $query->getResultArray();
    }
    
    public function edit(){
        $db = \Config\Database::connect();
        $builder = $db->table('contacts');

        $incomingData = file_get_contents("php://input");
        $data = json_decode($incomingData, true);

        $newData = [
            'name'         => $data['name'],
            'email'        => $data['email'],
            'phone'        => $data['phone'],
        ];

        $builder->where('id', $data['id']);
        $builder->update($newData);
    }

    public function new(){
        $db = \Config\Database::connect();
        $builder = $db->table('contacts');

        $incomingData = file_get_contents("php://input");
        $data = json_decode($incomingData, true);

        $newData = [
            'name'         => $data['name'],
            'email'        => $data['email'],
            'phone'        => $data['phone'],
        ];
        
        $builder->insert($data);
    }

    public function deleteCon(){
        $incomingData = file_get_contents("php://input");
        $data = json_decode($incomingData, true); 

        $db = \Config\Database::connect();
        $builder = $db->table('contacts');
        
        $builder->delete(['id' => $data['id']]);
    }
}