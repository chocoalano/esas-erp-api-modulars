<?php

namespace App\HrisModule\Controllers;

use App\Console\Support\ExcelExport;
use App\HrisModule\Requests\UserAttendance\AttendanceRequest;
use App\HrisModule\Requests\UserAttendance\UserAttendanceFileRequest;
use App\HrisModule\Requests\UserAttendance\UserAttendanceIndexRequest;
use App\HrisModule\Requests\UserAttendance\UserAttendanceRequest;
use App\HrisModule\Services\UserAttendanceService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;

/**
 * @method bool hasFile(string $key)
 * @method \Illuminate\Http\UploadedFile|null file(string $key, $default = null)
 * @method array validated()
 */
class UserAttendanceController extends BaseController
{
    protected $service;

    protected $excel;

    public function __construct(UserAttendanceService $service, ExcelExport $excel)
    {
        $this->service = $service;
        $this->excel = $excel;

        $permissions = [
            'index' => 'view_user_attendances|view_any_user_attendances',
            'show' => 'view_user_attendances|view_any_user_attendances',
            'store' => 'create_user_attendances',
            'update' => 'update_user_attendances',
            'destroy' => 'delete_user_attendances|delete_any_user_attendances',
            'delete' => 'forcedelete_user_attendances|forcedelete_any_user_attendances',
            'forceDelete' => 'forcedelete_user_attendances|forcedelete_any_user_attendances',
            'restore' => 'restore_user_attendances',
            'export' => 'export_user_attendances',
            'import' => 'import_user_attendances',
        ];

        foreach ($permissions as $method => $permission) {
            $this->middleware("permission:{$permission},sanctum", ['only' => [$method]]);
        }
    }

    public function index(UserAttendanceIndexRequest $request)
    {
        $validated = $request->validated();

        return response()->json($this->service->paginate(
            $validated['page'],
            $validated['limit'],
            $validated['search'] ?? [],
            $validated['sortBy'] ?? []
        ));
    }

    public function filter_table(Request $request)
    {
        $validated = $request->validate([
            'company_id' => 'nullable|integer|exists:companies,id',
            'dept_id' => 'nullable|integer|exists:departements,id',
        ]);

        return response()->json([
            'form' => $this->service->filter_table($validated['company_id'] ?? null, $validated['dept_id'] ?? null),
        ]);
    }

    public function create(Request $request)
    {
        $validated = $request->validate([
            'company_id' => 'nullable|int|exists:companies,id',
            'departement_id' => 'nullable|int|exists:departements,id',
            'user_id' => 'nullable|int|exists:users,id',
        ]);

        return response()->json([
            'form' => $this->service->form($validated['company_id'] ?? null, $validated['departement_id'] ?? null, $validated['user_id'] ?? null),
        ]);
    }

    public function store(UserAttendanceRequest $request)
    {
        // Ambil hanya data yang tervalidasi
        $data = $request->validated();
        $result = $this->service->create($data, $data['image_in'] ?? null, $data['image_out'] ?? null);

        return response()->json([
            'success' => true,
            'data' => $result,
        ], 201);
    }

    public function presence_form_qr(Request $request)
    {
        $validated = $request->validate([
            'company_id' => 'nullable|integer|exists:companies,id',
            'departement_id' => 'nullable|integer|exists:departements,id',
        ]);

        // dd();
        // return response()->json($validated);
        return response()->json($this->service->presence_form_qr($validated['company_id'] ?? null, $validated['departement_id'] ?? null));
    }

    public function presence_form_qr_submit(Request $request)
    {
        $validated = $request->validate([
            'departement_id' => 'required|integer|exists:departements,id',
            'shift_id' => 'required|integer|exists:time_workes,id',
            'type' => 'required|in:in,out',
        ]);
        $proses = $this->service->presence_form_generate_qr($validated['departement_id'], $validated['shift_id'], $validated['type']);

        return response()->json([
            'type' => $proses->type,
            'departement_id' => $proses->departement_id,
            'timework_id' => $proses->timework_id,
            'for_presence' => $proses->for_presence->timezone('Asia/Jakarta')->toDateTimeString(),
            'expires_at' => $proses->expires_at->timezone('Asia/Jakarta')->toDateTimeString(),
            'updated_at' => optional($proses->updated_at)->timezone('Asia/Jakarta')->toDateTimeString(),
            'created_at' => optional($proses->created_at)->timezone('Asia/Jakarta')->toDateTimeString(),
            'id' => $proses->id,
        ]);
    }

