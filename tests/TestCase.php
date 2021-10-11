<?php

namespace Tests;

use App\Models\Customer;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Faker\Factory;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;
    protected $faker;

    public function __construct(?string $name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->faker = Factory::create();
    }

    public function createCustomer($options = [])
    {
        $firstName = isset($options["firstName"]) ? $options["firstName"] : $this->faker->firstName;
        $lastName = isset($options["lastName"]) ? $options["lastName"] : $this->faker->lastName;
        $countryCode = isset($options["countryCode"]) ? $options["countryCode"] : $this->faker->countryCode;
        $email = isset($options["email"]) ? $options["email"] : $this->faker->email;
        $gender = isset($options["gender"]) ? $options["gender"] : "M";

        return Customer::create([
            'first_name' => $firstName,
            'last_name' => $lastName,
            'country' => $countryCode,
            'email' => $email,
            'gender' => $gender
        ]);
    }
}
