<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        return view('libraries.transactions.index');
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

        $query = Transaction::query()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%");
                });
            })
            ->orderBy('workflow_order')
            ->orderBy('name');

        $paginator = $query->paginate($perPage, ['*'], 'page', $page);

        $data = $paginator->getCollection()->map(function (Transaction $t) {
            return [
                'id' => $t->id,
                'name' => $t->name,
                'code' => $t->code,
                'description' => $t->description,
                'workflow_order' => $t->workflow_order,
                'is_active' => (bool) $t->is_active,
                'transfer_allowed' => (bool) $t->transfer_allowed,
                'priority_enabled' => (bool) $t->priority_enabled,
                'created_at' => $t->created_at?->format('Y-m-d H:i'),
                'updated_at' => $t->updated_at?->format('Y-m-d H:i'),
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
            'name' => ['required', 'string', 'max:150'],
            'code' => ['required', 'string', 'max:20', 'unique:transactions,code'],
            'description' => ['nullable', 'string'],
            'workflow_order' => ['nullable', 'integer', 'min:1'],
            'is_active' => ['nullable', 'boolean'],
            'transfer_allowed' => ['nullable', 'boolean'],
            'priority_enabled' => ['nullable', 'boolean'],
        ]);

        try {
            DB::transaction(function () use ($request, $validated) {
                $transaction = new Transaction;
                $transaction->name = $validated['name'];
                $transaction->code = $validated['code'];
                $transaction->description = $validated['description'] ?? null;
                $transaction->workflow_order = $validated['workflow_order'] ?? 1;
                $transaction->is_active = (bool) ($validated['is_active'] ?? false);
                $transaction->transfer_allowed = (bool) ($validated['transfer_allowed'] ?? false);
                $transaction->priority_enabled = (bool) ($validated['priority_enabled'] ?? false);
                $transaction->created_by = $request->user()?->id;
                $transaction->updated_by = $request->user()?->id;
                $transaction->save();
            });
        } catch (\Throwable $e) {
            return redirect()->route('libraries.transaction-types')->with('error', 'Failed to create transaction type.');
        }

        return redirect()->route('libraries.transaction-types')->with('success', 'Transaction type created.');
    }

    public function update(Request $request, Transaction $transaction)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'code' => [
                'required',
                'string',
                'max:20',
                Rule::unique('transactions', 'code')->ignore($transaction->id),
            ],
            'description' => ['nullable', 'string'],
            'workflow_order' => ['nullable', 'integer', 'min:1'],
            'is_active' => ['nullable', 'boolean'],
            'transfer_allowed' => ['nullable', 'boolean'],
            'priority_enabled' => ['nullable', 'boolean'],
        ]);

        try {
            DB::transaction(function () use ($request, $transaction, $validated) {
                $transaction->name = $validated['name'];
                $transaction->code = $validated['code'];
                $transaction->description = $validated['description'] ?? null;
                $transaction->workflow_order = $validated['workflow_order'] ?? 1;
                $transaction->is_active = (bool) ($validated['is_active'] ?? false);
                $transaction->transfer_allowed = (bool) ($validated['transfer_allowed'] ?? false);
                $transaction->priority_enabled = (bool) ($validated['priority_enabled'] ?? false);
                $transaction->updated_by = $request->user()?->id;
                $transaction->save();
            });
        } catch (\Throwable $e) {
            return redirect()->route('libraries.transaction-types')->with('error', 'Failed to update transaction type.');
        }

        return redirect()->route('libraries.transaction-types')->with('success', 'Transaction type updated.');
    }

    public function destroy(Transaction $transaction)
    {
        try {
            DB::transaction(function () use ($transaction) {
                $transaction->delete();
            });
        } catch (\Throwable $e) {
            return redirect()->route('libraries.transaction-types')->with('error', 'Failed to delete transaction type.');
        }

        return redirect()->route('libraries.transaction-types')->with('success', 'Transaction type deleted.');
    }
}
