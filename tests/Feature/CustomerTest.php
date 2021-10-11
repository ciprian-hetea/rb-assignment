<?php

namespace Tests\Feature;

use App\Http\Controllers\API\CustomerController;
use App\Models\Customer;
use Tests\TestCase;

class CustomerTest extends TestCase
{
    /**
     * API call can create customer.
     *
     * @return void
     */
    public function testApiCanCreateCustomer()
    {
        $firstName = $this->faker->firstName;
        $lastName = $this->faker->lastName;
        $countryCode = $this->faker->countryCode;
        $email = $this->faker->email;
        $gender = "M";

        $response = $this->postJson(
            action([CustomerController::class, 'create']),
            [
                'first_name' => $firstName,
                'last_name' => $lastName,
                'country' => $countryCode,
                'email' => $email,
                'gender' => $gender
            ]
        );

        $response
            ->assertStatus(200)
            ->assertJson([
                'first_name' => $firstName,
                'last_name' => $lastName,
                'country' => $countryCode,
                'email' => $email,
                'gender' => $gender
            ]);

        $customer = Customer::findByEmail($email);

        $this->assertEquals($firstName, $customer->first_name);
        $this->assertEquals($lastName, $customer->last_name);
        $this->assertEquals($countryCode, $customer->country);
        $this->assertEquals($email, $customer->email);
        $this->assertEquals($gender, $customer->gender);

        $this->assertLessThanOrEqual(0.25, $customer->bonus);
        $this->assertGreaterThanOrEqual(0.05, $customer->bonus);
    }

    /**
     * API returns error if not all fields are given on customer create.
     *
     * @return void
     */
    public function testApiReturnsErrorOnCustomerCreateWithoutAllFields()
    {
        $firstName = $this->faker->firstName;
        $lastName = $this->faker->lastName;
        $countryCode = $this->faker->countryCode;
        $email = $this->faker->email;
        $gender = "M";

        $response = $this->postJson(
            action([CustomerController::class, 'create']),
            [
                'last_name' => $lastName,
                'country' => $countryCode,
                'email' => $email,
                'gender' => $gender
            ]
        );

        $response->assertStatus(422);

        $response = $this->postJson(
            action([CustomerController::class, 'create']),
            [
                'first_name' => $firstName,
                'country' => $countryCode,
                'email' => $email,
                'gender' => $gender
            ]
        );

        $response->assertStatus(422);

        $response = $this->postJson(
            action([CustomerController::class, 'create']),
            [
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => $email,
                'gender' => $gender
            ]
        );

        $response->assertStatus(422);

        $response = $this->postJson(
            action([CustomerController::class, 'create']),
            [
                'first_name' => $firstName,
                'last_name' => $lastName,
                'country' => $countryCode,
                'gender' => $gender
            ]
        );

        $response->assertStatus(422);

        $response = $this->postJson(
            action([CustomerController::class, 'create']),
            [
                'first_name' => $firstName,
                'last_name' => $lastName,
                'country' => $countryCode,
                'email' => $email,
            ]
        );

        $response->assertStatus(422);
    }


    /**
     * API returns error if not all fields are not the correct type.
     *
     * @return void
     */
    public function testApiEnforcesValidationOnCustomerCreate()
    {
        $firstName = $this->faker->firstName;
        $lastName = $this->faker->lastName;
        $countryCode = $this->faker->countryCode;
        $gender = "M";

        $response = $this->postJson(
            action([CustomerController::class, 'create']),
            [
                'first_name' => $firstName,
                'last_name' => $lastName,
                'country' => $countryCode,
                'email' => $gender,
                'gender' => $gender
            ]
        );

        $response->assertStatus(422);
        // TODO test the rest of the validation rules
    }

