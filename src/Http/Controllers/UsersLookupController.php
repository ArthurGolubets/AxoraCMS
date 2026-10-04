<?php

namespace HolartWeb\AxoraCMS\Http\Controllers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Schema;

/**
 * Site users lookup for "user" fields (infoblocks, custom forms).
 * Exposes only id / name / email.
 */
class UsersLookupController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $model = $this->userModel();
        if (! $model) {
            return response()->json(['data' => []]);
        }

        $table = $model->getTable();
        $hasName = Schema::hasColumn($table, 'name');

        $users = $model->newQuery()
            ->when($request->filled('search'), function ($query) use ($request, $hasName) {
                $search = '%'.$request->get('search').'%';
                $query->where(function ($q) use ($search, $hasName) {
                    $q->where('email', 'like', $search);
                    if ($hasName) {
                        $q->orWhere('name', 'like', $search);
                    }
                });
            })
            ->orderBy($model->getKeyName())
            ->limit(min((int) $request->get('per_page', 20), 50))
            ->get()
            ->map(fn (Model $user) => $this->present($user));

        return response()->json(['data' => $users]);
    }

    public function show(int $id): JsonResponse
    {
        $user = $this->userModel()?->newQuery()->findOrFail($id);
        abort_unless($user, 404);

        return response()->json($this->present($user));
    }

    protected function userModel(): ?Model
    {
        $class = config('auth.providers.users.model');

        if (! $class || ! class_exists($class)) {
            return null;
        }

        $model = new $class;

        return Schema::hasTable($model->getTable()) ? $model : null;
    }

    /**
     * @return array{id: mixed, name: ?string, email: ?string}
     */
    protected function present(Model $user): array
    {
        return [
            'id' => $user->getKey(),
            'name' => $user->getAttribute('name'),
            'email' => $user->getAttribute('email'),
        ];
    }
}
