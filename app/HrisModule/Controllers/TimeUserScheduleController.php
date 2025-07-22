<?php

namespace App\HrisModule\Controllers;

use App\HrisModule\Requests\TimeUserSchedule\TimeUserScheduleRequest;
use App\HrisModule\Requests\TimeUserSchedule\TimeUserScheduleIndexRequest;
use App\HrisModule\Requests\TimeUserSchedule\TimeUserScheduleFileRequest;
use App\HrisModule\Services\TimeUserScheduleService;
use Barryvdh\DomPDF\Facade\Pdf;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;

class TimeUserScheduleController extends BaseController
{
    protected TimeUserScheduleService $service;

    public function __construct(TimeUserScheduleService $service)
    {
        $this->service = $service;

        $permissions = [
            'index'       => 'view_user_timework_schedules|view_any_user_timework_schedules',
            'show'        => 'view_user_timework_schedules|view_any_user_timework_schedules',
            'store'       => 'create_user_timework_schedules',
            'update'      => 'update_user_timework_schedules',
            'destroy'     => 'delete_user_timework_schedules|delete_any_user_timework_schedules',
            'export'      => 'export_user_timework_schedules',
            'import'      => 'import_user_timework_schedules',
        ];

        foreach ($permissions as $method => $permission) {
            $this->middleware("permission:{$permission},sanctum", ['only' => [$method]]);
        }
    }

    public function index(TimeUserScheduleIndexRequest $request)
    {
        $validated = $request->validated();

        return response()->json($this->service->paginate(
            $validated['page'],
            $validated['limit'],
            $validated['search'] ?? [],
            $validated['sortBy'] ?? []
        ));
    }

    public function filter_paginate(Request $request)
    {
        $input = $request->validate([
            'company_id' => ['nullable', 'integer', 'exists:companies,id'],
            'dept_id' => ['nullable', 'integer', 'exists:departements,id'],
        ]);
        // dd($input);
        return response()->json($this->service->get_table_filter_attribute(
            $input['company_id'] ?? null,
            $input['dept_id'] ?? null
        ));
    }

    public function create(Request $request)
    {
        $companyId = $request->input('company_id', null);
        $deptId = $request->input('departement_id', null);
        return response()->json([
            'form' => $this->service->form($companyId, $deptId)
        ]);
    }

    public function store(TimeUserScheduleRequest $request)
    {
        return response()->json($this->service->create($request->validated()));
    }

    public function show($id)
    {
        return response()->json($this->service->find($id));
    }

    public function edit(Request $request, $id)
    {
        $companyId = $request->input('company_id', null);
        $deptId = $request->input('departement_id', null);
        return response()->json([
            'data' => $this->service->find($id),
            'form' => $this->service->form($companyId, $deptId)
        ]);
    }

    public function update(TimeUserScheduleRequest $request, $id)
    {
        return response()->json($this->service->update($id, $request->validated()));
    }

    public function destroy($id)
    {
        $result = null;
        if (is_int($id)) {
            $result = $this->service->delete($id);
        } else {
            $arrIds = explode(',', $id);
            $arrIds = array_filter($arrIds, 'is_numeric');
            $arrIds = array_map('intval', $arrIds);
            if (!empty($arrIds)) {
                $deleteResults = [];
                foreach ($arrIds as $singleId) {
                    $deleteResults[$singleId] = $this->service->delete($singleId);
                }
                $result = $deleteResults;
            } else {
                return response()->json(['message' => 'No valid IDs provided for deletion.'], 400);
            }
        }
        return response()->json($result);
    }

    public function print(TimeUserScheduleFileRequest $request)
    {
        $validated = $request->validated();

        $data = $this->service->export(
            $validated['name'] ?? null,
            $validated['createdAt'] ?? null,
            $validated['updatedAt'] ?? null,
            $validated['startRange'] ?? null,
            $validated['endRange'] ?? null
        );

        $pdf = Pdf::loadView('pdf.TimeUserSchedule', ['data' => $data]);
        $filename = 'TimeUserSchedule-' . now()->format('YmdHis') . '.pdf';

        return response($pdf->output(), 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }

    public function xlsx(TimeUserScheduleFileRequest $request)
    {
        $validated = $request->validated();

        $data = $this->service->export(
            $validated['name'] ?? null,
            $validated['createdAt'] ?? null,
            $validated['updatedAt'] ?? null,
            $validated['startRange'] ?? null,
            $validated['endRange'] ?? null
        );

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $headers = [/**tulis header xlsx anda disini**/];
        $sheet->fromArray($headers, null, 'A1');

        $row = 2;
        foreach ($data as $index => $val) {
            $sheet->fromArray([
                $index + 1,
                /**tulis value xlsx anda disini**/
                //$val->created_at->format('Y-m-d H:i:s'),
                //$val->updated_at->format('Y-m-d H:i:s')
            ], null, 'A' . $row);
            $row++;
        }

        $fileName = 'data_TimeUserSchedule_' . now()->format('Ymd_His') . '.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    public function import(Request $request)
    {
        return response()->json($this->service->import($request));
    }
}
