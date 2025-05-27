<?php

namespace App\Models;

use CodeIgniter\Model;

class UserModel extends Model
{
    protected $file = APPPATH.'words.json';

    public function findAll(?int $limit = null, int $offset = 0)
    {
        $data = file_get_contents($this->file);
        return json_decode($data, true);
    }
}