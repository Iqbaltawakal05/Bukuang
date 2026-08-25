<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Export\StoreExportRequest;
use App\Http\Resources\ExportResource;
use App\Models\Export;
use App\Services\ExportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportController extends Controller
{
    public function __construct(
        protected ExportService $exportService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $exports = $this->exportService->getUserExports($request->user());

        return response()->json([
            'data' => ExportResource::collection($exports),
        ], Response::HTTP_OK);
    }

    public function store(StoreExportRequest $request): JsonResponse
    {
        $export = $this->exportService->createExport($request->user(), $request->validated());

        return response()->json([
            'message' => 'Permintaan ekspor laporan berhasil dibuat dan sedang diproses.',
            'data' => new ExportResource($export),
        ], Response::HTTP_ACCEPTED);
    }

    public function show(Request $request, Export $export): JsonResponse
    {
        $this->authorize('view', $export);

        return response()->json([
            'data' => new ExportResource($export),
        ], Response::HTTP_OK);
    }

    public function download(Request $request, Export $export): StreamedResponse
    {
        $this->authorize('download', $export);

        return $this->exportService->downloadExport($export);
    }
}
