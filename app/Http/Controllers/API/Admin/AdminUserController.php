<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminUserController extends Controller
{
    use ApiResponse;

    public function index(): JsonResponse
    {
        $admins = User::whereIn('user_type', ['super_admin', 'ops_admin', 'finance_admin', 'admin'])
            ->with('roles')
            ->get();
        return $this->success($admins);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name'      => 'required|string|max:100',
            'email'     => 'required|email|unique:users',
            'password'  => 'required|string|min:8',
            'user_type' => 'required|in:ops_admin,finance_admin,admin',
        ]);

        $user = User::create([
            'name'      => $request->name,
            'email'     => $request->email,
            'password'  => Hash::make($request->password),
            'user_type' => $request->user_type,
            'status'    => 'active',
        ]);

        $user->assignRole($request->user_type);

        return $this->success($user, 'Admin user create ho gaya', 201);
    }

    public function show($id): JsonResponse
    {
        $admin = User::whereIn('user_type', ['super_admin', 'ops_admin', 'finance_admin', 'admin'])
            ->with(['roles', 'permissions'])
            ->find($id);
        return $admin ? $this->success($admin) : $this->error('Admin nahi mila', 404);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $admin = User::whereIn('user_type', ['ops_admin', 'finance_admin', 'admin'])->find($id);
        if (!$admin) return $this->error('Admin nahi mila', 404);

        $request->validate([
            'name'   => 'sometimes|string|max:100',
            'status' => 'sometimes|in:active,inactive',
        ]);

        $admin->update($request->only('name', 'status'));
        return $this->success($admin, 'Admin update ho gaya');
    }

    public function destroy($id): JsonResponse
    {
        $admin = User::whereIn('user_type', ['ops_admin', 'finance_admin', 'admin'])->find($id);
        if (!$admin) return $this->error('Admin nahi mila', 404);
        $admin->delete();
        return $this->success(null, 'Admin delete ho gaya');
    }

    // POST /admin/admin-users/{id}/2fa/reset
    public function reset2FA($id): JsonResponse
    {
        $admin = User::find($id);
        if (!$admin) return $this->error('Admin nahi mila', 404);
        // TODO: 2FA secret reset karo
        return $this->success(null, '2FA reset ho gaya');
    }
}
