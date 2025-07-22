<?php

namespace App\HrisModule\Controllers;

use App\HrisModule\Requests\TimeWorke\TimeWorkeRequest;
use App\HrisModule\Requests\TimeWorke\TimeWorkeIndexRequest;
use App\HrisModule\Requests\TimeWorke\TimeWorkeFileRequest;
use App\HrisModule\Services\TimeWorkeService;
use Barryvdh\DomPDF\Facade\Pdf;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;

class TimeWorkeController extends BaseController
{
    protected TimeWorkeService $service;

    public function __construct(TimeWorkeService $service)
    {
        $this->service = $service;

        $permissions = [
            'index' => 'view_time_workes|view_any_time_workes',
            'show' => 'view_time_workes|view_any_time_workes',
            'store' => 'create_time_workes',
            'update' => 'update_time_workes',
            'destroy' => 'delete_time_workes|delete_any_time_workes',
            'delete' => 'forcedelete_time_workes|forcedelete_any_time_workes',
            'forceDelete' => 'forcedelete_time_workes|forcedelete_any_time_workes',
            'restore' => 'restore_time_workes',
            'export' => 'export_time_workes',
            'import' => 'import_time_workes',
        ];

        foreach ($permissions as $method => $permission) {
            $this->middleware("permission:{$permission},sanctum", ['only' => [$method]]);
        }
    }

    public function index(TimeWorkeIndexRequest $request)
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
        $companyId = $request->input('company_id', null);
        return response()->json([
            'form' => $this->service->form($companyId)
        ]);
    }

    public function store(TimeWorkeRequest $request)
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
        return response()->json([
            'data' => $this->service->find($id),
            'form' => $this->service->form($companyId)
        ]);
    }

    public function update(TimeWorkeRequest $request, $id)
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

    public function deleted(Request $request)
    {
        $validated = $request->validate([
            'page' => 'required|integer|min:1',
            'limit' => 'required|integer|min:1|max:100',
            'search' => 'nullable|array',
            'search.nip' => ['nullable', 'string', 'max:50'],
        ]);

        return response()->json($this->service->paginateTrashed(
            $validated['page'],
            $validated['limit'],
            $validated['search']
        ));
    }

    public function print(TimeWorkeFileRequest $request)
    {
        $validated = $request->validated();

        $data = $this->service->export(
            $validated['name'] ?? null,
            $validated['createdAt'] ?? null,
            $validated['updatedAt'] ?? null,
            $validated['startRange'] ?? null,
            $validated['endRange'] ?? null
        );

        $pdf = Pdf::loadView('pdf.time_worke', ['data' => $data]);
        $filename = 'TimeWorke-' . now()->format('YmdHis') . '.pdf';

        return response($pdf->output(), 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }

    public function xlsx(TimeWorkeFileRequest $request)
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
            "No.",
            "Company Name",
            "Dept Name",
            "Time In",
            "Time Out",
            "Created At",
            "Updated At"
        ];
        $sheet->fromArray($headers, null, 'A1');

        $row = 2;
        foreach ($data as $index => $val) {
            $sheet->fromArray([
                $index + 1,
                /**tulis value xlsx anda disini**/
                $val->company->name ?? '-',
                $val->departement->name ?? '-',
                \Carbon\Carbon::parse($val->in)->format('H:i') ?? '-',
                \Carbon\Carbon::parse($val->out)->format('H:i') ?? '-',
                $val->created_at->format('Y-m-d H:i:s'),
                $val->updated_at->format('Y-m-d H:i:s')
            ], null, 'A' . $row);
            $row++;
        }

        $fileName = 'data_TimeWorke_' . now()->format('Ymd_His') . '.xlsx';

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
