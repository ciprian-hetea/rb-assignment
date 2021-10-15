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

            // Have each countries transactions be offset by 1 day
            Carbon::setTestNow(now()->subDay());

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

        // Back to today
        Carbon::setTestNow();

        // By default we do the report for the last 7 days
        $response = $this->json(
            'GET',
            action([ReportController::class, 'index'])
        );

        $response->assertStatus(200)->assertJson([]);;

        $day = 1;
        foreach ($countries as $country => $values) {
            $response->assertJsonFragment([
                "date" => now()->subDays($day)->format("Y-m-d"),
                "country" => $country,
                "Unique Customers" => 3,
                "No of Deposits" => $values["depositCount"],
                "Total Deposit Amount" => $values["depositTotal"],
                "No of Withdrawals" => $values["withdrawalCount"],
                "Total Withdrawal Amount" => $values["withdrawalTotal"],
            ]);
            $day++;
        }

        $response = $this->json(
            'GET',
            action([ReportController::class, 'index']),
            [
                'from' => now()->subDays(1)->format('Y-m-d'),
                'to' => now()->format('Y-m-d')
            ]
        );

        $response
            ->assertStatus(200)
            ->assertExactJson([[
                "date" => now()->subDay()->format("Y-m-d"),
                "country" => 'MT',
                "Unique Customers" => 3,
                "No of Deposits" => $countries["MT"]["depositCount"],
                "Total Deposit Amount" => $countries["MT"]["depositTotal"],
                "No of Withdrawals" => $countries["MT"]["withdrawalCount"],
                "Total Withdrawal Amount" => $countries["MT"]["withdrawalTotal"],
            ]]);
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
            [
                'from' => 'asdf',
                'to' => 'asdf'
            ]
        );

        $response->assertStatus(422);
    }
}
