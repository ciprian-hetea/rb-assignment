<?php

namespace App\Models;

use Illuminate\Support\Facades\DB;

class Customer extends BaseModel
{
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
        parent::__construct($data);
    }

    public static function create($data)
    {
        $table = self::$table;
        $pdo = DB::connection()->getPdo();
        $bonus = self::generateBonus();
        $query = $pdo->prepare("
            INSERT INTO $table
                (first_name, last_name, country, email, gender, bonus, created_at, updated_at)
            VALUES
                (:first_name, :last_name, :country, :email, :gender, :bonus, :created_at, :updated_at)
        ");

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

    /**
     * @param $id
     * @return Customer|null
     */
    public static function find($id)
    {
        $table = self::$table;
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

        return new self($data);
    }

    /**
     * @param $email
     * @return Customer|null
     */
    public static function findByEmail($email)
    {
        $table = self::$table;
        $pdo = DB::connection()->getPdo();
        $query = $pdo->prepare("SELECT * FROM $table WHERE email = :email");

        try {
            $query->execute(['email' => $email]);
        } catch (\Exception $e) {
            return null;
        }

        $data = $query->fetch($pdo::FETCH_ASSOC);

        if ($data === false) {
            return null;
        }

        return new self($data);
    }

    /**
     * @param array $data
     * @return bool
     */
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

        $query = $this->pdo->prepare($queryString);
        $updatedFields["id"] = $this->id;
        $updatedFields["updated_at"] = now();

        try {
            $query->execute($updatedFields);
        } catch (\Exception $e) {
            return false;
        }

        return true;
    }

    /**
     * @return bool
     */
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

    /**
     * @return float|int
     */
    public static function generateBonus()
    {
        return rand(5, 20) / 100;
    }

    /**
     * @param $amount
     * @return bool
     */
    public function addTransaction($amount)
    {
        try {
            $this->pdo->beginTransaction();
            $this->checkAvailableBalance($amount);
            $bonus_amount = $this->calculateBonus($amount);
            $this->updateBalances($amount, $bonus_amount);
            Transaction::create([
                'customer_id' => $this->id,
                'amount' => $amount
            ]);
            $this->pdo->commit();
        } catch (\BadMethodCallException $e) {
            throw new \BadMethodCallException(
                "Customer has insufficient balance."
            );
        } catch (\Exception $e) {
            $this->pdo->rollBack();
            return false;
        }

        return true;
    }

    /**
     * @param $amount
     */
    public function checkAvailableBalance($amount)
    {
        $table = self::$table;
        $query = $this->pdo->prepare("
                SELECT balance, bonus_balance
                FROM $table
                WHERE id = :id
                FOR UPDATE
        ");
        $query->execute(['id' => $this->id]);
        $data = $query->fetch($this->pdo::FETCH_ASSOC);

        $futureBalance = $amount + $data['balance'];

        if ($futureBalance < 0) {
            throw new \BadMethodCallException(
                "Customer has insufficient balance."
            );
        }
    }

    /**
     * @param $amount
     * @return float|int
     */
    public function calculateBonus($amount)
    {
        $bonus_amount = 0;
        if (Transaction::isEveryThirdDeposit($this->id)) {
            $bonus_amount = $amount * $this->bonus;
        }
        return $bonus_amount;
    }

    /**
     * @param $amount
     * @param $bonus_amount
     */
    public function updateBalances($amount, $bonus_amount)
    {
        $table = self::$table;
        $query = $this->pdo->prepare("
            UPDATE {$table} SET
                balance = balance + :amount,
                bonus_balance = bonus_balance + :bonus_amount,
                updated_at = :updated_at
            WHERE id = :id
        ");

        $query->execute([
            "amount" => $amount,
            "bonus_amount" => $bonus_amount,
            "updated_at" => now(),
            "id" => $this->id
        ]);
    }
}
