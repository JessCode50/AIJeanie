<?php

namespace App\Controllers;

class Home extends BaseController
{
    public function index()
    {
        $model = new \App\Models\ContactsModel();
        $data = $model->findAll(); 

        return $this->response->setJSON($data);
    }

    public function getContacts() {
        $model = new \App\Models\ContactsModel();
        $data= $model->findAll(); 

        return $this->response->setJSON($data);
    }

    public function editContacts() {
        $model = new \App\Models\ContactsModel();
        $data = $this->request->getJSON(true);
        $update = $model->update($data['id'], $data); 
        
        return $this->response->setJSON($data);
    } 
    
    public function newContacts() {
        $model = new \App\Models\ContactsModel();
        $data = $this->request->getJSON(true); //note that true means conversion to associative array
        $insert = $model->insert($data); 
        
        return $this->response->setJSON($data);
    }

    public function deleteContacts() {
        $model = new \App\Models\ContactsModel();
        $data = $this->request->getJSON(true);
        $delete = $model->delete($data['id']); 

        return $this->response->setJSON($data);
    }
}
