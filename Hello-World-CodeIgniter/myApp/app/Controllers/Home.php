<?php

namespace App\Controllers;

class Home extends BaseController
{
    public function index(): string
    {
        $model = new \App\Models\UserModel();
        $data = $model->findAll();
        return view('helloView', ['data' => $data]);
    }
}
