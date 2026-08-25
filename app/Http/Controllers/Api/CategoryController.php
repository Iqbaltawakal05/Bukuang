<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Category\StoreCategoryRequest;
use App\Http\Requests\Category\UpdateCategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use App\Services\CategoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CategoryController extends Controller
{
    public function __construct(
        protected CategoryService $categoryService
    ) {}

    /**
     * Display a listing of categories (System default + User custom).
     */
    public function index(Request $request): JsonResponse
    {
        $type = $request->query('type');
        $categories = $this->categoryService->getCategoriesForUser($request->user(), $type);

        return response()->json([
            'data' => CategoryResource::collection($categories),
        ], Response::HTTP_OK);
    }

    /**
     * Store a newly created custom category in storage.
     */
    public function store(StoreCategoryRequest $request): JsonResponse
    {
        $category = $this->categoryService->createCategory($request->user(), $request->validated());

        return response()->json([
            'message' => 'Kategori berhasil dibuat.',
            'data' => new CategoryResource($category),
        ], Response::HTTP_CREATED);
    }

    /**
     * Display the specified category.
     */
    public function show(Request $request, Category $category): JsonResponse
    {
        $this->authorize('view', $category);

        return response()->json([
            'data' => new CategoryResource($category),
        ], Response::HTTP_OK);
    }

    /**
     * Update the specified custom category in storage.
     */
    public function update(UpdateCategoryRequest $request, Category $category): JsonResponse
    {
        $this->authorize('update', $category);

        $updatedCategory = $this->categoryService->updateCategory($category, $request->validated());

        return response()->json([
            'message' => 'Kategori berhasil diperbarui.',
            'data' => new CategoryResource($updatedCategory),
        ], Response::HTTP_OK);
    }

    /**
     * Remove the specified custom category from storage.
     */
    public function destroy(Request $request, Category $category): JsonResponse
    {
        $this->authorize('delete', $category);

        $this->categoryService->deleteCategory($category);

        return response()->json([
            'message' => 'Kategori berhasil dihapus.',
        ], Response::HTTP_OK);
    }
}
