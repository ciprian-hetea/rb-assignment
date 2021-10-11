<?php

namespace Tests\Feature;

use App\Http\Controllers\API\ReportController;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ReportTest extends TestCase
{
    public function refreshTransactions()
    {
        DB::table('transactions')->truncate();
    }

    /**
     * Report generates correct results.
     *
     * @return void
     */
    public function testReportGeneratesCorrectResults()
    {
        $this->refreshTransactions();
        // generate 3 deposits and 3 withdrawals for 3 customers for 3 countries
        $countries = [
            "MT" => [
                "depositCount" => 0,
                "depositTotal" => 0,
                "withdrawalCount" => 0,
                "withdrawalTotal" => 0
            ],
            "RO" => [
                "depositCount" => 0,
                "depositTotal" => 0,
                "withdrawalCount" => 0,
                "withdrawalTotal" => 0
            ],
            "GB" => [
                "depositCount" => 0,
                "depositTotal" => 0,
                "withdrawalCount" => 0,
                "withdrawalTotal" => 0
            ]
        ];
        foreach ($countries as $country => $values) {
            for ($i = 0; $i < 3; $i++) {
                $customer = $this->createCustomer(["countryCode" => $country]);
                for ($j = 0; $j < 3; $j++) {
                    $deposit = $this->faker->numberBetween(1000, 10000);
                    $customer->addTransaction($deposit);
                    $countries[$country]["depositCount"]++;
                    $countries[$country]["depositTotal"] += $deposit;


                    $withdrawal = $this->faker->numberBetween(-1000, -100);
                    $customer->addTransaction($withdrawal);
                    $countries[$country]["withdrawalCount"]++;
                    $countries[$country]["withdrawalTotal"] += $withdrawal;
                }
            }
        }

        $response = $this->json(
            'GET',
            action([ReportController::class, 'index']),
            ['period' => '7 days']
        );

        $response->assertStatus(200)->assertJson([]);;

        foreach ($countries as $country => $values) {
            $response->assertJsonFragment([
                "date" => now()->format("Y-m-d"),
                "country" => $country,
                "Unique Customers" => 3,
                "No of Deposits" => strval($values["depositCount"]),
                "Total Deposit Amount" => $values["depositTotal"],
                "No of Withdrawals" => strval($values["withdrawalCount"]),
                "Total Withdrawal Amount" => $values["withdrawalTotal"],
            ]);
        }

        // In the future the results shouldn't be visible
        Carbon::setTestNow(now()->addDays(3));

        $response = $this->json(
            'GET',
            action([ReportController::class, 'index']),
            ['period' => '2 days']
        );

        $response
            ->assertStatus(200)
            ->assertJson([]);
    }

    /**
     * Report validates date.
     *
     * @return void
     */
    public function testReportValidatesDate()
    {
        $response = $this->json(
            'GET',
            action([ReportController::class, 'index']),
            ['period' => 'asdf']
        );

        $response->assertStatus(422);
    }
}
