<?php

namespace App\GeneralModule\Controllers;

use App\GeneralModule\Requests\UserIndexRequest;
use App\GeneralModule\Requests\UserLogRequest;
use Illuminate\Routing\Controller as BaseController;
use App\GeneralModule\Requests\UserRequest;
use App\GeneralModule\Services\UserService;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class UserController extends BaseController
{
    protected UserService $service;

    public function __construct(UserService $service)
    {
        $this->service = $service;

        $permissions = [
            'index' => 'view_users|view_any_users',
            'show' => 'view_users|view_any_users',
            'store' => 'create_users',
            'update' => 'update_users',
            'destroy' => 'delete_users|delete_any_users',
            'delete' => 'forcedelete_users|forcedelete_any_users',
            'forceDelete' => 'forcedelete_users|forcedelete_any_users',
            'restore' => 'restore_users',
            'print' => 'export_users',
            'xlsx' => 'export_users',
            'import' => 'import_users',
        ];

        foreach ($permissions as $method => $permission) {
            $this->middleware("permission:{$permission},sanctum", ['only' => [$method]]);
        }
    }

    public function index(UserIndexRequest $request)
    {
        $validated = $request->validated();

        $page = $validated['page'];
        $limit = $validated['limit'];
        $search = $validated['search'] ?? [];
        $sortBy = $validated['sortBy'] ?? [];

        $user = Auth::user();
        if ($user->hasRole(['Administrator', 'super_admin'])) {
            $data = $this->service->paginateAdmin($page, $limit, $search, $sortBy);
        } elseif ($user->hasRole(['Member', 'Admin Departement'])) {
            $departementId = $user->employee->departement_id;
            $data = $this->service->paginateMember($page, $limit, $search, $sortBy, $departementId);
        } else {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json($data);
    }
    public function filter_paginate(Request $request)
    {
        $input = $request->validate([
            'company_id' => ['nullable', 'integer', 'exists:companies,id'],
            'dept_id' => ['nullable', 'integer', 'exists:departements,id'],
            'post_id' => ['nullable', 'integer', 'exists:job_positions,id'],
            'lvl_id' => ['nullable', 'integer', 'exists:job_levels,id'],
        ]);
        // dd($input);
        return response()->json($this->service->get_table_filter_attribute(
            $input['company_id'] ?? null,
            $input['dept_id'] ?? null,
            $input['post_id'] ?? null,
            $input['lvl_id'] ?? null
        ));
    }

    public function create(Request $request)
    {
        $input = $request->validate([
            'company_id' => ['nullable', 'integer', 'exists:companies,id'],
            'dept_id' => ['nullable', 'integer', 'exists:departements,id'],
            'post_id' => ['nullable', 'integer', 'exists:job_positions,id'],
            'lvl_id' => ['nullable', 'integer', 'exists:job_levels,id'],
        ]);
        return response()->json([
            'form' => $this->service->form(
                $input['company_id'],
                $input['dept_id'],
                $input['post_id'],
                $input['lvl_id']
            )
        ]);
    }

    public function store(UserRequest $request)
    {
        $input = $request->validated();
        $file = $request->file('avatar_file');
        return response()->json($this->service->create($input, $file));
    }

    public function show($id)
    {
        return response()->json($this->service->find($id));
    }
    public function password_reset($id)
    {
        $find = $this->service->find($id);
        $hashedPassword = Hash::make($find->nip);
        $update = $this->service->simple_update($find->id, ['password' => $hashedPassword]);
        return response()->json($update);
    }
    public function device_reset($id)
    {
        $find = $this->service->find($id);
        $update = $this->service->simple_update($find->id, ['device_id' => null]);
        return response()->json($update);
    }

    public function edit(Request $request, $id)
    {
        $input = $request->validate([
            'company_id' => ['nullable', 'integer', 'exists:companies,id'],
            'dept_id' => ['nullable', 'integer', 'exists:departements,id'],
            'post_id' => ['nullable', 'integer', 'exists:job_positions,id'],
            'lvl_id' => ['nullable', 'integer', 'exists:job_levels,id'],
        ]);
        return response()->json([
            'data' => $this->service->find($id),
            'form' => $this->service->form(
                $input['company_id'] ?? null,
                $input['dept_id'] ?? null,
                $input['post_id'] ?? null,
                $input['lvl_id'] ?? null
            )
        ]);
    }

    public function update(UserRequest $request, $id)
    {
        $input = $request->validated();
        $file = $request->file('avatar');
        return response()->json($this->service->update($id, $input, $file));
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

    public function deleted(UserIndexRequest $request)
    {
        $validated = $request->validated();
        $page = $validated['page'];
        $limit = $validated['limit'];
        $search = $validated['search'] ?? [];
        $sortBy = $validated['sortBy'] ?? [];
        return response()->json($this->service->paginateTrashed($page, $limit, $search, $sortBy));
    }

    public function print(Request $request)
    {
        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'company' => 'nullable|string|max:255',
            'departemen' => 'nullable|string|max:255',
            'position' => 'nullable|string|max:255',
            'level' => 'nullable|string|max:50',
            'createdAt' => 'nullable|date',
            'updatedAt' => 'nullable|date',
            'startRange' => 'nullable|date',
            'endRange' => 'nullable|date|after_or_equal:startRange',
        ]);
        $data = $this->service->export(
            $validated['name'] ?? null,
            $validated['company'] ?? null,
            $validated['departemen'] ?? null,
            $validated['position'] ?? null,
            $validated['level'] ?? null,
            $validated['createdAt'] ?? null,
            $validated['updatedAt'] ?? null,
            $validated['startRange'] ?? null,
            $validated['endRange'] ?? null
        );
        $pdf = Pdf::loadView('pdf.user', ['data' => $data]);
        $filename = 'user-' . now()->format('YmdHis') . '.pdf';
        return response($pdf->output(), 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }
    public function xlsx(Request $request)
    {
        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'company' => 'nullable|string|max:255',
            'departemen' => 'nullable|string|max:255',
            'position' => 'nullable|string|max:255',
            'level' => 'nullable|string|max:50',
            'createdAt' => 'nullable|date',
            'updatedAt' => 'nullable|date',
            'startRange' => 'nullable|date',
            'endRange' => 'nullable|date|after_or_equal:startRange',
        ]);

        // Ambil data
        $data = $this->service->export(
            $validated['name'] ?? null,
            $validated['company'] ?? null,
            $validated['departemen'] ?? null,
            $validated['position'] ?? null,
            $validated['level'] ?? null,
            $validated['createdAt'] ?? null,
            $validated['updatedAt'] ?? null,
            $validated['startRange'] ?? null,
            $validated['endRange'] ?? null
        );

        // Inisialisasi Spreadsheet
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Definisikan Header dan Mapping
        $headers = [
            'No',
            'NIP',
            'Nama',
            'Email',
            'Status',
            'Perusahaan',
            'Tanggal Dibuat',
            'Tanggal Diperbarui'
        ];

        $sheet->fromArray($headers, null, 'A1'); // Buat header otomatis

        // Isi data secara dinamis
        $row = 2;
        foreach ($data as $index => $user) {
            $sheet->fromArray([
                $index + 1,
                $user->nip,
                $user->name,
                $user->email,
                    $user::STATUS[$user->status] ?? $user->status,
                $user->company->name ?? '-',
                $user->created_at->format('Y-m-d H:i:s'),
                $user->updated_at->format('Y-m-d H:i:s')
            ], null, 'A' . $row);
            $row++;
        }

        // Buat response
        $fileName = 'data_users_' . now()->format('Ymd_His') . '.xlsx';

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
    public function logs(UserLogRequest $request,$id)
    {
        $validated = $request->validated();

        $page = $validated['page'];
        $limit = $validated['limit'];
        $search = $validated['search'] ?? [];
        $sortBy = $validated['sortBy'] ?? [];
        return response()->json($this->service->logs($id, $page, $limit, $search, $sortBy));
    }
}
