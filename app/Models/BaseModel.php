<?php

namespace App\Models;

use Illuminate\Support\Facades\DB;

class BaseModel
{
    public $fields = array();

    protected $pdo;

    public function __construct($data)
    {
        foreach ($data as $column => $value) {
            $this->fields[$column] = $value;
        }

        $this->pdo = DB::connection()->getPdo();
    }

    public function __set($property, $value)
    {
        return $this->fields[$property] = $value;
    }

    public function __get($property)
    {
        return array_key_exists($property, $this->fields)
            ? $this->fields[$property]
            : null;
    }
}
