<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\AddTransactionRequest;
use App\Http\Requests\CustomerCreateRequest;
use App\Http\Requests\CustomerUpdateRequest;
use App\Models\Customer;
use App\Http\Controllers\Controller;

class CustomerController extends Controller
{
    /**
     * @param CustomerCreateRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function create(CustomerCreateRequest $request)
    {
        $validated = $request->validated();
        $customer = Customer::create($validated);

        if ($customer === false) {
            return response()->json(
                [],
                400
            );
        }
        return response()->json($customer->fields);
    }

    /**
     * @param $id
     * @param CustomerUpdateRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function update($id, CustomerUpdateRequest $request)
    {
        $customer = Customer::find($id);
        if ($customer === null) {
            return response()->json(
                [],
                404
            );
        }

        $validated = $request->validated();
        $result = $customer->update($validated);

        if ($result === false) {
            return response()->json(
                [],
                400
            );
        }

        $updatedCustomer = Customer::find($id);
        return response()->json($updatedCustomer->fields);
    }

    /**
     * @param $id
     * @param AddTransactionRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function addTransaction($id, AddTransactionRequest $request)
    {
        $customer = Customer::find($id);
        if ($customer === null) {
            return response()->json(
                [],
                404
            );
        }

        $validated = $request->validated();

        try {
            $result = $customer->addTransaction($validated["amount"]);
        } catch (\Exception $e) {
            return response()->json(
                ['message' => $e->getMessage()],
                409
            );
        }

        if ($result === false) {
            return response()->json(
                [],
                409
            );
        }

        return response()->json(
            [],
            200
        );
    }
}
