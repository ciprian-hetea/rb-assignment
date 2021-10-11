<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReportIndexRequest;
use App\Models\Transaction;

class ReportController extends Controller
{
    /**
     * @param ReportIndexRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(ReportIndexRequest $request)
    {
        $validated = $request->validated();
        $data = Transaction::generateReport($validated);
        return response()->json($data);
    }
}
