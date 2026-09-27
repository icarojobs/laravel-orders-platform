<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Reports\SalesReport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\SalesReportRequest;
use Illuminate\Http\JsonResponse;

class SalesReportController extends Controller
{
    public function __invoke(SalesReportRequest $request, SalesReport $report): JsonResponse
    {
        return response()->json(['data' => $report->for($request->from(), $request->to())]);
    }
}
