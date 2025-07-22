<?php

namespace App\HrisModule\Controllers;

use App\HrisModule\Requests\JobPosition\JobPositionRequest;
use App\HrisModule\Requests\JobPosition\JobPositionIndexRequest;
use App\HrisModule\Requests\JobPosition\JobPositionFileRequest;
use App\HrisModule\Services\JobPositionService;
use Barryvdh\DomPDF\Facade\Pdf;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;

class JobPositionController extends BaseController
{
    protected JobPositionService $service;

    public function __construct(JobPositionService $service)
    {
        $this->service = $service;

        $permissions = [
            'index' => 'view_job_positions|view_any_job_positions',
            'show' => 'view_job_positions|view_any_job_positions',
            'store' => 'create_job_positions',
            'update' => 'update_job_positions',
            'destroy' => 'delete_job_positions|delete_any_job_positions',
            'delete' => 'forcedelete_job_positions|forcedelete_any_job_positions',
            'forceDelete' => 'forcedelete_job_positions|forcedelete_any_job_positions',
            'restore' => 'restore_job_positions',
            'export' => 'export_job_positions',
            'import' => 'import_job_positions',
        ];

        foreach ($permissions as $method => $permission) {
            $this->middleware("permission:{$permission},sanctum", ['only' => [$method]]);
        }
    }

    public function index(JobPositionIndexRequest $request)
    {
        $validated = $request->validated();

        return response()->json($this->service->paginate(
            $validated['page'],
            $validated['limit'],
            $validated['search'] ?? [],
            $validated['sortBy'] ?? []
        ));
    }

    public function create(Request $request)
    {
        $companyId = $request->input('company_id');
        return response()->json([
            'form' => $this->service->form($companyId)
        ]);
    }

    public function store(JobPositionRequest $request)
    {
        return response()->json($this->service->create($request->validated()));
    }

    public function show($id)
    {
        return response()->json($this->service->find($id));
    }

    public function edit(Request $request, $id)
    {
        $companyId = $request->input('company_id');
        return response()->json([
            'data' => $this->service->find($id),
            'form' => $this->service->form($companyId)
        ]);
    }

    public function update(JobPositionRequest $request, $id)
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

    public function deleted(JobPositionIndexRequest $request)
    {
        $validated = $request->validated();

        return response()->json($this->service->paginateTrashed(
            $validated['page'],
            $validated['limit'],
            $validated['search'] ?? [],
            $validated['sortBy'] ?? []
        ));
    }

    public function print(JobPositionFileRequest $request)
    {
        $validated = $request->validated();

        $data = $this->service->export(
            $validated['name'] ?? null,
            $validated['createdAt'] ?? null,
            $validated['updatedAt'] ?? null,
            $validated['startRange'] ?? null,
            $validated['endRange'] ?? null
        );

        $pdf = Pdf::loadView('pdf.job_position', ['data' => $data]);
        $filename = 'JobPosition-' . now()->format('YmdHis') . '.pdf';

        return response($pdf->output(), 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }

    public function xlsx(JobPositionFileRequest $request)
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

        $headers = [
            'No.',
            'Company Name',
            'Dept Name',
            'Position Name',
            'Created At',
            'Updated At',
        ];
        $sheet->fromArray($headers, null, 'A1');

        $row = 2;
        foreach ($data as $index => $val) {
            $sheet->fromArray([
                $index + 1,
                /**tulis value xlsx anda disini**/
                $val->company->name,
                $val->departement->name,
                $val->name,
                $val->created_at->format('Y-m-d H:i:s'),
                $val->updated_at->format('Y-m-d H:i:s')
            ], null, 'A' . $row);
            $row++;
        }

        $fileName = 'data_JobPosition_' . now()->format('Ymd_His') . '.xlsx';

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
