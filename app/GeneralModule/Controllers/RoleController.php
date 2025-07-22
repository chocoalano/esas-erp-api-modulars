<?php

namespace App\GeneralModule\Controllers;

use App\GeneralModule\Requests\Role\RoleRequest;
use App\GeneralModule\Requests\Role\RoleIndexRequest;
use App\GeneralModule\Requests\Role\RoleFileRequest;
use App\GeneralModule\Services\RoleService;
use Barryvdh\DomPDF\Facade\Pdf;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;

class RoleController extends BaseController
{
    protected RoleService $service;

    public function __construct(RoleService $service)
    {
        $this->service = $service;

        $permissions = [
            'index' => 'view_roles|view_any_roles',
            'show' => 'view_roles|view_any_roles',
            'create' => 'create_roles',
            'store' => 'create_roles',
            'edit' => 'update_roles',
            'update' => 'update_roles',
            'destroy' => 'delete_roles|delete_any_roles',
            'delete' => 'forcedelete_roles|forcedelete_any_roles',
            'forceDelete' => 'forcedelete_roles|forcedelete_any_roles',
            'restore' => 'restore_roles',
            'print' => 'export_roles',
            'xlsx' => 'export_roles',
            'import' => 'import_roles',
        ];

        foreach ($permissions as $method => $permission) {
            $this->middleware("permission:{$permission},sanctum", ['only' => [$method]]);
        }
    }

    public function index(RoleIndexRequest $request)
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
    public function store(RoleRequest $request)
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

    public function update(RoleRequest $request, $id)
    {
        return response()->json($this->service->update($id, $request->validated()));
    }

    public function destroy($id)
    {
        return response()->json($this->service->delete($id));
    }

    public function print(RoleFileRequest $request)
    {
        $validated = $request->validated();

        $data = $this->service->export(
            $validated['name'] ?? null,
            $validated['createdAt'] ?? null,
            $validated['updatedAt'] ?? null,
            $validated['startRange'] ?? null,
            $validated['endRange'] ?? null
        );

        $pdf = Pdf::loadView('pdf.role', ['data' => $data]);
        $filename = 'Role-' . now()->format('YmdHis') . '.pdf';

        return response($pdf->output(), 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }

    public function xlsx(RoleFileRequest $request)
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

        $headers = ['No','Name','Guard','Created at','Updated at'];
        $sheet->fromArray($headers, null, 'A1');

        $row = 2;
        foreach ($data as $index => $val) {
            $sheet->fromArray([
                $index + 1,
                /**tulis value xlsx anda disini**/
                $val->name,
                $val->guard_name,
                $val->created_at->format('Y-m-d H:i:s'),
                $val->updated_at->format('Y-m-d H:i:s')
            ], null, 'A' . $row);
            $row++;
        }

        $fileName = 'data_Role_' . now()->format('Ymd_His') . '.xlsx';

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
