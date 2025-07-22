<?php

namespace App\Console\Support;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Illuminate\Support\Collection; // Use Illuminate's Collection for data
use Carbon\Carbon; // Ensure Carbon is imported

class ExcelExport
{
    /**
     * Generate an Excel spreadsheet and prepare it for download.
     *
     * @param Collection $data The data collection to export.
     * @param array $headers An array of strings for the column headers.
     * @param callable $dataFormatter A callback function to format each row's data.
     * It receives the current item ($item) and its index ($index).
     * It should return an array of values for the row.
     * @param string $fileNamePrefix A prefix for the generated file name.
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function generateAndDownload(
        Collection $data,
        array $headers,
        callable $dataFormatter, // A callback to format each row
        string $fileNamePrefix = 'export'
    ) {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Set headers in the first row
        $sheet->fromArray([$headers], null, 'A1');

        $row = 2; // Start from the second row for data
        foreach ($data as $index => $item) {
            // Use the provided dataFormatter callback to get the row's values
            $rowData = $dataFormatter($item, $index);
            $sheet->fromArray([$rowData], null, 'A' . $row);
            $row++;
        }

        $fileName = $fileNamePrefix . '_' . now()->format('Ymd_His') . '.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    /**
     * Helper to safely parse and format a date.
     *
     * @param string|null $dateString
     * @param string $format
     * @return string|null
     */
    public static function formatCarbonDate(?string $dateString, string $format = 'Y-m-d H:i:s'): ?string
    {
        return $dateString ? Carbon::parse($dateString)->format($format) : null;
    }

    /**
     * Helper to safely get a value from a nested relationship.
     *
     * @param mixed $item The main item/model.
     * @param string $relationPath The dot-separated path to the nested value (e.g., 'user.name').
     * @param string $default The default value if not found.
     * @return mixed
     */
    public static function getNestedValue($item, string $relationPath, string $default = 'N/A')
    {
        $parts = explode('.', $relationPath);
        $current = $item;

        foreach ($parts as $part) {
            if (is_object($current) && isset($current->$part)) {
                $current = $current->$part;
            } elseif (is_array($current) && isset($current[$part])) {
                $current = $current[$part];
            } else {
                return $default; // Path not found
            }
        }

        return $current;
    }
}
