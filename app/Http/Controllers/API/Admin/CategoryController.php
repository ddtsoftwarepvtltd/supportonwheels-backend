<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Models\ServiceCategory;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    use ApiResponse;

    public function index(): JsonResponse
    {
        return $this->success(ServiceCategory::withCount('services')->orderBy('sort_order')->get());
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name'       => 'required|string|max:100|unique:service_categories',
            'icon'       => 'required|string|max:10',
            'color'      => 'sometimes|string|max:10',
            'sort_order' => 'sometimes|integer',
        ]);

        $category = ServiceCategory::create($request->only('name', 'icon', 'color', 'sort_order') + ['is_active' => true]);
        return $this->success($category, 'Category create ho gaya', 201);
    }

    public function show($id): JsonResponse
    {
        $category = ServiceCategory::withCount('services')->find($id);
        return $category ? $this->success($category) : $this->error('Category nahi mili', 404);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $category = ServiceCategory::find($id);
        if (!$category) return $this->error('Category nahi mili', 404);

        $request->validate([
            'name'       => 'sometimes|string|max:100|unique:service_categories,name,' . $id,
            'icon'       => 'sometimes|string|max:10',
            'is_active'  => 'sometimes|boolean',
            'sort_order' => 'sometimes|integer',
        ]);

        $category->update($request->only('name', 'icon', 'color', 'is_active', 'sort_order'));
        return $this->success($category, 'Category update ho gaya');
    }

    public function destroy($id): JsonResponse
    {
        $category = ServiceCategory::find($id);
        if (!$category) return $this->error('Category nahi mili', 404);
        $category->update(['is_active' => false]);
        return $this->success(null, 'Category deactivate ho gaya');
    }
}
