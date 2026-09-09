<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HistoryController extends Controller
{
    /**
     * Filterable, paginated transaction history.
     *
     * The summary tiles are computed over the whole filtered set, not just the
     * page on screen — a total that only covered page 1 would be actively
     * misleading.
     */
    public function index(Request $request): View
    {
        $filters = $this->filters($request);

        $query = Transaction::query()->with('user');
        $this->applyFilters($query, $filters);

        $summary = [
            'cash_in' => (float) (clone $query)->where('type', Transaction::TYPE_CASH_IN)->sum('amount'),
            'cash_out' => (float) (clone $query)->where('type', Transaction::TYPE_CASH_OUT)->sum('amount'),
            'service_charge' => (float) (clone $query)->sum('service_charge'),
            'count' => (clone $query)->count(),
        ];

        $transactions = $query
            ->latest('created_at')
            ->latest('id')
            ->paginate(25)
            ->withQueryString();

        return view('history', compact('transactions', 'summary', 'filters'));
    }

    /**
     * @return array{range: string, type: string, search: ?string, from: ?string, to: ?string}
     */
    private function filters(Request $request): array
    {
        $validated = $request->validate([
            'range' => ['nullable', 'in:today,week,month,all,custom'],
            'type' => ['nullable', 'in:all,cash_in,cash_out'],
            'search' => ['nullable', 'string', 'max:50'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        return [
            'range' => $validated['range'] ?? 'today',
            'type' => $validated['type'] ?? 'all',
            'search' => $validated['search'] ?? null,
            'from' => $validated['from'] ?? null,
            'to' => $validated['to'] ?? null,
        ];
    }

    private function applyFilters(Builder $query, array $filters): void
    {
        match ($filters['range']) {
            'today' => $query->whereDate('created_at', today()),
            'week' => $query->where('created_at', '>=', now()->startOfWeek()),
            'month' => $query->where('created_at', '>=', now()->startOfMonth()),
            'custom' => $this->applyCustomRange($query, $filters),
            default => null,
        };

        if ($filters['type'] !== 'all') {
            $query->where('type', $filters['type']);
        }

        if ($filters['search']) {
            $search = $filters['search'];

            $query->where(function (Builder $q) use ($search) {
                $q->where('reference_number', 'like', "%{$search}%")
                    ->orWhere('mobile_number', 'like', "%{$search}%");
            });
        }
    }

    private function applyCustomRange(Builder $query, array $filters): void
    {
        if ($filters['from']) {
            $query->whereDate('created_at', '>=', $filters['from']);
        }

        if ($filters['to']) {
            $query->whereDate('created_at', '<=', $filters['to']);
        }
    }
}
