<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Permission;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Artisan;

class CheckPermission
{
    /**
     * Handle an incoming request.
     * API ke liye: Inertia hata diya, JSON response deta hai
     */
    public function handle(Request $request, Closure $next, $permission = null)
    {
        // ── 1. Auth check ──────────────────────────────────────────────
        if (!Auth::check()) {
            return $this->unauthorizedResponse($request, 'Unauthenticated.');
        }

        $user = auth()->user();

        // ── 2. Route name resolve karo ─────────────────────────────────
        $routeName = $request->route()?->getName();

        if (!$routeName) {
            return $this->unauthorizedResponse($request, 'Route name not defined.');
        }

        // Sirf admin. prefix wali routes check karo
        if (preg_match('/^v\d+\.admin\.(.+)$/', $routeName, $matches)) {
            $routeName = $matches[1];
        } else {
            return $next($request);
        }

        $permissionToCheck = $this->resolvePermission($routeName, $request);


        $this->createPermissionWithParent($permissionToCheck);

        // ── 6. Access check ────────────────────────────────────────────
        if (!$this->hasAccessToPermission($permissionToCheck)) {
            return $this->unauthorizedResponse(
                $request,
                'You are not authorized to access this resource.'
            );
        }

        return $next($request);
    }

    // ────────────────────────────────────────────────────────────────────
    // Permission string resolve karo — route params ke hisaab se
    // ────────────────────────────────────────────────────────────────────

    protected function resolvePermission(string $routeName, Request $request): string
    {
        // GET /user/{role} — list by role
        if ($routeName === 'user_list') {
            $roleSlug = $request->route('role');
            if ($roleSlug) {
                $roleName = str_replace('-', '_', $roleSlug);
                return 'user.' . $roleName;
            }
        }

        // GET /user/{name}/create
        if ($routeName === 'user.create') {
            $roleSlug = $request->route('name');
            if ($roleSlug) {
                $roleName = str_replace('-', '_', $roleSlug);
                return 'user.' . $roleName . '.create';
            }
        }

        // GET /user/{name}/edit/{userId}
        if ($routeName === 'user.edit') {
            $roleSlug = $request->route('name');
            if ($roleSlug) {
                $roleName = str_replace('-', '_', $roleSlug);
                return 'user.' . $roleName . '.edit';
            }
        }

        return $routeName;
    }

    // ────────────────────────────────────────────────────────────────────
    // Permission check
    // ────────────────────────────────────────────────────────────────────

    protected function hasAccessToPermission(string $permission): bool
    {
        $user = Auth::user();

        // dd($user->can($permission),$permission);
        Artisan::call('permission:cache-reset');
        return $user->can($permission);
    }

    // ────────────────────────────────────────────────────────────────────
    // Permission + uske saare parents create karo with parent_id + label
    //
    // Example: "users.create.bulk"
    //   Step 1 → "users"          parent_id: null   label: "Users"
    //   Step 2 → "users.create"   parent_id: id(1)  label: "Users Create"
    //   Step 3 → "users.create.bulk" parent_id: id(2) label: "Users Create Bulk"
    // ────────────────────────────────────────────────────────────────────

    protected function createPermissionWithParent(string $permissionName): void
    {
        $segments      = explode('.', $permissionName);
        $permissionPath = '';
        $parentId      = null;

        foreach ($segments as $segment) {
            $permissionPath = $permissionPath
                ? $permissionPath . '.' . $segment
                : $segment;

            $label = $this->generateLabel($permissionPath);

            // firstOrCreate — duplicate nahi banegi
            $permission = Permission::firstOrCreate(
                ['name' => $permissionPath],
                [
                    'parent_id' => $parentId,
                    'label'     => $label,
                ]
            );

            // Agar already exist karti thi aur parent_id/label null hai — update karo
            $needsUpdate = false;

            if (is_null($permission->parent_id) && !is_null($parentId)) {
                $permission->parent_id = $parentId;
                $needsUpdate = true;
            }

            if (is_null($permission->label)) {
                $permission->label = $label;
                $needsUpdate = true;
            }

            if ($needsUpdate) {
                $permission->save();
            }

            // Super Admin role ko yeh permission de do
            $superAdminRole = Role::where('name', 'super_admin')->first();
            if ($superAdminRole && !$superAdminRole->hasPermissionTo($permissionPath)) {
                $superAdminRole->givePermissionTo($permission);
            }

            // Next iteration ke liye yeh current permission parent banega
            $parentId = $permission->id;
        }
    }

    // ────────────────────────────────────────────────────────────────────
    // Label generator
    // "users.create"  → "Users Create"
    // "user_list"     → "User List"
    // "site-health"   → "Site Health"
    // Sirf LAST segment ka label banata hai, poore path ka nahi
    // ────────────────────────────────────────────────────────────────────

    protected function generateLabel(string $permissionPath): string
    {
        $clean = preg_replace('/[\.\-_]+/', ' ', $permissionPath);
        return ucwords(strtolower(trim($clean)));
    }

    // ────────────────────────────────────────────────────────────────────
    // API + Web dono ke liye unified error response
    // API request → JSON, Web request → abort(403)
    // ────────────────────────────────────────────────────────────────────

    protected function unauthorizedResponse(Request $request, string $message): \Illuminate\Http\Response|\Illuminate\Http\JsonResponse
    {
        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => false,
                'message' => $message,
                'code'    => 403,
            ], 403);
        }

        abort(403, $message);
    }
}
