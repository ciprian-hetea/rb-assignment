<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReportIndexRequest;
use App\Models\Transaction;

class ReportController extends Controller
{
    public function index(ReportIndexRequest $request)
    {
        $validated = $request->validated();
        $data = Transaction::generateReport($validated);
        return response()->json($data);
    }
}
