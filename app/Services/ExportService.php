<?php

namespace App\Services;

use App\Jobs\GenerateExportJob;
use App\Models\Export;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportService
{
    /**
     * Create an export job request and dispatch background processing.
     */
    public function createExport(User $user, array $data): Export
    {
        $export = Export::create([
            'user_id' => $user->id,
            'format' => $data['format'],
            'status' => 'pending',
        ]);

        GenerateExportJob::dispatch($export, $data);

        return $export;
    }

    /**
     * Get user export jobs list.
     */
    public function getUserExports(User $user): Collection
    {
        return Export::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Download completed export file.
     */
    public function downloadExport(Export $export): StreamedResponse
    {
        if ($export->status !== 'completed' || !$export->file_path || !Storage::disk('local')->exists($export->file_path)) {
            abort(404, 'Berkas ekspor belum siap atau tidak ditemukan.');
        }

        return Storage::disk('local')->download($export->file_path, $export->file_name);
    }
}
