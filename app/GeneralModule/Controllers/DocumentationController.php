<?php

namespace App\GeneralModule\Controllers;

use App\GeneralModule\Requests\Documentation\DocumentationPublicRequest;
use App\GeneralModule\Requests\Documentation\DocumentationRequest;
use App\GeneralModule\Requests\Documentation\DocumentationIndexRequest;
use App\GeneralModule\Requests\Documentation\DocumentationFileRequest;
use App\GeneralModule\Services\DocumentationService;
use Barryvdh\DomPDF\Facade\Pdf;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;

class DocumentationController extends BaseController
{
    protected DocumentationService $service;

    public function __construct(DocumentationService $service)
    {
        $this->service = $service;

        $permissions = [
            'index'       => 'view_documentations|view_any_documentations',
            'show'        => 'view_documentations|view_any_documentations',
            'store'       => 'create_documentations',
            'update'      => 'update_documentations',
            'destroy'     => 'delete_documentations|delete_any_documentations',
            'delete'      => 'forcedelete_documentations|forcedelete_any_documentations',
            'forceDelete' => 'forcedelete_documentations|forcedelete_any_documentations',
            'restore'     => 'restore_documentations',
            'export'      => 'export_documentations',
            'import'      => 'import_documentations',
        ];

        foreach ($permissions as $method => $permission) {
            $this->middleware("permission:{$permission},sanctum", ['only' => [$method]]);
        }
    }

    public function index(DocumentationIndexRequest $request)
    {
        $validated = $request->validated();

        return response()->json($this->service->paginate(
            $validated['page'],
            $validated['limit'],
            $validated['search'] ?? [],
            $validated['sortBy'] ?? []
        ));
    }
    public function public(DocumentationPublicRequest $request)
    {
        $validated = $request->validated();

        return response()->json($this->service->paginatePublic(
            $validated['page'] ?? 1,
            $validated['limit'] ?? 20,
            $validated['search'] ?? ''
        ));
    }

    public function create()
    {
        return response()->json([
            'form' => $this->service->form()
        ]);
    }

    public function store(DocumentationRequest $request)
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

    public function update(DocumentationRequest $request, $id)
    {
        return response()->json($this->service->update($id, $request->validated()));
    }

    public function destroy($id)
    {
        return response()->json($this->service->delete($id));
    }

    public function print(DocumentationFileRequest $request)
    {
        $validated = $request->validated();

        $data = $this->service->export(
            $validated['name'] ?? null,
            $validated['createdAt'] ?? null,
            $validated['updatedAt'] ?? null,
            $validated['startRange'] ?? null,
            $validated['endRange'] ?? null
        );

        $pdf = Pdf::loadView('pdf.documentation', ['data' => $data]);
        $filename = 'Documentation-' . now()->format('YmdHis') . '.pdf';

        return response($pdf->output(), 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }

    public function xlsx(DocumentationFileRequest $request)
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

        $fileName = 'data_Documentation_' . now()->format('Ymd_His') . '.xlsx';

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
