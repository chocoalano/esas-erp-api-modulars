<?php

namespace App\HrisModule\Controllers;

use App\Console\Support\ExcelExport;
use App\HrisModule\Requests\Permit\PermitRequest;
use App\HrisModule\Requests\Permit\PermitIndexRequest;
use App\HrisModule\Requests\Permit\PermitFileRequest;
use App\HrisModule\Services\PermitService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;

class PermitController extends BaseController
{
    protected $service;
    protected $excel;

    public function __construct(PermitService $service, ExcelExport $excel)
    {
        $this->service = $service;
        $this->excel = $excel;

        $permissions = [
            'index' => 'view_permits|view_any_permits',
            'show' => 'view_permits|view_any_permits',
            'store' => 'create_permits',
            'update' => 'update_permits',
            'destroy' => 'delete_permits|delete_any_permits',
            'delete' => 'forcedelete_permits|forcedelete_any_permits',
            'forceDelete' => 'forcedelete_permits|forcedelete_any_permits',
            'restore' => 'restore_permits',
            'export' => 'export_permits',
            'import' => 'import_permits',
        ];

        foreach ($permissions as $method => $permission) {
            $this->middleware("permission:{$permission},sanctum", ['only' => [$method]]);
        }
    }

    public function index(PermitIndexRequest $request)
    {
        $validated = $request->validated();

        return response()->json($this->service->paginate(
            $validated['page'],
            $validated['limit'],
            $validated['search'] ?? [],
            $validated['sortBy'] ?? []
        ));
    }
    public function list_type(PermitIndexRequest $request, $typeId)
    {
        $validated = $request->validated();

        return response()->json($this->service->paginateListType(
            $typeId,
            $validated['page'],
            $validated['limit']
        ));
    }

    public function create(Request $request)
    {
        $input = $request->validate([
            'companyId' => 'nullable|exists:companies,id',
            'deptId' => 'nullable|exists:departements,id',
            'userId' => 'nullable|exists:users,id',
            'typeId' => 'nullable|exists:permit_types,id',
            'scheduleId' => 'nullable|exists:user_timework_schedules,id',
        ]);
        return response()->json([
            'form' => $this->service->form(
                $input['companyId'] ?? null,
                $input['deptId'] ?? null,
                $input['userId'] ?? null,
                $input['typeId'] ?? null,
                $input['scheduleId'] ?? null
            )
        ]);
    }

    public function store(PermitRequest $request)
    {
        $input = $request->validated();
        $file = $request->file('file');
        return response()->json($this->service->create($input, $file));
    }

    public function show($id)
    {
        return response()->json($this->service->find($id));
    }

    public function edit(Request $request, $id)
    {
        $input = $request->validate([
            'companyId' => 'nullable|exists:companies,id',
            'deptId' => 'nullable|exists:departements,id',
            'userId' => 'nullable|exists:users,id',
            'typeId' => 'nullable|exists:permit_types,id',
            'scheduleId' => 'nullable|exists:user_timework_schedules,id',
        ]);
        return response()->json([
            'data' => $this->service->find($id),
            'form' => $this->service->form(
                $input['companyId'] ?? null,
                $input['deptId'] ?? null,
                $input['userId'] ?? null,
                $input['typeId'] ?? null,
                $input['scheduleId'] ?? null
            )
        ]);
    }

    public function update(PermitRequest $request, $id)
    {
        $input = $request->validated();
        $file = $request->file('file');
        return response()->json($this->service->update($id, $input, $file));
    }
    public function approval(Request $request, $id)
    {
        $input = $request->validate([
            'user_approve' => 'required|in:y,w,n',
            'notes' => 'required_if:user_approve,n|nullable|string|max:100',
        ]);
        $proses = $this->service->approve($id, Auth::id(), $input);
        return response()->json($proses, !is_null($proses) ? 200 : 500);
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

    public function restore($id)
    {
        return response()->json($this->service->restore($id));
    }

    public function forceDelete($id)
    {
        $result = null;
        if (is_int($id)) {
            $result = $this->service->forceDelete($id);
        } else {
            $arrIds = explode(',', $id);
            $arrIds = array_filter($arrIds, 'is_numeric');
            $arrIds = array_map('intval', $arrIds);
            if (!empty($arrIds)) {
                $deleteResults = [];
                foreach ($arrIds as $singleId) {
                    $deleteResults[$singleId] = $this->service->forceDelete($singleId);
                }
                $result = $deleteResults;
            } else {
                return response()->json(['message' => 'No valid IDs provided for deletion.'], 400);
            }
        }
        return response()->json($result);
    }

    public function deleted(PermitIndexRequest $request)
    {
        $validated = $request->validated();

        return response()->json($this->service->paginateTrashed(
            $validated['page'],
            $validated['limit'],
            $validated['search'] ?? [],
            $validated['sortBy'] ?? []
        ));
    }

    public function print(PermitFileRequest $request)
    {
        $validated = $request->validated();

        $data = $this->service->export(
            $validated['name'] ?? null,
            $validated['createdAt'] ?? null,
            $validated['updatedAt'] ?? null,
            $validated['startRange'] ?? null,
            $validated['endRange'] ?? null
        );

        $pdf = Pdf::loadView('pdf.permit', ['data' => $data]);
        $filename = 'Permit-' . now()->format('YmdHis') . '.pdf';

        return response($pdf->output(), 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }

    public function xlsx(PermitFileRequest $request)
    {
        $validated = $request->validated();
        $data = $this->service->export(
            $validated['name'] ?? null,
            $validated['createdAt'] ?? null,
            $validated['updatedAt'] ?? null,
            $validated['startRange'] ?? null,
            $validated['endRange'] ?? null
        );

        $headers = [
            'No.',
            'Permit Numbers',
            'User Name',
            'Permit Type',
            'Work Schedule Date',
            'Time In Adjust',
            'Time Out Adjust',
            'Current Shift ID',
            'Adjust Shift ID',
            'Start Date',
            'End Date',
            'Start Time',
            'End Time',
            'Notes',
            'File',
            'Created At',
            'Updated At',
            'Deleted At'
        ];
        $dataFormatter = function ($val, $index) {
            return [
                $index + 1,
                $val->permit_numbers,
                ExcelExport::getNestedValue($val, 'user.name'),
                ExcelExport::getNestedValue($val, 'permitType.type'),
                ExcelExport::getNestedValue($val, 'userTimeworkSchedule.date'),
                $val->timein_adjust,
                $val->timeout_adjust,
                $val->current_shift_id,
                $val->adjust_shift_id,
                ExcelExport::formatCarbonDate($val->start_date, 'Y-m-d'),
                ExcelExport::formatCarbonDate($val->end_date, 'Y-m-d'),
                ExcelExport::formatCarbonDate($val->start_time, 'H:i:s'),
                ExcelExport::formatCarbonDate($val->end_time, 'H:i:s'),
                $val->notes,
                $val->file,
                ExcelExport::formatCarbonDate($val->created_at),
                ExcelExport::formatCarbonDate($val->updated_at),
                ExcelExport::formatCarbonDate($val->deleted_at),
            ];
        };
        return $this->excel->generateAndDownload(
            $data,
            $headers,
            $dataFormatter,
            'izin' // Prefix untuk nama file
        );
    }

    public function import(Request $request)
    {
        return response()->json($this->service->import($request));
    }
}
