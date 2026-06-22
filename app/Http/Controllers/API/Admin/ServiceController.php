<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $query = Service::with('category');
        if ($request->filled('category_id')) $query->where('category_id', $request->category_id);
        if ($request->filled('is_active')) $query->where('is_active', $request->boolean('is_active'));
        return $this->paginated($query->latest()->paginate($request->get('per_page', 20)));
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'category_id'             => 'required|exists:service_categories,id',
            'name'                    => 'required|string|max:200',
            'description'             => 'required|string',
            'base_price'              => 'required|numeric|min:0',
            'price_type'              => 'required|in:fixed,per_hour,quote_based',
            'estimated_duration_min'  => 'required|integer|min:15',
            'icon'                    => 'sometimes|string|max:10',
            'color'                   => 'sometimes|string|max:10',
            'tag'                     => 'sometimes|string|max:50',
        ]);

        $service = Service::create($request->all() + ['is_active' => true]);
        return $this->success($service->load('category'), 'Service create ho gaya', 201);
    }

    public function show($id): JsonResponse
    {
        $service = Service::with('category')->find($id);
        return $service ? $this->success($service) : $this->error('Service nahi mili', 404);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $service = Service::find($id);
        if (!$service) return $this->error('Service nahi mili', 404);

        $request->validate([
            'name'       => 'sometimes|string|max:200',
            'base_price' => 'sometimes|numeric|min:0',
            'is_active'  => 'sometimes|boolean',
        ]);

        $service->update($request->all());
        return $this->success($service->fresh('category'), 'Service update ho gaya');
    }

    public function destroy($id): JsonResponse
    {
        $service = Service::find($id);
        if (!$service) return $this->error('Service nahi mili', 404);
        $service->update(['is_active' => false]);
        return $this->success(null, 'Service deactivate ho gaya');
    }

    // PATCH /admin/services/{id}/toggle
    public function toggle($id): JsonResponse
    {
        $service = Service::find($id);
        if (!$service) return $this->error('Service nahi mili', 404);
        $service->update(['is_active' => !$service->is_active]);
        return $this->success(['is_active' => $service->is_active], 'Toggle ho gaya');
    }
}
