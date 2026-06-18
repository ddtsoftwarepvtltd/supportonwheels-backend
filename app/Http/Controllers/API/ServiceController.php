<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Service;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Service::active();

        if ($request->filled('search')) {
            $q = $request->search;
            $query->where(function ($q2) use ($q) {
                $q2->where('title', 'like', "%$q%")
                   ->orWhere('tag', 'like', "%$q%");
            });
        }

        if ($request->filled('tag')) {
            $query->where('tag', $request->tag);
        }

        return response()->json([
            'success' => true,
            'data'    => $query->get(),
        ]);
    }

    public function show(Service $service): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => $service,
        ]);
    }
}
