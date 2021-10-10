<?php

namespace App\Models;

use Illuminate\Support\Facades\DB;

class Customer
{
    public $fields = array();
    protected static $table = 'customers';

    // The model fields that we can update in bulk
    protected static $fillable = [
        'first_name',
        'last_name',
        'country',
        'email',
        'gender'
    ];

    public function __construct($data)
    {
        foreach ($data as $column => $value) {
            $this->fields[$column] = $value;
        }
    }

    public static function create($data)
    {
        $table = self::$table;
        $pdo = DB::connection()->getPdo();
        $bonus = self::generateBonus();
        $query = $pdo->prepare(
            "INSERT INTO $table
                (first_name, last_name, country, email, gender, bonus, created_at, updated_at)
             VALUES
                (:first_name, :last_name, :country, :email, :gender, :bonus, :created_at, :updated_at)");

        try {
            $query->execute([
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'country' => $data['country'],
                'email' => $data['email'],
                'gender' => $data['gender'],
                'bonus' => $bonus,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Exception $e) {
            return false;
        }

        $lastId = $pdo->lastInsertId();
        return self::find($lastId);
    }

    public static function find($id)
    {
        $table = self::$table;
        $pdo = DB::connection()->getPdo();
        $query = $pdo->prepare("SELECT * FROM $table WHERE id = :id");

        try {
            $query->execute(['id' => $id]);
        } catch (\Exception $e) {
            return false;
        }

        $data = $query->fetch($pdo::FETCH_ASSOC);

        if ($data === false) {
            return null;
        }

        return new self($data);
    }

    public function update($data = [])
    {
        if (!isset($user->id)) {
            throw new \BadMethodCallException(
                "Can't update Customer with no id."
            );
        }

        $table = self::$table;
        $queryString = "UPDATE {$table} SET";
        // Only update fields that we are allowed to
        $updatedFields = [];
        foreach ($data as $key => $value) {
            if (in_array($key, self::$fillable)) {
                $queryString .= " {$key} = :{$key}, ";
                $updatedFields[$key] = $value;
            }
        }

        $queryString .= " updated_at = :updated_at ";
        $queryString .= "WHERE id = :id";

        $pdo = DB::connection()->getPdo();
        $query = $pdo->prepare($queryString);
        $updatedFields["id"] = $this->id;
        $updatedFields["updated_at"] = now();

        try {
            $query->execute($updatedFields);
        } catch (\Exception $e) {
            return false;
        }

        return true;
    }

    public function save()
    {
        if (isset($this->fields["id"])) {
            return $this->update($this->fields);
        }

        $created = self::create($this->fields);

        if ($created === false) {
            return false;
        }

        return true;
    }

    public static function generateBonus()
    {
        return rand(5, 25) / 100;
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