    public function presence_form_attendance_qr(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:in,out',
            'id_token' => 'required|integer|exists:qr_presences,id',
        ]);
        $proses = $this->service->presence_form_attendance_qr(
            $validated['type'],
            $validated['id_token']
        );

        return response()->json($proses);
    }

    public function presence_form_frd(Request $request)
    {
        $validated = $request->validate([
            'company_id' => 'nullable|integer|exists:companies,id',
            'departement_id' => 'nullable|integer|exists:departements,id',
            'timework_id' => 'nullable|integer|exists:time_workes,id',
            'nip' => 'nullable|numeric',
        ]);

        return response()->json($this->service->presence_form_frd(
            $validated['company_id'] ?? null,
            $validated['departement_id'] ?? null,
            $validated['timework_id'] ?? null,
            $validated['nip'] ?? null,
        ));
    }

    public function presence_form_attendance_frd(AttendanceRequest $request)
    {
        $input = $request->validated();
        $file = $input['image'];
        $created = $input['type'] === 'in' ?
            $this->service->inFrd($input, $file) :
            $this->service->outFrd($input, $file);

        return response()->json($created);
    }

    public function show($id)
    {
        return response()->json($this->service->find($id));
    }

    public function edit(Request $request, $id)
    {
        $validated = $request->validate([
            'company_id' => 'nullable|int|exists:companies,id',
            'departement_id' => 'nullable|int|exists:departements,id',
            'user_id' => 'nullable|int|exists:users,id',
        ]);

        return response()->json([
            'data' => $this->service->find($id),
            'form' => $this->service->form($validated['company_id'] ?? null, $validated['departement_id'] ?? null, $validated['user_id'] ?? null),
        ]);
    }

    public function update(UserAttendanceRequest $request, $id)
    {
        $input = $request->validated();

        return response()->json($this->service->update($id, $input, $input['image_in'] ?? null, $input['image_out'] ?? null));
    }

    public function destroy($id)
    {
        return response()->json($this->service->delete($id));
    }

    public function print(UserAttendanceFileRequest $request)
    {
        $validated = $request->validated();

        $data = $this->service->export(
            $validated['name'] ?? null,
            $validated['createdAt'] ?? null,
            $validated['updatedAt'] ?? null,
            $validated['startRange'] ?? null,
            $validated['endRange'] ?? null
        );

        $pdf = Pdf::loadView('pdf.user_attendance', ['data' => $data]);
        $filename = 'UserAttendance-'.now()->format('YmdHis').'.pdf';

        return response($pdf->output(), 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="'.$filename.'"');
    }

    public function xlsx(UserAttendanceFileRequest $request)
    {
        $validated = $request->validated();

        $data = $this->service->export(
            $validated['name'] ?? null,
            $validated['createdAt'] ?? null,
            $validated['updatedAt'] ?? null,
            $validated['start'] ?? null,
            $validated['end'] ?? null
        );
        if (! empty($data)) {
            // code...
            $headers = [
                'No.',
                'NIP',
                'User Name',
                'Time In',
                'Status In',
                'Type In',
                'Lat In',
                'Lng In',
                'Time Out',
                'Status Out',
                'Type Out',
                'Lat Out',
                'Lng Out',
                'Created At',
                'Updated At',
            ];
            $dataFormatter = function ($val, $index) {
                return [
                    $index + 1,
                    ExcelExport::getNestedValue($val, 'user.nip'),
                    ExcelExport::getNestedValue($val, 'user.name'),
                    $val->time_in,
                    $val->status_in,
                    $val->type_in,
                    $val->lat_in,
                    $val->long_in,
                    $val->time_out,
                    $val->status_out,
                    $val->type_out,
                    $val->lat_out,
                    $val->long_out,
                    ExcelExport::formatCarbonDate($val->start_date, 'Y-m-d'),
                    ExcelExport::formatCarbonDate($val->created_at),
                    ExcelExport::formatCarbonDate($val->updated_at),
                ];
            };

            return $this->excel->generateAndDownload(
                $data,
                $headers,
                $dataFormatter,
                'absensi' // Prefix untuk nama file
            );
        } else {
            return response()->json('Tidak ada data ditampilkan', 404);
        }
    }

    public function import(Request $request)
    {
        return response()->json($this->service->import($request));
    }

    public function xlsx_report(Request $request)
    {
        $validated = $request->validate([
            'company_id' => 'nullable|integer|exists:companies,id',
            'departement_id' => 'nullable|integer|exists:departements,id',
            'user_id' => 'nullable|array',
            'status_in' => 'nullable|string|in:late,unlate,normal',
            'status_out' => 'nullable|string|in:late,unlate,normal',
            'start' => 'required|date|before_or_equal:end',
            'end' => 'required|date|after_or_equal:start',
        ]);
        $data = $this->service->report(
            $validated['company_id'] ?? null,
            $validated['departement_id'] ?? null,
            $validated['user_id'] ?? null,
            $validated['status_in'] ?? null,
            $validated['status_out'] ?? null,
            $validated['start'] ?? null,
            $validated['end'] ?? null,
        );

        return $data;
    }
}
