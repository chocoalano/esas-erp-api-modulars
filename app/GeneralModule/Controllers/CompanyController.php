<?php

namespace App\GeneralModule\Controllers;

use App\GeneralModule\Requests\Company\CompanyRequest;
use App\GeneralModule\Requests\Company\CompanyIndexRequest;
use App\GeneralModule\Requests\Company\CompanyFileRequest;
use App\GeneralModule\Services\CompanyService;
use Barryvdh\DomPDF\Facade\Pdf;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;

class CompanyController extends BaseController
{
    protected CompanyService $service;

    public function __construct(CompanyService $service)
    {
        $this->service = $service;

        $permissions = [
            'index'       => 'view_companies|view_any_companies',
            'show'        => 'view_companies|view_any_companies',
            'store'       => 'create_companies',
            'update'      => 'update_companies',
            'destroy'     => 'delete_companies|delete_any_companies',
            'delete'      => 'forcedelete_companies|forcedelete_any_companies',
            'forceDelete' => 'forcedelete_companies|forcedelete_any_companies',
            'restore'     => 'restore_companies',
            'export'      => 'export_companies',
            'import'      => 'import_companies',
        ];

        foreach ($permissions as $method => $permission) {
            $this->middleware("permission:{$permission},sanctum", ['only' => [$method]]);
        }
    }

    public function index(CompanyIndexRequest $request)
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

    public function store(CompanyRequest $request)
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

    public function update(CompanyRequest $request, $id)
    {
        return response()->json($this->service->update($id, $request->validated()));
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

    public function deleted(CompanyIndexRequest $request)
    {
        $validated = $request->validated();
        $page = $validated['page'];
        $limit = $validated['limit'];
        $search = $validated['search'] ?? [];
        $sortBy = $validated['sortBy'] ?? [];

        return response()->json($this->service->paginateTrashed(
            $page,
            $limit,
            $search,
            $sortBy,
        ));
    }

    public function print(CompanyFileRequest $request)
    {
        $validated = $request->validated();

        $data = $this->service->export(
            $validated['name'] ?? null,
            $validated['createdAt'] ?? null,
            $validated['updatedAt'] ?? null,
            $validated['startRange'] ?? null,
            $validated['endRange'] ?? null
        );

        $pdf = Pdf::loadView('pdf.company', ['data' => $data]);
        $filename = 'Company-' . now()->format('YmdHis') . '.pdf';

        return response($pdf->output(), 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }

    public function xlsx(CompanyFileRequest $request)
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

        $headers = ['No.', 'Name', 'Latitude', 'Longitude', 'Address', 'Created at', 'Updated at'];
        $sheet->fromArray($headers, null, 'A1');

        $row = 2;
        foreach ($data as $index => $val) {
            $sheet->fromArray([
                $index + 1,
                /**tulis value xlsx anda disini**/
                $val->name,
                $val->latitude,
                $val->longitude,
                $val->full_address,
                $val->created_at->format('Y-m-d H:i:s'),
                $val->updated_at->format('Y-m-d H:i:s')
            ], null, 'A' . $row);
            $row++;
        }

        $fileName = 'data_Company_' . now()->format('Ymd_His') . '.xlsx';

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
