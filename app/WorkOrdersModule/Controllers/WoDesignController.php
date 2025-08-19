<?php

namespace App\WorkOrdersModule\Controllers;

use App\WorkOrdersModule\Enums\DesignApprovalStatus;
use App\WorkOrdersModule\Enums\DesignRequestStatus;
use App\WorkOrdersModule\Requests\WoDesign\WoApprovalDesignRequest;
use App\WorkOrdersModule\Requests\WoDesign\WoDesignRequest;
use App\WorkOrdersModule\Requests\WoDesign\WoDesignIndexRequest;
use App\WorkOrdersModule\Requests\WoDesign\WoDesignFileRequest;
use App\WorkOrdersModule\Resources\DesignRequestResource;
use App\WorkOrdersModule\Services\WoDesignService;
use Barryvdh\DomPDF\Facade\Pdf;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;

class WoDesignController extends BaseController
{
    protected WoDesignService $service;

    public function __construct(WoDesignService $service)
    {
        $this->service = $service;

        $permissions = [
            'index' => 'view_design_requests|view_any_design_requests',
            'show' => 'view_design_requests|view_any_design_requests',
            'store' => 'create_design_requests',
            'update' => 'update_design_requests',
            'destroy' => 'delete_design_requests|delete_any_design_requests',
            'delete' => 'forcedelete_design_requests|forcedelete_any_design_requests',
            'forceDelete' => 'forcedelete_design_requests|forcedelete_any_design_requests',
            'restore' => 'restore_design_requests',
            'export' => 'export_design_requests',
            'import' => 'import_design_requests',
        ];

        foreach ($permissions as $method => $permission) {
            $this->middleware("permission:{$permission},sanctum", ['only' => [$method]]);
        }
    }

    public function index(WoDesignIndexRequest $request)
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

    public function store(WoDesignRequest $request)
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

    public function update(WoDesignRequest $request, $id)
    {
        return response()->json($this->service->update($id, $request->validated()));
    }
    public function approval(WoApprovalDesignRequest $request, $id)
    {
        $designRequest = $this->service->approval($id, $request->validated());
        return response()->json($designRequest);
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

    public function print(WoDesignFileRequest $request)
    {
        $validated = $request->validated();

        $data = $this->service->export(
            $validated['name'] ?? null,
            $validated['createdAt'] ?? null,
            $validated['updatedAt'] ?? null,
            $validated['startRange'] ?? null,
            $validated['endRange'] ?? null
        );

        $pdf = Pdf::loadView('pdf.WoDesign', ['data' => $data]);
        $filename = 'WoDesign-' . now()->format('YmdHis') . '.pdf';

        return response($pdf->output(), 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }

    public function xlsx(WoDesignFileRequest $request)
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

        $fileName = 'data_WoDesign_' . now()->format('Ymd_His') . '.xlsx';

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
