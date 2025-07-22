<?php

namespace App\HrisModule\Controllers;

use App\HrisModule\Models\Departement;
use App\HrisModule\Requests\Departement\DepartementRequest;
use App\HrisModule\Requests\Departement\DepartementIndexRequest;
use App\HrisModule\Requests\Departement\DepartementFileRequest;
use App\HrisModule\Services\DepartementService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;

class DepartementController extends BaseController
{
    protected DepartementService $service;

    public function __construct(DepartementService $service)
    {
        $this->service = $service;

        $permissions = [
            'index' => 'view_departements|view_any_departements',
            'show' => 'view_departements|view_any_departements',
            'store' => 'create_departements',
            'update' => 'update_departements',
            'destroy' => 'delete_departements|delete_any_departements',
            'delete' => 'forcedelete_departements|forcedelete_any_departements',
            'forceDelete' => 'forcedelete_departements|forcedelete_any_departements',
            'restore' => 'restore_departements',
            'export' => 'export_departements',
            'import' => 'import_departements',
        ];

        foreach ($permissions as $method => $permission) {
            $this->middleware("permission:{$permission},sanctum", ['only' => [$method]]);
        }
    }

    public function index(DepartementIndexRequest $request)
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

    public function store(DepartementRequest $request)
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

    public function update(DepartementRequest $request, $id)
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

    public function deleted(DepartementIndexRequest $request)
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

    public function print(DepartementFileRequest $request)
    {
        $validated = $request->validated();

        $data = $this->service->export(
            $validated['name'] ?? null,
            $validated['createdAt'] ?? null,
            $validated['updatedAt'] ?? null,
            $validated['startRange'] ?? null,
            $validated['endRange'] ?? null
        );
        $pdf = Pdf::loadView('pdf.departement', ['data' => $data]);
        $filename = 'Departement-' . now()->format('YmdHis') . '.pdf';

        return response($pdf->output(), 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }

    public function xlsx(DepartementFileRequest $request)
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
            'No',
            'Perusahaan',
            'Nama',
            'Tanggal Dibuat',
            'Tanggal Diperbarui'
        ];
        $sheet->fromArray($headers, null, 'A1');

        $row = 2;
        foreach ($data as $index => $val) {
            $sheet->fromArray([
                $index + 1,
                /**tulis value xlsx anda disini**/
                $val->company->name,
                $val->name,
                $val->created_at->format('Y-m-d H:i:s'),
                $val->updated_at->format('Y-m-d H:i:s')
            ], null, 'A' . $row);
            $row++;
        }

        $fileName = 'data_Departement_' . now()->format('Ymd_His') . '.xlsx';

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
