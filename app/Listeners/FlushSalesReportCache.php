<?php

namespace App\Listeners;

use App\Domain\Reports\SalesReport;

class FlushSalesReportCache
{
    public function __construct(private readonly SalesReport $report) {}

    public function handle(): void
    {
        $this->report->flush();
    }
}
