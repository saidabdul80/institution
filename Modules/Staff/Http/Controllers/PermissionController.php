<?php

namespace Modules\Staff\Http\Controllers;

use App\Http\Resources\APIResource;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use App\Models\User;
use Exception;

class PermissionController extends Controller
{
    /**
     * Get all permissions organized by category
     */
    public function getPermissions()
    {
        try {
            $permissions = config('permissions.permissions', []);
            $result = [];

            foreach ($permissions as $category => $categoryPermissions) {
                $result[] = [
                    'category' => $category,
                    'name' => ucwords(str_replace('_', ' ', $category)),
                    'permissions' => collect($categoryPermissions)->map(function ($description, $permission) {
                        return [
                            'name' => $permission,
                            'description' => $description,
                            'exists' => Permission::where('name', $permission)->exists()
                        ];
                    })->values()
                ];
            }

            return new APIResource($result, false, 200);
        } catch (Exception $e) {
            return new APIResource($e->getMessage(), true, 500);
        }
    }

    /**
     * Get all roles with their permissions
     */
    public function getRoles()
    {
        try {
            $roles = Role::with('permissions')->get()->map(function ($role) {
                return [
                    'id' => $role->id,
                    'name' => $role->name,
                    'permissions_count' => $role->permissions->count(),
                    'permissions' => $role->permissions->pluck('name'),
                    'users_count' => $role->users()->count()
                ];
            });

            return new APIResource($roles, false, 200);
        } catch (Exception $e) {
            return new APIResource($e->getMessage(), true, 500);
        }
    }

    /**
     * Update permissions and roles from configuration
     */
    public function updatePermissions(Request $request)
    {
        try {
            $fresh = $request->get('fresh', false);

            $options = $fresh ? ['--fresh' => true] : [];

            // Run the permissions update command
            Artisan::call('permissions:update', $options);

            $output = Artisan::output();

            return new APIResource([
                'message' => 'Permissions updated successfully',
                'output' => $output
            ], false, 200);

        } catch (Exception $e) {
            return new APIResource($e->getMessage(), true, 500);
        }
    }

    /**
     * Assign role to user
     */
    public function assignRole(Request $request)
    {
        try {
            $request->validate([
                'user_id' => 'required|exists:users,id',
                'role_name' => 'required|exists:roles,name'
            ]);

            $user = User::findOrFail($request->user_id);
            $user->assignRole($request->role_name);

            return new APIResource([
                'message' => "Role '{$request->role_name}' assigned to user successfully",
                'user' => $user->load('roles')
            ], false, 200);

        } catch (Exception $e) {
            return new APIResource($e->getMessage(), true, 500);
        }
    }

    /**
     * Remove role from user
     */
    public function removeRole(Request $request)
    {
        try {
            $request->validate([
                'user_id' => 'required|exists:users,id',
                'role_name' => 'required|exists:roles,name'
            ]);

            $user = User::findOrFail($request->user_id);
            $user->removeRole($request->role_name);

            return new APIResource([
                'message' => "Role '{$request->role_name}' removed from user successfully",
                'user' => $user->load('roles')
            ], false, 200);

        } catch (Exception $e) {
            return new APIResource($e->getMessage(), true, 500);
        }
    }

    /**
     * Get users with their roles
     */
    public function getUsersWithRoles()
    {
        try {
            $users = User::with('roles')->get()->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'roles' => $user->roles->pluck('name'),
                    'permissions' => $user->getAllPermissions()->pluck('name')
                ];
            });

            return new APIResource($users, false, 200);
        } catch (Exception $e) {
            return new APIResource($e->getMessage(), true, 500);
        }
    }

    /**
     * Check if user has permission
     */
    public function checkPermission(Request $request)
    {
        try {
            $request->validate([
                'user_id' => 'required|exists:users,id',
                'permission' => 'required|string'
            ]);

            $user = User::findOrFail($request->user_id);
            $hasPermission = $user->can($request->permission);

            return new APIResource([
                'user_id' => $user->id,
                'permission' => $request->permission,
                'has_permission' => $hasPermission
            ], false, 200);

        } catch (Exception $e) {
            return new APIResource($e->getMessage(), true, 500);
        }
    }
}
