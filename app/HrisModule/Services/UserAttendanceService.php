<?php

namespace App\HrisModule\Services;

use App\GeneralModule\Models\Company;
use App\GeneralModule\Models\User;
use App\HrisModule\Models\Departement;
use App\HrisModule\Models\LogUserAttendance;
use App\HrisModule\Models\TimeWorke;
use App\HrisModule\Models\UserAttendance;
use App\HrisModule\Repositories\Contracts\UserAttendanceRepositoryInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class UserAttendanceService
{
    public function __construct(protected UserAttendanceRepositoryInterface $repo)
    {
    }

    public function paginate(int $page, int $limit, array $search, array $sortBy): mixed
    {
        return $this->repo->paginate($page, $limit, $search, $sortBy);
    }

    public function filter_table(?int $companyId, ?int $deptId): mixed
    {
        return [
            'company' => $companyId
                ? Company::where('id', $companyId)->get()
                : Company::all(),

            'dept' => $companyId
                ? Departement::where('company_id', $companyId)->get()
                : Departement::all(),

            'user' => ($companyId && $deptId)
                ? User::where('company_id', $companyId)
                    ->whereHas('employee', fn($query) => $query->where('departement_id', $deptId))
                    ->get()
                : User::all(),

            'status' => [
                ['name' => 'Terlambat', 'value' => 'late'],
                ['name' => 'Tidak Terlambat', 'value' => 'unlate'],
                ['name' => 'Normal', 'value' => 'normal'],
            ],
        ];
    }

    public function form(?int $companyId, ?int $deptId, ?int $userId): mixed
    {
        return $this->repo->form($companyId, $deptId, $userId);
    }

    public function presence_form_qr(?int $company_id, ?int $departement_id): array
    {
        $companies = Company::all();
        $departments = Departement::query()
            ->when($company_id, fn($query) => $query->where('company_id', $company_id))
            ->get();
        $shifts = TimeWorke::query()
            ->when($company_id && $departement_id, function ($query) use ($company_id, $departement_id) {
                $query->where('company_id', $company_id)
                    ->where('departemen_id', $departement_id);
            }, function ($query) {
                $query->with(['company', 'departement']);
            })
            ->get();
        $types = [
            ['name' => 'Masuk', 'value' => 'in'],
            ['name' => 'Pulang', 'value' => 'out'],
        ];
        return [
            'companies' => $companies,
            'departments' => $departments,
            'shifts' => $shifts,
            'types' => $types,
        ];
    }

    public function presence_form_generate_qr(
        int $departement_id,
        int $shift_id,
        string $type_presence
    ): mixed {
        return $this->repo->registration_and_generate_qrcode(
            $departement_id,
            $shift_id,
            $type_presence
        );
    }
    public function presence_form_attendance_qr(
        string $type_presence,
        int $id_token,
    ): mixed {
        return $this->repo->attendance_inout_qrcode(
            $type_presence,
            $id_token,
        );
    }

    public function create(array $data, UploadedFile|null $image_in, UploadedFile|null $image_out): mixed
    {
        if ($image_in) {
            $uploadPath_in = $this->repo->fileUpload($image_in);
            $data['image_in'] = $uploadPath_in;
        }
        if ($image_out) {
            $uploadPath_out = $this->repo->fileUpload($image_out);
            $data['image_out'] = $uploadPath_out;
        }
        return $this->repo->create($data);
    }

    public function presence_form_frd(?int $company_id, ?int $departement_id, ?int $timework_id, ?int $nip): array
    {
        $companies = Company::all();
        $departments = Departement::query()
            ->when($company_id, fn($query) => $query->where('company_id', $company_id))
            ->get();
        $shifts = TimeWorke::query()
            ->when($company_id && $departement_id, function ($query) use ($company_id, $departement_id) {
                $query->where('company_id', $company_id)
                    ->where('departemen_id', $departement_id);
            }, function ($query) {
                $query->with(['company', 'departement']);
            })
            ->get();
        $types = [
            ['name' => 'Masuk', 'value' => 'in'],
            ['name' => 'Pulang', 'value' => 'out'],
        ];
        $query = User::query();
        if ($company_id && $departement_id) {
            $query->where('company_id', $company_id)
                ->whereHas('employee', function ($employeeQuery) use ($departement_id) {
                    $employeeQuery->where('departement_id', $departement_id);
                });
        }
        if ($nip) {
            $query->where('nip', 'like', '%' . $nip . '%');
        }
        $users = $query->get();
        return [
            'companies' => $companies,
            'departments' => $departments,
            'shifts' => $shifts,
            'types' => $types,
            'users' => $users,
        ];
    }

    public function inFrd(array $data, UploadedFile|null $file): mixed
    {
        if ($file) {
            $uploadPath = $this->repo->fileUpload($file);
            $data['image'] = $uploadPath;
        }
        return $this->repo->in($data);
    }

    public function outFrd(array $data, UploadedFile|null $file): mixed
    {
        if ($file) {
            $uploadPath = $this->repo->fileUpload($file);
            $data['image'] = $uploadPath;
        }
        return $this->repo->out($data);
    }

    public function update(int|string $id, array $data, UploadedFile|null $image_in, UploadedFile|null $image_out): mixed
    {
        if ($image_in) {
            $this->repo->fileDelete($id, 'in');
            $uploadPath_in = $this->repo->fileUpload($image_in);
            $data['image_in'] = $uploadPath_in;
        }
        if ($image_out) {
            $this->repo->fileDelete($id, 'out');
            $uploadPath_out = $this->repo->fileUpload($image_out);
            $data['image_out'] = $uploadPath_out;
        }
        return $this->repo->update($id, $data);
    }

    public function delete(int|string $id): bool
    {
        return $this->repo->delete($id);
    }

    public function forceDelete(int|string $id): bool
    {
        return $this->repo->forceDelete($id);
    }

    public function restore(int|string $id): mixed
    {
        return $this->repo->restore($id);
    }

    public function export(
        $name,
        $createdAt,
        $updatedAt,
        $startRange,
        $endRange,
    ): mixed {
        return $this->repo->export(
            $name,
            $createdAt,
            $updatedAt,
            $startRange,
            $endRange,
        );
    }

    public function import($file): mixed
    {
        return $this->repo->import($file);
    }

    public function find($id): mixed
    {
        return $this->repo->find($id);
    }
    public function report($company_id, $departement_id, $user_id, $status_in, $status_out, $start, $end): mixed
    {
        $data = $this->repo->report($company_id, $departement_id, $user_id, $status_in, $status_out, $start, $end);
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        if ($data->isNotEmpty()) {
            // Set Header
            $headers = array_keys((array) $data[0]);
            $columnIndex = 'A';

            foreach ($headers as $header) {
                $sheet->setCellValue($columnIndex . '1', strtoupper($header));
                $columnIndex++;
            }

            // Isi Data
            $rowNumber = 2;
            foreach ($data as $row) {
                $columnIndex = 'A';
                foreach ((array) $row as $value) {
                    $sheet->setCellValue($columnIndex . $rowNumber, $value);
                    $columnIndex++;
                }
                $rowNumber++;
            }
        }

        // Tambahkan Sheet Log Jika Super Admin
        if (Auth::user()->hasRole('super_admin') || Auth::user()->employee->departement_id === 13) {
            $superAdmins = LogUserAttendance::with('user')->get();
            $superAdminSheet = $spreadsheet->createSheet();
            $superAdminSheet->setTitle('Log Attendance');

            if ($superAdmins->isNotEmpty()) {
                $headers = ['ID', 'NIP', 'Name', 'Type', 'Time', 'Date'];
                $columnIndex = 'A';

                foreach ($headers as $header) {
                    $superAdminSheet->setCellValue($columnIndex . '1', strtoupper($header));
                    $columnIndex++;
                }

                $rowNumber = 2;
                foreach ($superAdmins as $admin) {
                    $superAdminSheet->setCellValue('A' . $rowNumber, $admin->id);
                    $superAdminSheet->setCellValue('B' . $rowNumber, $admin->user->nip ?? '-');
                    $superAdminSheet->setCellValue('C' . $rowNumber, $admin->user->name ?? '-');
                    $superAdminSheet->setCellValue('D' . $rowNumber, $admin->type);
                    $superAdminSheet->setCellValue('E' . $rowNumber, Carbon::parse($admin->created_at)->format('H:i:s'));
                    $superAdminSheet->setCellValue('F' . $rowNumber, Carbon::parse($admin->created_at)->format('Y-m-d'));
                    $rowNumber++;
                }
            }
        }

        // Generate File
        $fileName = 'data_export_attendance_' . now()->format('Ymd_His') . '.xlsx';

        $response = new StreamedResponse(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        });

        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $fileName . '"');
        $response->headers->set('Cache-Control', 'max-age=0');
        return $response;
    }
}
