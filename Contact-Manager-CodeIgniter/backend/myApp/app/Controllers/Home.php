<?php

namespace App\Controllers;

class Home extends BaseController
{
    public function index()
    {
        $model = new \App\Models\ContactsModel();
        $jsonString = $model->display(); 
        $data = json_decode($jsonString, true);

        header('Access-Control-Allow-Origin: *');
        header('Content-Type: application/json');

        return $this->response->setJSON($data);
    }

    public function getContacts() {
        $model = new \App\Models\ContactsModel();
        $jsonString = $model->display(); 
        $data = json_decode($jsonString, true);

        header('Access-Control-Allow-Origin: *');
        header('Content-Type: application/json');

        return $this->response->setJSON($data);
    }

    public function editContacts() {
        $model = new \App\Models\ContactsModel();
        $jsonString = $model->edit(); 
        $data = json_decode($jsonString, true);

        header('Access-Control-Allow-Origin: *');
        header('Content-Type: application/json');
        
        return $this->response->setJSON($data);
    } 
    
    public function newContacts() {
        $model = new \App\Models\ContactsModel();
        $jsonString = $model->new(); 
        $data = json_decode($jsonString, true);

        header('Access-Control-Allow-Origin: *');
        header('Content-Type: application/json');
        
        return $this->response->setJSON($data);
    }

    public function deleteContacts() {
        $model = new \App\Models\ContactsModel();
        $jsonString = $model->deleteCon(); 
        $data = json_decode($jsonString, true);

        header('Access-Control-Allow-Origin: *');
        header('Content-Type: application/json');

        return $this->response->setJSON($data);
    }
}
