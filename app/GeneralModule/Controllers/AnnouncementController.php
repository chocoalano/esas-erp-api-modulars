<?php

namespace App\GeneralModule\Controllers;

use App\GeneralModule\Requests\Announcement\AnnouncementRequest;
use App\GeneralModule\Requests\Announcement\AnnouncementIndexRequest;
use App\GeneralModule\Requests\Announcement\AnnouncementFileRequest;
use App\GeneralModule\Services\AnnouncementService;
use Barryvdh\DomPDF\Facade\Pdf;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;

class AnnouncementController extends BaseController
{
    protected AnnouncementService $service;

    public function __construct(AnnouncementService $service)
    {
        $this->service = $service;

        $permissions = [
            'index'       => 'view_announcements|view_any_announcements',
            'show'        => 'view_announcements|view_any_announcements',
            'store'       => 'create_announcements',
            'update'      => 'update_announcements',
            'destroy'     => 'delete_announcements|delete_any_announcements',
            'delete'      => 'forcedelete_announcements|forcedelete_any_announcements',
            'forceDelete' => 'forcedelete_announcements|forcedelete_any_announcements',
            'restore'     => 'restore_announcements',
            'export'      => 'export_announcements',
            'import'      => 'import_announcements',
        ];

        foreach ($permissions as $method => $permission) {
            $this->middleware("permission:{$permission},sanctum", ['only' => [$method]]);
        }
    }

    public function index(AnnouncementIndexRequest $request)
    {
        $validated = $request->validated();

        return response()->json($this->service->paginate(
            $validated['page'],
            $validated['limit'],
            $validated['search'] ?? [],
            $validated['sortBy'] ?? []
        ));
    }
    public function indexActive()
    {
        return response()->json($this->service->getAllActive());
    }

    public function create()
    {
        return response()->json([
            'form' => $this->service->form()
        ]);
    }

    public function store(AnnouncementRequest $request)
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

    public function update(AnnouncementRequest $request, $id)
    {
        return response()->json($this->service->update($id, $request->validated()));
    }

    public function destroy($id)
    {
        return response()->json($this->service->delete($id));
    }

    public function print(AnnouncementFileRequest $request)
    {
        $validated = $request->validated();

        $data = $this->service->export(
            $validated['name'] ?? null,
            $validated['createdAt'] ?? null,
            $validated['updatedAt'] ?? null,
            $validated['startRange'] ?? null,
            $validated['endRange'] ?? null
        );

        $pdf = Pdf::loadView('pdf.announcement', ['data' => $data]);
        $filename = 'Announcement-' . now()->format('YmdHis') . '.pdf';

        return response($pdf->output(), 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }

    public function xlsx(AnnouncementFileRequest $request)
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

        $fileName = 'data_Announcement_' . now()->format('Ymd_His') . '.xlsx';

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
