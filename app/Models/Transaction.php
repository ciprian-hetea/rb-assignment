<?php

namespace App\Models;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class Transaction
{
    protected static $table = 'transactions';

    /**
     * @param array $data
     * @return false|array
     */
    public static function generateReport($data = []) {
        if (!isset($data['period'])) {
            $period = '7 days';
        } else {
            $period = $data['period'];
        }

        $date = Carbon::parse("{$period} ago");
        $pdo = DB::connection()->getPdo();
        $query = $pdo->prepare(
            "SELECT
                DATE_FORMAT(transactions.created_at, '%Y-%m-%d') AS 'date',
                customers.country AS 'country',
                COUNT(DISTINCT transactions.customer_id) AS 'Unique Customers',
                SUM(CASE WHEN transactions.amount > 0 THEN 1 ELSE 0 END) AS 'No of Deposits',
                SUM(CASE WHEN transactions.amount > 0 THEN transactions.amount ELSE 0 END) AS 'Total Deposit Amount',
                SUM(CASE WHEN transactions.amount < 0 THEN 1 ELSE 0 END) AS 'No of Withdrawals',
                SUM(CASE WHEN transactions.amount < 0 THEN transactions.amount ELSE 0 END) AS 'Total Withdrawal Amount'
            FROM transactions
            INNER JOIN customers
            ON transactions.customer_id = customers.id
            WHERE transactions.created_at > :date
            GROUP BY date, country"
        );

        try {
            $query->execute([
                'date' => $date
            ]);
        } catch (\Exception $e) {
            return false;
        }

        $data = $query->fetchAll($pdo::FETCH_ASSOC);

        return $data;
    }
}
