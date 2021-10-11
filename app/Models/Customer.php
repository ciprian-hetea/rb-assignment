<?php

namespace App\Models;

use Illuminate\Support\Facades\DB;

class Customer
{
    public $fields = array();
    protected static $table = 'customers';
    protected static $transactionsTable = 'transactions';

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

    public static function findByEmail($email)
    {
        $table = self::$table;
        $pdo = DB::connection()->getPdo();
        $query = $pdo->prepare("SELECT * FROM $table WHERE email = :email");

        try {
            $query->execute(['email' => $email]);
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
        if (!isset($this->fields['id'])) {
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
        return rand(5, 20) / 100;
    }

    public function addTransaction($amount)
    {
        $table = self::$table;
        $pdo = DB::connection()->getPdo();
        try {
            $pdo->beginTransaction();

            // Check if the transaction is valid
            $query = $pdo->prepare("
                SELECT balance, bonus_balance
                FROM $table
                WHERE id = :id
                "); // TODO add FOR UPDATE only on live
            $query->execute(['id' => $this->id]);
            $data = $query->fetch($pdo::FETCH_ASSOC);

            $futureBalance = $amount + $data['balance'];

            if ($futureBalance < 0) {
                throw new \BadMethodCallException(
                    "Customer has insufficient balance."
                );
            }

            // Calculate bonus
            $bonus_amount = 0;
            $transactionsTable = self::$transactionsTable;
            $query = $pdo->prepare(
                "SELECT count(id) as numberOfDeposits
                 FROM $transactionsTable
                 WHERE customer_id = :customer_id AND amount > 0"
            );
            $query->execute(['customer_id' => $this->id]);
            $data = $query->fetch($pdo::FETCH_ASSOC);
            $numberOfDeposits = $data["numberOfDeposits"];

            if (($numberOfDeposits + 1) % 3 === 0) {
                $bonus_amount = $amount * $this->bonus;
            }

            // Update balances
            $query = $pdo->prepare(
                "UPDATE {$table} SET
                balance = balance + :amount,
                bonus_balance = bonus_balance + :bonus_amount,
                updated_at = :updated_at
                WHERE id = :id"
            );

            $query->execute([
                "amount" => $amount,
                "bonus_amount" => $bonus_amount,
                "updated_at" => now(),
                "id" => $this->id
            ]);

            // Insert transaction
            $query = $pdo->prepare(
                "INSERT INTO $transactionsTable
                (amount, customer_id, created_at)
                VALUES
                (:amount, :customer_id, :created_at)"
            );

            $query->execute([
                "amount" => $amount,
                "customer_id" => $this->id,
                "created_at" => now()
            ]);

            // Commit all the transactions
            $pdo->commit();
        } catch (\Exception $e) {
            $pdo->rollBack();
            return false;
        }

        return true;
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
