<?php

namespace App\Jobs;

use App\Models\Export;
use App\Models\Transaction;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Throwable;

class GenerateExportJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Export $export,
        public array $filters = []
    ) {}

    public function handle(): void
    {
        try {
            $this->export->update(['status' => 'processing']);

            $query = Transaction::with('category')
                ->where('user_id', $this->export->user_id);

            if (!empty($this->filters['type'])) {
                $query->where('type', $this->filters['type']);
            }

            if (!empty($this->filters['category_id'])) {
                $query->where('category_id', $this->filters['category_id']);
            }

            if (!empty($this->filters['start_date'])) {
                $query->whereDate('transaction_date', '>=', $this->filters['start_date']);
            }

            if (!empty($this->filters['end_date'])) {
                $query->whereDate('transaction_date', '<=', $this->filters['end_date']);
            }

            $transactions = $query->orderBy('transaction_date', 'asc')->get();

            $timestamp = now()->format('Ymd_His');
            $fileName = "export_{$this->export->id}_{$timestamp}.{$this->export->format}";
            $filePath = "exports/{$fileName}";

            $fileContent = match ($this->export->format) {
                'csv' => $this->generateCsvContent($transactions),
                'xlsx' => $this->generateXlsxXmlContent($transactions),
                'pdf' => $this->generatePdfHtmlContent($transactions),
                default => $this->generateCsvContent($transactions),
            };

            Storage::disk('local')->put($filePath, $fileContent);

            $this->export->update([
                'file_name' => $fileName,
                'file_path' => $filePath,
                'status' => 'completed',
            ]);
        } catch (Throwable $e) {
            $this->export->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    protected function generateCsvContent($transactions): string
    {
        $output = fopen('php://temp', 'r+');
        fputcsv($output, ['ID', 'Date', 'Type', 'Category', 'Amount (IDR)', 'Description', 'Notes']);

        foreach ($transactions as $t) {
            fputcsv($output, [
                $t->id,
                $t->transaction_date ? $t->transaction_date->format('Y-m-d') : '',
                strtoupper($t->type),
                $t->category?->name ?? 'Uncategorized',
                number_format((float) $t->amount, 2, '.', ''),
                $t->description ?? '',
                $t->notes ?? '',
            ]);
        }

        rewind($output);
        $csvString = stream_get_contents($output);
        fclose($output);

        return $csvString;
    }

    protected function generateXlsxXmlContent($transactions): string
    {
        // Generates Spreadsheet XML representation compatible with Excel (.xlsx/.xml)
        $xml = "<?xml version=\"1.0\"?>\n";
        $xml .= "<?mso-application progid=\"Excel.Sheet\"?>\n";
        $xml .= "<Workbook xmlns=\"urn:schemas-microsoft-com:office:spreadsheet\"\n";
        $xml .= " xmlns:o=\"urn:schemas-microsoft-com:office:office\"\n";
        $xml .= " xmlns:x=\"urn:schemas-microsoft-com:office:excel\"\n";
        $xml .= " xmlns:ss=\"urn:schemas-microsoft-com:office:spreadsheet\">\n";
        $xml .= " <Worksheet ss:Name=\"Financial Transactions\">\n";
        $xml .= "  <Table>\n";
        $xml .= "   <Row>\n";
        $xml .= "    <Cell><Data ss:Type=\"String\">ID</Data></Cell>\n";
        $xml .= "    <Cell><Data ss:Type=\"String\">Date</Data></Cell>\n";
        $xml .= "    <Cell><Data ss:Type=\"String\">Type</Data></Cell>\n";
        $xml .= "    <Cell><Data ss:Type=\"String\">Category</Data></Cell>\n";
        $xml .= "    <Cell><Data ss:Type=\"String\">Amount</Data></Cell>\n";
        $xml .= "    <Cell><Data ss:Type=\"String\">Description</Data></Cell>\n";
        $xml .= "   </Row>\n";

        foreach ($transactions as $t) {
            $xml .= "   <Row>\n";
            $xml .= "    <Cell><Data ss:Type=\"Number\">{$t->id}</Data></Cell>\n";
            $xml .= "    <Cell><Data ss:Type=\"String\">" . ($t->transaction_date ? $t->transaction_date->format('Y-m-d') : '') . "</Data></Cell>\n";
            $xml .= "    <Cell><Data ss:Type=\"String\">" . strtoupper($t->type) . "</Data></Cell>\n";
            $xml .= "    <Cell><Data ss:Type=\"String\">" . htmlspecialchars($t->category?->name ?? 'Uncategorized') . "</Data></Cell>\n";
            $xml .= "    <Cell><Data ss:Type=\"Number\">{$t->amount}</Data></Cell>\n";
            $xml .= "    <Cell><Data ss:Type=\"String\">" . htmlspecialchars($t->description ?? '') . "</Data></Cell>\n";
            $xml .= "   </Row>\n";
        }

        $xml .= "  </Table>\n";
        $xml .= " </Worksheet>\n";
        $xml .= "</Workbook>";

        return $xml;
    }

    protected function generatePdfHtmlContent($transactions): string
    {
        $rows = '';
        foreach ($transactions as $t) {
            $date = $t->transaction_date ? $t->transaction_date->format('Y-m-d') : '';
            $type = strtoupper($t->type);
            $cat = htmlspecialchars($t->category?->name ?? 'Uncategorized');
            $amount = 'Rp ' . number_format((float) $t->amount, 0, ',', '.');
            $desc = htmlspecialchars($t->description ?? '');

            $rows .= "<tr><td>{$t->id}</td><td>{$date}</td><td>{$type}</td><td>{$cat}</td><td style='text-align:right;'>{$amount}</td><td>{$desc}</td></tr>";
        }

        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Financial Report</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; margin: 20px; }
        h1 { color: #333; text-align: center; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>
    <h1>Bukuang - Financial Report</h1>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Date</th>
                <th>Type</th>
                <th>Category</th>
                <th style="text-align:right;">Amount</th>
                <th>Description</th>
            </tr>
        </thead>
        <tbody>
            {$rows}
        </tbody>
    </table>
</body>
</html>
HTML;
    }
}
