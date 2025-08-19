<?php

namespace App\WorkOrdersModule\Controllers;

use App\WorkOrdersModule\Requests\WoIctMtc\WoIctMtcClearanceRequest;
use App\WorkOrdersModule\Requests\WoIctMtc\WoIctMtcRequest;
use App\WorkOrdersModule\Requests\WoIctMtc\WoIctMtcIndexRequest;
use App\WorkOrdersModule\Requests\WoIctMtc\WoIctMtcFileRequest;
use App\WorkOrdersModule\Requests\WoIctMtc\WoIctMtcServiceRequest;
use App\WorkOrdersModule\Requests\WoIctMtc\WoIctMtcSignoffRequest;
use App\WorkOrdersModule\Services\WoIctMtcService;
use Barryvdh\DomPDF\Facade\Pdf;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;

class WoIctMtcController extends BaseController
{
    protected WoIctMtcService $service;

    public function __construct(WoIctMtcService $service)
    {
        $this->service = $service;

        $permissions = [
            'index'       => 'view_work_orders|view_any_work_orders',
            'show'        => 'view_work_orders|view_any_work_orders',
            'store'       => 'create_work_orders',
            'update'      => 'update_work_orders',
            'destroy'     => 'delete_work_orders|delete_any_work_orders',
            'delete'      => 'forcedelete_work_orders|forcedelete_any_work_orders',
            'forceDelete' => 'forcedelete_work_orders|forcedelete_any_work_orders',
            'restore'     => 'restore_work_orders',
            'export'      => 'export_work_orders',
            'import'      => 'import_work_orders',
        ];

        foreach ($permissions as $method => $permission) {
            $this->middleware("permission:{$permission},sanctum", ['only' => [$method]]);
        }
    }

    public function index(WoIctMtcIndexRequest $request)
    {
        $validated = $request->validated();

        return response()->json($this->service->paginate(
            $validated['page'],
            $validated['limit'],
            $validated['search'] ?? [],
            $validated['sortBy'] ?? []
        ));
    }

    public function create()
    {
        return response()->json([
            'form' => $this->service->form()
        ]);
    }

    public function store(WoIctMtcRequest $request)
    {
        return response()->json($this->service->create($request->validated()));
    }

    public function show($id)
    {
        return response()->json($this->service->find($id));
    }

    public function edit($id)
    {
        return response()->json([
            'data' => $this->service->find($id),
            'form' => $this->service->form()
        ]);
    }

    public function update(WoIctMtcRequest $request, $id)
    {
        return response()->json($this->service->update($id, $request->validated()));
    }
    public function services(WoIctMtcServiceRequest $request, $id)
    {
        return response()->json($this->service->service($id, $request->validated()));
    }
    public function signoff(WoIctMtcSignoffRequest $request, $id)
    {
        return response()->json($this->service->signoff($id, $request->validated()));
    }
    public function clearance(WoIctMtcClearanceRequest $request, $id)
    {
        return response()->json($this->service->clearance($id, $request->validated()));
    }

    public function destroy($id)
    {
        return response()->json($this->service->delete($id));
    }

    public function restore($id)
    {
        return response()->json($this->service->restore($id));
    }

    public function forceDelete($id)
    {
        return response()->json($this->service->forceDelete($id));
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

    public function print(WoIctMtcFileRequest $request)
    {
        $validated = $request->validated();

        $data = $this->service->export(
            $validated['name'] ?? null,
            $validated['createdAt'] ?? null,
            $validated['updatedAt'] ?? null,
            $validated['startRange'] ?? null,
            $validated['endRange'] ?? null
        );

        $pdf = Pdf::loadView('pdf.WoIctMtc', ['data' => $data]);
        $filename = 'WoIctMtc-' . now()->format('YmdHis') . '.pdf';

        return response($pdf->output(), 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }

    public function xlsx(WoIctMtcFileRequest $request)
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

        $fileName = 'data_WoIctMtc_' . now()->format('Ymd_His') . '.xlsx';

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
