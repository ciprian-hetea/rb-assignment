<?php

namespace Tests;

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
}
