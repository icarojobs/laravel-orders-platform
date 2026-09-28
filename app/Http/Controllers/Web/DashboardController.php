<?php

namespace App\Http\Controllers\Web;

use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Reports\SalesReport;
use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(SalesReport $report): Response
    {
        $labels = [];
        foreach (OrderStatus::cases() as $status) {
            $labels[$status->value] = $status->label();
        }

        return Inertia::render('dashboard', [
            'report' => $report->for(now()->subDays(29), now()),
            'statusLabels' => $labels,
        ]);
    }
}
