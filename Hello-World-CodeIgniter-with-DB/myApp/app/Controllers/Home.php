<?php

namespace App\Controllers;

class Home extends BaseController
{
    public function index(): string
    {
        $model = new \App\Models\UserModel();
        $data = $model->message();
        return view('helloView', ['data' => $data]);
    }
}
