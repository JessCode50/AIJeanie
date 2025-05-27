<?php

namespace App\Models;

use CodeIgniter\Model;

class ContactsModel extends Model
{

    protected $file = APPPATH.'contacts.json';

    public function display(){
        if (!file_exists($this->file)){
            return json_encode(["status" => "error", "message" => "File not found"]);
        }

        $jsonString = file_get_contents($this->file);
        return $jsonString;
    }

    public function edit(){
        $incomingData = file_get_contents("php://input");
        $data = json_decode($incomingData, true);

        if (!isset($data)){
            return json_encode(["status" => "error", "message" => "Invalid contact index provided"]);
            exit;
        }

        if (!file_exists($this->file)){
            return json_encode(["status" => "error", "message" => "File not found"]);
            exit;
        }

        $contacts = file_get_contents($this->file);
        $contacts = json_decode($contacts, true);

        if (!is_array($contacts)){
            return json_encode(["status" => "error", "message" => "File is corrupted"]);
            exit;
        }

        if (!is_numeric($data['index'])){
            return json_encode(["status" => "error", "message" => "Invalid contact index provided"]);
            exit;
        }
        $contacts[$data['index']] = $data['contact'];


        file_put_contents($this->file, json_encode($contacts, JSON_PRETTY_PRINT));
    }

    public function new(){
        $incomingData = file_get_contents("php://input");
        $data = json_decode($incomingData, true);

        if (!isset($data)){
            return json_encode(["status" => "error", "message" => "Invalid contact index provided"]);
            exit;
        }

        if (!file_exists($this->file)){
            return json_encode(["status" => "error", "message" => "File not found"]);
            exit;
        }

        $contacts = file_get_contents($this->file);
        $contacts = json_decode($contacts, true);

        if (!is_array($contacts)){
            return json_encode(["status" => "error", "message" => "File is corrupted"]);
            exit;
        }

        array_push($contacts, $data);
        

        file_put_contents($this->file, json_encode($contacts, JSON_PRETTY_PRINT));
    }

    public function deleteCon(){
        $incomingData = file_get_contents("php://input");
        $data = json_decode($incomingData); 
        
        if (!isset($data) || !is_numeric($data)){
            return json_encode(["status" => "error", "message" => "Invalid contact index provided"]);
            exit;
        }
        
        if (file_exists($this->file)){
            $contacts = file_get_contents($this->file);
            $contacts = json_decode($contacts, true);
        
            if (!is_array($contacts)){
                return json_encode(["status" => "error", "message" => "File is corrupted"]);
                exit;
            }
        }
        
        else {
            return json_encode(["status" => "error", "message" => "File not found"]);
            exit; 
        }
        
        unset($contacts[$data]);
        $contacts = array_values($contacts);
        
        file_put_contents($this->file, json_encode($contacts, JSON_PRETTY_PRINT));
    }
}