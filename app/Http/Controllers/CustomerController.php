<?php

namespace App\Http\Controllers;

use App\Http\Requests\CustomerCreateRequest;
use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function create(CustomerCreateRequest $request)
    {
        $validated = $request->validated();
        $customer = Customer::create($validated);

        if ($customer === false) {
            return response(
                [],
                400
            );
        }

        return response($customer);
    }

    public function update($id, Request $request)
    {
        // TODO validation
        $customer = Customer::find($id);
        if ($customer === null) {
            return response(
                [],
                404
            );
        }

        $result = $customer->update($request->all());

        if ($result === false) {
            return response(
                [],
                400
            );
        }

        $updatedCustomer = Customer::find($id);
        return response($updatedCustomer);
    }
}
