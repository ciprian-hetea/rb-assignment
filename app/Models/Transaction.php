<?php

namespace App\Models;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class Transaction extends BaseModel
{
    protected static $table = 'transactions';

    public function __construct($data)
    {
        parent::__construct($data);
    }

    /**
     * @param $id
     * @return self|null
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

    public static function create($data)
    {
        $table = self::$table;
        $pdo = DB::connection()->getPdo();
        $query = $pdo->prepare("
            INSERT INTO $table
                (customer_id, amount, created_at)
            VALUES
                (:customer_id, :amount, :created_at)
        ");

        try {
            $query->execute([
                'customer_id' => $data['customer_id'],
                'amount' => $data['amount'],
                'created_at' => now(),
            ]);
        } catch (\Exception $e) {
            return false;
        }

        $lastId = $pdo->lastInsertId();
        return self::find($lastId);
    }

    /**
     * @param array $data
     * @return false|array
     */
    public static function generateReport($data = []) {
        if (!isset($data['from']) || !isset($data['to'])) {
            $from = now()->subWeek()->format('Y-m-d');
            $to = now()->format('Y-m-d');
        } else {
            $from = $data['from'];
            $to = $data['to'];
        }

        $pdo = DB::connection()->getPdo();
        $query = $pdo->prepare("
            SELECT
                DATE_FORMAT(transactions.created_at, '%Y-%m-%d') AS 'date',
                customers.country AS 'country',
                COUNT(DISTINCT transactions.customer_id) AS 'Unique Customers',
                CAST(SUM(CASE WHEN transactions.amount > 0 THEN 1 ELSE 0 END) AS UNSIGNED) AS 'No of Deposits',
                SUM(CASE WHEN transactions.amount > 0 THEN transactions.amount ELSE 0 END) AS 'Total Deposit Amount',
                CAST(SUM(CASE WHEN transactions.amount < 0 THEN 1 ELSE 0 END) AS UNSIGNED) AS 'No of Withdrawals',
                SUM(CASE WHEN transactions.amount < 0 THEN transactions.amount ELSE 0 END) AS 'Total Withdrawal Amount'
            FROM transactions
            INNER JOIN customers
            ON transactions.customer_id = customers.id
            WHERE transactions.created_at >= :from AND transactions.created_at <= :to
            GROUP BY date, country
        ");

        try {
            $query->execute([
                'from' => $from,
                'to' => $to
            ]);
        } catch (\Exception $e) {
            return false;
        }

        $data = $query->fetchAll($pdo::FETCH_ASSOC);

        return $data;
    }

    /**
     * @param $customerID
     * @return bool
     */
    public static function isEveryThirdDeposit($customerID)
    {
        $table = self::$table;
        $pdo = DB::connection()->getPdo();
        $query = $pdo->prepare("
            SELECT count(id) as numberOfDeposits
             FROM $table
             WHERE customer_id = :customer_id AND amount > 0
        ");
        $query->execute(['customer_id' => $customerID]);
        $data = $query->fetch($pdo::FETCH_ASSOC);
        $numberOfDeposits = $data["numberOfDeposits"];

        if (($numberOfDeposits + 1) % 3 === 0) {
            return true;
        }

        return false;
    }
}
