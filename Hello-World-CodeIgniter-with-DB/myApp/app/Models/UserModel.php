<?php

namespace App\Models;

use CodeIgniter\Model;

class UserModel extends Model
{
    protected $file = APPPATH.'words.json';

    public function message()
    {
        // $data = file_get_contents($this->file);
        // return json_decode($data, true);
        
        $db = \Config\Database::connect();
        $builder = $db->table('Messages');
        $query = $builder->get();
        
        return $query->getResultArray();
    }
}