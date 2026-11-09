<?php

namespace Tests\Unit\Services;

use App\Features\Todo\Models\Todo;
use App\Features\Todo\Repositories\TodoRepository;
use App\Features\Todo\Services\TodoExcelService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Tests\TestCase;

class TodoExcelServiceTest extends TestCase
{
    use RefreshDatabase;

    protected TodoExcelService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new TodoExcelService(new TodoRepository());
    }

    public function test_build_spreadsheet_and_export_response(): void
    {
        $user = User::factory()->create();
        $todos = Todo::factory()->count(2)->create(['user_id' => $user->id]);

        $spreadsheet = $this->service->buildSpreadsheet($todos, 'Custom Todos');
        $this->assertInstanceOf(Spreadsheet::class, $spreadsheet);
        $this->assertEquals('Custom Todos', $spreadsheet->getActiveSheet()->getTitle());

        $response = $this->service->exportToResponse($todos, 'test.xlsx');
        $this->assertInstanceOf(StreamedResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());

        ob_start();
        $response->sendContent();
        $outputContent = ob_get_clean();
        $this->assertNotEmpty($outputContent);
    }

    public function test_import_spreadsheet(): void
    {
        $user = User::factory()->create();

        // Create an in-memory spreadsheet and save to a temporary file
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Headers
        $sheet->setCellValue('A1', 'No');
        $sheet->setCellValue('B1', 'Title');
        $sheet->setCellValue('C1', 'Description');
        $sheet->setCellValue('D1', 'Status');
        $sheet->setCellValue('E1', 'Due Date');

        // Row 2: Standard todo
        $sheet->setCellValue('A2', '1');
        $sheet->setCellValue('B2', 'Imported Todo 1');
        $sheet->setCellValue('C2', 'Desc 1');
        $sheet->setCellValue('D2', 'in_progress');
        $sheet->setCellValue('E2', '2026-12-01');

        // Row 3: Status invalid fallback to pending, invalid date, description '-'
        $sheet->setCellValue('A3', '2');
        $sheet->setCellValue('B3', 'Imported Todo 2');
        $sheet->setCellValue('C3', '-');
        $sheet->setCellValue('D3', 'unknown_status');
        $sheet->setCellValue('E3', 'not-a-date');

        // Row 4: Column B empty, fallback to Column A
        $sheet->setCellValue('A4', 'Todo from Col A');
        $sheet->setCellValue('B4', '');
        $sheet->setCellValue('C4', 'Col A Desc');
        $sheet->setCellValue('D4', 'completed');
        $sheet->setCellValue('E4', '-');

        // Row 5: Row with duplicate header 'No' or empty title (ignored)
        $sheet->setCellValue('A5', 'No');
        $sheet->setCellValue('B5', 'Title');
        $sheet->setCellValue('C5', 'Skip this');

        $tempPath = tempnam(sys_get_temp_dir(), 'test_excel_').'.xlsx';
        $writer = new Xlsx($spreadsheet);
        $writer->save($tempPath);

        $result = $this->service->import($tempPath, $user->id);
        @unlink($tempPath);

        $this->assertEquals(3, $result['imported']);
        $this->assertDatabaseHas('todos', [
            'user_id' => $user->id,
            'title' => 'Imported Todo 1',
            'status' => 'in_progress',
            'due_date' => '2026-12-01',
        ]);
        $this->assertDatabaseHas('todos', [
            'user_id' => $user->id,
            'title' => 'Imported Todo 2',
            'description' => null,
            'status' => 'pending',
            'due_date' => null,
        ]);
        $this->assertDatabaseHas('todos', [
            'user_id' => $user->id,
            'title' => 'Todo from Col A',
            'status' => 'completed',
        ]);
    }
}
