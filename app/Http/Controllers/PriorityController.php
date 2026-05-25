<?php

namespace App\Http\Controllers;

use App\Models\Priority;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PriorityController extends Controller
{
    public function index(Request $request)
    {
        return view('libraries.priorities.index');
    }

    public function data(Request $request)
    {
        $perPage = (int) $request->query('per_page', 10);
        $allowedPerPage = [10, 25, 50, 100];
        if (! in_array($perPage, $allowedPerPage, true)) {
            $perPage = 10;
        }

        $page = max(1, (int) $request->query('page', 1));
        $search = trim((string) $request->query('q', ''));

        $query = Priority::query()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%");
                });
            })
            ->orderBy('priority_level')
            ->orderBy('name');

        $paginator = $query->paginate($perPage, ['*'], 'page', $page);

        $data = $paginator->getCollection()->map(function (Priority $p) {
            return [
                'id' => $p->id,
                'name' => $p->name,
                'code' => $p->code,
                'priority_level' => $p->priority_level,
                'is_active' => (bool) $p->is_active,
                'created_at' => $p->created_at?->format('Y-m-d H:i'),
                'updated_at' => $p->updated_at?->format('Y-m-d H:i'),
            ];
        })->values();

        return response()->json([
            'data' => $data,
            'meta' => [
                'page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'code' => ['required', 'string', 'max:20', 'unique:priorities,code'],
            'priority_level' => ['nullable', 'integer', 'min:1'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        try {
            DB::transaction(function () use ($validated) {
                $priority = new Priority;
                $priority->name = $validated['name'];
                $priority->code = $validated['code'];
                $priority->priority_level = $validated['priority_level'] ?? 1;
                $priority->is_active = (bool) ($validated['is_active'] ?? false);
                $priority->save();
            });
        } catch (\Throwable $e) {
            return redirect()->route('libraries.priorities')->with('error', 'Failed to create priority.');
        }

        return redirect()->route('libraries.priorities')->with('success', 'Priority created.');
    }

    public function update(Request $request, Priority $priority)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'code' => [
                'required',
                'string',
                'max:20',
                Rule::unique('priorities', 'code')->ignore($priority->id),
            ],
            'priority_level' => ['nullable', 'integer', 'min:1'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        try {
            DB::transaction(function () use ($priority, $validated) {
                $priority->name = $validated['name'];
                $priority->code = $validated['code'];
                $priority->priority_level = $validated['priority_level'] ?? 1;
                $priority->is_active = (bool) ($validated['is_active'] ?? false);
                $priority->save();
            });
        } catch (\Throwable $e) {
            return redirect()->route('libraries.priorities')->with('error', 'Failed to update priority.');
        }

        return redirect()->route('libraries.priorities')->with('success', 'Priority updated.');
    }

    public function destroy(Priority $priority)
    {
        try {
            DB::transaction(function () use ($priority) {
                $priority->delete();
            });
        } catch (\Throwable $e) {
            return redirect()->route('libraries.priorities')->with('error', 'Failed to delete priority.');
        }

        return redirect()->route('libraries.priorities')->with('success', 'Priority deleted.');
    }
}
