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

    /**
     * @param $id
     * @return static|null
     */
    public static function find($id)
    {
        $table = static::$table;
        $pdo = DB::connection()->getPdo();
        $query = $pdo->prepare("SELECT * FROM $table WHERE id = :id");

        try {
            $query->execute(['id' => $id]);
        } catch (\Exception $e) {
            return null;
        }

        $data = $query->fetch($pdo::FETCH_ASSOC);

        if ($data === false) {
            return null;
        }

        return new static($data);
    }
}
