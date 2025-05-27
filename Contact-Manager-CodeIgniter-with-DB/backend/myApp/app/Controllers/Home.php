<?php

namespace App\Controllers;

class Home extends BaseController
{
    public function index()
    {
        $model = new \App\Models\ContactsModel();
        $data = $model->display(); 

        header('Access-Control-Allow-Origin: *');
        header('Content-Type: application/json');

        return $this->response->setJSON($data);
    }

    public function getContacts() {
        $model = new \App\Models\ContactsModel();
        $data= $model->display(); 

        header('Access-Control-Allow-Origin: *');
        header('Content-Type: application/json');

        return $this->response->setJSON($data);
    }

    public function editContacts() {
        $model = new \App\Models\ContactsModel();
        $data = $model->edit(); 

        header('Access-Control-Allow-Origin: *');
        header('Content-Type: application/json');
        
        return $this->response->setJSON($data);
    } 
    
    public function newContacts() {
        $model = new \App\Models\ContactsModel();
        $data = $model->new(); 

        header('Access-Control-Allow-Origin: *');
        header('Content-Type: application/json');
        
        return $this->response->setJSON($data);
    }

    public function deleteContacts() {
        $model = new \App\Models\ContactsModel();
        $data = $model->deleteCon(); 

        header('Access-Control-Allow-Origin: *');
        header('Content-Type: application/json');

        return $this->response->setJSON($data);
    }
}
