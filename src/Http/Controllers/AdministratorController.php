<?php

namespace HolartWeb\AxoraCMS\Http\Controllers;

use HolartWeb\AxoraCMS\Enums\AdminRole;
use HolartWeb\AxoraCMS\Models\TAdministrator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AdministratorController extends Controller
{
    /**
     * Display a listing of administrators.
     */
    public function index(): JsonResponse
    {
        $administrators = TAdministrator::all();

        return response()->json($administrators);
    }

    /**
     * Store a newly created administrator.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:t_administrators,email',
            'password' => 'required|string|min:8',
            'role' => 'required|in:super_admin,administrator,manager',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $current = $request->user('admin');
        $targetRole = AdminRole::from($request->role);

        if (! $current || ! $current->role->canAssignRole($targetRole)) {
            return response()->json(['message' => 'Недостаточно прав для назначения этой роли'], 403);
        }

        $administrator = TAdministrator::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'is_active' => $request->is_active ?? true,
        ]);

        return response()->json($administrator, 201);
    }

    /**
     * Update the specified administrator.
     */
    public function update(Request $request, int|string $id): JsonResponse
    {
        $administrator = TAdministrator::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|string|email|max:255|unique:t_administrators,email,'.$id,
            'password' => 'sometimes|nullable|string|min:8',
            'role' => 'sometimes|required|in:super_admin,administrator,manager',
            'is_active' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $current = $request->user('admin');
        $isSelf = $current && (int) $current->id === (int) $administrator->id;

        // Nobody may change their own role or active flag.
        if ($isSelf && ($request->filled('role') || $request->has('is_active'))) {
            return response()->json(['message' => 'Нельзя изменить собственную роль или статус активности'], 403);
        }

        // A role may only be assigned if the current admin outranks it, and the
        // target administrator's existing role must also be assignable by them.
        if ($request->filled('role')) {
            $targetRole = AdminRole::from($request->role);

            if (! $current
                || ! $current->role->canAssignRole($targetRole)
                || ! $current->role->canAssignRole($administrator->role)
            ) {
                return response()->json(['message' => 'Недостаточно прав для назначения этой роли'], 403);
            }
        }

        $data = $request->only(['name', 'email', 'role', 'is_active']);

        if ($isSelf) {
            unset($data['role'], $data['is_active']);
        }

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $administrator->update($data);

        return response()->json($administrator);
    }

    /**
     * Remove the specified administrator.
     */
    public function destroy(Request $request, int|string $id): JsonResponse
    {
        $administrator = TAdministrator::findOrFail($id);

        // Prevent deleting yourself
        if ((int) $administrator->id === (int) $request->user('admin')?->id) {
            return response()->json(['error' => 'Вы не можете удалить себя'], 403);
        }

        // Never allow removing the last active super administrator.
        if ($administrator->role === AdminRole::SUPER_ADMIN) {
            $remainingSuperAdmins = TAdministrator::where('role', AdminRole::SUPER_ADMIN->value)
                ->where('is_active', true)
                ->where('id', '!=', $administrator->id)
                ->count();

            if ($remainingSuperAdmins === 0) {
                return response()->json(['error' => 'Нельзя удалить последнего супер-администратора'], 403);
            }
        }

        $administrator->delete();

        return response()->json(['message' => 'Администратор удален'], 200);
    }
}