    /**
     * API call can update a customer.
     *
     * @return void
     */
    public function testApiCanUpdateCustomer()
    {
        $firstName = $this->faker->firstName;
        $lastName = $this->faker->lastName;
        $countryCode = $this->faker->countryCode;
        $email = $this->faker->email;
        $gender = "M";

        $customer = Customer::create([
            'first_name' => $firstName,
            'last_name' => $lastName,
            'country' => $countryCode,
            'email' => $email,
            'gender' => $gender
        ]);

        $firstName2 = $this->faker->firstName;
        $lastName2 = $this->faker->lastName;
        $countryCode2 = $this->faker->countryCode;
        $email2 = $this->faker->email;
        $gender2 = "F";

        $response = $this->putJson(
            action([CustomerController::class, 'update'], $customer->id),
            [
                'first_name' => $firstName2,
                'last_name' => $lastName2,
                'country' => $countryCode2,
                'email' => $email2,
                'gender' => $gender2
            ]
        );

        $response
            ->assertStatus(200)
            ->assertJson([
                'first_name' => $firstName2,
                'last_name' => $lastName2,
                'country' => $countryCode2,
                'email' => $email2,
                'gender' => $gender2
            ]);

        $customer = Customer::find($customer->id);

        $this->assertEquals($firstName2, $customer->first_name);
        $this->assertEquals($lastName2, $customer->last_name);
        $this->assertEquals($countryCode2, $customer->country);
        $this->assertEquals($email2, $customer->email);
        $this->assertEquals($gender2, $customer->gender);

        // TODO test the validation rules
    }

    /**
     * API call can add a transaction.
     *
     * @return void
     */
    public function testApiCanAddTransactionForCustomer()
    {
        $customer = $this->createCustomer();
        $amount = $this->faker->numberBetween(1, 10000);

        $response = $this->postJson(
            action([CustomerController::class, 'addTransaction'], $customer->id),
            [
                'amount' => $amount
            ]
        );

        $response->assertStatus(200);
        $customer = Customer::find($customer->id);
        $this->assertEquals($amount, $customer->balance);
    }

    /**
     * Every third deposit will add a bonus to the customer.
     *
     * @return void
     */
    public function testApiCanAddBonusForCustomer()
    {
        $customer = $this->createCustomer();
        $amount = $this->faker->numberBetween(1, 10000);

        $response = $this->postJson(
            action([CustomerController::class, 'addTransaction'], $customer->id),
            [
                'amount' => $amount
            ]
        );

        $response->assertStatus(200);
        $customer = Customer::find($customer->id);
        $this->assertEquals($amount, $customer->balance);

        $amount = $this->faker->numberBetween(1, 10000);
        $balance = $customer->balance + $amount;

        $response = $this->postJson(
            action([CustomerController::class, 'addTransaction'], $customer->id),
            [
                'amount' => $amount
            ]
        );

        $response->assertStatus(200);
        $customer = Customer::find($customer->id);
        $this->assertEquals($balance, $customer->balance);

        $amount = $this->faker->numberBetween(1, 10000);
        $balance = $customer->balance + $amount;
        $bonusBalance = $customer->bonus * $amount;

        $response = $this->postJson(
            action([CustomerController::class, 'addTransaction'], $customer->id),
            [
                'amount' => $amount
            ]
        );

        $response->assertStatus(200);
        $customer = Customer::find($customer->id);
        $this->assertEquals($balance, $customer->balance);
        $this->assertEquals($bonusBalance, $customer->bonus_balance);
    }


    /**
     * Amont can be withdrawn.
     *
     * @return void
     */
    public function testWithdrawalWorks()
    {
        $customer = $this->createCustomer();
        $amount = $this->faker->numberBetween(1, 10000);

        $response = $this->postJson(
            action([CustomerController::class, 'addTransaction'], $customer->id),
            [
                'amount' => $amount
            ]
        );

        $response->assertStatus(200);
        $customer = Customer::find($customer->id);
        $this->assertEquals($amount, $customer->balance);

        $amount = -1 * $amount;

        $response = $this->postJson(
            action([CustomerController::class, 'addTransaction'], $customer->id),
            [
                'amount' => $amount
            ]
        );

        $response->assertStatus(200);
        $customer = Customer::find($customer->id);
        $this->assertEquals(0, $customer->balance);


        $amount = $this->faker->numberBetween(-10000, -1);

        $response = $this->postJson(
            action([CustomerController::class, 'addTransaction'], $customer->id),
            [
                'amount' => $amount
            ]
        );

        $response->assertStatus(409);
        $customer = Customer::find($customer->id);
        $this->assertEquals(0, $customer->balance);
    }
}
