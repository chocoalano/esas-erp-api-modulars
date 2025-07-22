<?php

namespace App\DashboardModule\Controllers;

use App\DashboardModule\Requests\Hris\HrisIndexRequest;
use App\DashboardModule\Services\HrisService;
use App\GeneralModule\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class HrisController extends BaseController
{
    protected HrisService $service;

    public function __construct(HrisService $service)
    {
        $this->service = $service;

        $permissions = [
            'index' => 'view_users|view_any_users',
            'show' => 'view_users|view_any_users'
        ];

        foreach ($permissions as $method => $permission) {
            $this->middleware("permission:{$permission},sanctum", ['only' => [$method]]);
        }
    }

    public function index(HrisIndexRequest $request)
    {
        $validated = $request->validated();

        // Correctly parse the date strings into Carbon instances
        $companyId = $validated['company_id'] ?? Auth::user()->company_id;
        $startDate = isset($validated['start']) ?? null;
        $endDate = isset($validated['end']) ?? null;

        return response()->json($this->service->index(
            $companyId,
            $startDate,
            $endDate
        ));
    }
    public function company()
    {
        return response()->json(Company::all());
    }
    public function absen_chart(Request $request)
    {
        $validated = $request->validate([
            'company_id' => 'nullable|integer|exists:companies,id',
            'start' => 'nullable|date|before_or_equal:end',
            'end' => 'nullable|date|after_or_equal:start',
            'now' => [
                'nullable',
                Rule::in(['true', 'false', true, false, 1, 0, '1', '0']),
            ],
        ]);
        $companyId = $validated['company_id'] ?? Auth::user()->company_id;
        $startDate = $validated['start'] ?? null;
        $endDate = $validated['end'] ?? null;
        return response()->json($this->service->AttendanceChart(
            $companyId,
            $startDate,
            $endDate,
            $validated['now'] ?? null
        ));
    }
}
