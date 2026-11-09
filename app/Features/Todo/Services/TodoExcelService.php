<?php

namespace App\Features\Todo\Services;

use App\Features\Todo\Contracts\TodoRepositoryInterface;
use App\Features\Todo\Enums\TodoStatus;
use App\Features\Todo\Models\Todo;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TodoExcelService
{
    public function __construct(
        protected TodoRepositoryInterface $todoRepository
    ) {}

    /**
     * Build a Spreadsheet instance containing todos data.
     *
     * @param Collection<int, Todo> $todos
     */
    public function buildSpreadsheet(Collection $todos, string $sheetTitle = 'Todos'): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle(substr($sheetTitle, 0, 31));

        $headers = ['No', 'Title', 'Description', 'Status', 'Due Date', 'Created At'];
        $columns = ['A', 'B', 'C', 'D', 'E', 'F'];

        foreach ($headers as $index => $header) {
            $sheet->setCellValue("{$columns[$index]}1", $header);
        }

        // Header Styling
        $headerRange = 'A1:F1';
        $sheet->getStyle($headerRange)->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $sheet->getStyle($headerRange)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('2563EB');
        $sheet->getStyle($headerRange)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $rowNumber = 2;
        foreach ($todos as $idx => $todo) {
            $sheet->setCellValue("A{$rowNumber}", $idx + 1);
            $sheet->setCellValue("B{$rowNumber}", $todo->title);
            $sheet->setCellValue("C{$rowNumber}", $todo->description ?? '-');
            $sheet->setCellValue("D{$rowNumber}", ucfirst($todo->status));
            $sheet->setCellValue("E{$rowNumber}", $todo->due_date ? Carbon::parse($todo->due_date)->format('Y-m-d') : '-');
            $sheet->setCellValue("F{$rowNumber}", $todo->created_at ? $todo->created_at->format('Y-m-d H:i') : '-');
            $rowNumber++;
        }

        $lastRow = max(2, $rowNumber - 1);
        $sheet->getStyle("A1:F{$lastRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        foreach ($columns as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        return $spreadsheet;
    }

    /**
     * Export todos as a downloadable streamed response.
     *
     * @param Collection<int, Todo> $todos
     */
    public function exportToResponse(Collection $todos, string $filename = 'todolist.xlsx'): StreamedResponse
    {
        $spreadsheet = $this->buildSpreadsheet($todos);

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    /**
     * Import todos from an uploaded spreadsheet file path.
     *
     * @return array{imported: int, errors: array<int, string>}
     */
    public function import(string $filePath, int $userId): array
    {
        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray(null, true, true, true);

        $imported = 0;
        $errors = [];
        $recordsToInsert = [];
        $now = Carbon::now();

        // Skip header row
        $isFirst = true;
        $rowIndex = 1;

        foreach ($rows as $row) {
            if ($isFirst) {
                $isFirst = false;
                $rowIndex++;
                continue;
            }

            $title = isset($row['B']) && trim((string) $row['B']) !== '' ? trim((string) $row['B']) : (isset($row['A']) ? trim((string) $row['A']) : '');
            $description = isset($row['C']) ? trim((string) $row['C']) : null;
            $statusRaw = isset($row['D']) ? strtolower(trim((string) $row['D'])) : 'pending';
            $dueDateRaw = isset($row['E']) ? trim((string) $row['E']) : null;

            // Handle case where column A is Title
            if ($title === '' || strtolower($title) === 'no' || strtolower($title) === 'title') {
                $rowIndex++;
                continue;
            }

            $validStatuses = [
                TodoStatus::PENDING->value,
                TodoStatus::IN_PROGRESS->value,
                TodoStatus::COMPLETED->value,
            ];

            $status = in_array($statusRaw, $validStatuses, true) ? $statusRaw : TodoStatus::PENDING->value;

            $dueDate = null;
            if (!empty($dueDateRaw) && $dueDateRaw !== '-') {
                try {
                    $dueDate = Carbon::parse($dueDateRaw)->format('Y-m-d');
                } catch (\Throwable) {
                    $dueDate = null;
                }
            }

            $recordsToInsert[] = [
                'user_id' => $userId,
                'title' => substr($title, 0, 255),
                'description' => $description === '-' ? null : $description,
                'status' => $status,
                'due_date' => $dueDate,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            $imported++;
            $rowIndex++;
        }

        if (!empty($recordsToInsert)) {
            $this->todoRepository->insertMany($recordsToInsert);
        }

        return [
            'imported' => $imported,
            'errors' => $errors,
        ];
    }
}

