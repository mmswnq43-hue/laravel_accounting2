<?php

declare(strict_types=1);

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\Company;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

abstract class ReportController extends Controller
{
    protected function company(Request $request): Company
    {
        return $request->attributes->get('company');
    }

    /**
     * Returns [Carbon $from, Carbon $to] from request inputs.
     * Defaults: from = start of current year, to = end of today.
     */
    protected function resolveDateRange(Request $request): array
    {
        $from = $request->filled('from_date')
            ? Carbon::parse($request->from_date)->startOfDay()
            : now()->startOfYear();

        $to = $request->filled('to_date')
            ? Carbon::parse($request->to_date)->endOfDay()
            : now()->endOfDay();

        return [$from, $to];
    }

    /**
     * Human-readable date range label for exports and headers.
     */
    protected function dateRangeLabel(Carbon $from, Carbon $to): string
    {
        return 'من ' . $from->format('Y/m/d') . ' إلى ' . $to->format('Y/m/d');
    }

    /**
     * Abort with 403 if a model does not belong to the current company.
     * Use this when report routes accept a model ID parameter.
     */
    protected function authorizeCompany(Model $model, Company $company): void
    {
        abort_unless((int) $model->company_id === $company->id, 403);
    }
}
