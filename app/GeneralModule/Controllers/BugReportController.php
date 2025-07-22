<?php

namespace App\GeneralModule\Controllers;

use App\GeneralModule\Requests\BugReport\BugReportRequest;
use App\GeneralModule\Requests\BugReport\BugReportIndexRequest;
use App\GeneralModule\Requests\BugReport\BugReportFileRequest;
use App\GeneralModule\Services\BugReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;

class BugReportController extends BaseController
{
    protected BugReportService $service;

    public function __construct(BugReportService $service)
    {
        $this->service = $service;

        $permissions = [
            'index' => 'view_bug_reports|view_any_bug_reports',
            'show' => 'view_bug_reports|view_any_bug_reports',
            'store' => 'create_bug_reports',
            'update' => 'update_bug_reports',
            'destroy' => 'delete_bug_reports|delete_any_bug_reports',
            'delete' => 'forcedelete_bug_reports|forcedelete_any_bug_reports',
            'forceDelete' => 'forcedelete_bug_reports|forcedelete_any_bug_reports',
            'restore' => 'restore_bug_reports',
            'export' => 'export_bug_reports',
            'import' => 'import_bug_reports',
        ];

        foreach ($permissions as $method => $permission) {
            $this->middleware("permission:{$permission},sanctum", ['only' => [$method]]);
        }
    }

    public function index(BugReportIndexRequest $request)
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

    public function store(BugReportRequest $request)
    {
        $input=$request->validated();
        $file = $request->file('image');
        return response()->json($this->service->create($input, $file));
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

    public function update(BugReportRequest $request, $id)
    {
        $input=$request->validated();
        $file = $request->file('image');
        return response()->json($this->service->update($id, $input, $file));
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

    public function print(BugReportFileRequest $request)
    {
        $validated = $request->validated();

        $data = $this->service->export(
            $validated['name'] ?? null,
            $validated['createdAt'] ?? null,
            $validated['updatedAt'] ?? null,
            $validated['startRange'] ?? null,
            $validated['endRange'] ?? null
        );

        $pdf = Pdf::loadView('pdf.bug_report', ['data' => $data]);
        $filename = 'BugReport-' . now()->format('YmdHis') . '.pdf';

        return response($pdf->output(), 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }

    public function xlsx(BugReportFileRequest $request)
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

        $headers = ['No.', 'Title', 'Status', 'Message', 'Platform', 'Created At', 'Updated At'];
        $sheet->fromArray($headers, null, 'A1');

        $row = 2;
        foreach ($data as $index => $val) {
            $sheet->fromArray([
                $index + 1,
                /**tulis value xlsx anda disini**/
                $val->title,
                $val->status ? 'Solve' : 'On Progress',
                $val->platform,
                $val->created_at->format('Y-m-d H:i:s'),
                $val->updated_at->format('Y-m-d H:i:s')
            ], null, 'A' . $row);
            $row++;
        }

        $fileName = 'data_BugReport_' . now()->format('Ymd_His') . '.xlsx';

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
