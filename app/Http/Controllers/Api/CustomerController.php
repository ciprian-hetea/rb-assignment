<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\CustomerCreateRequest;
use App\Http\Requests\CustomerUpdateRequest;
use App\Models\Customer;
use App\Http\Controllers\Controller;

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
        return response()->json($customer->fields);
    }

    public function update($id, CustomerUpdateRequest $request)
    {
        $customer = Customer::find($id);
        if ($customer === null) {
            return response(
                [],
                404
            );
        }

        $validated = $request->validated();
        $result = $customer->update($validated);

        if ($result === false) {
            return response(
                [],
                400
            );
        }

        $updatedCustomer = Customer::find($id);
        return response()->json($updatedCustomer->fields);
    }
}
