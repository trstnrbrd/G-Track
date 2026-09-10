<?php

namespace App\Http\Controllers;

use App\Models\DailySession;
use App\Models\Transaction;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as PdfDocument; // aliased: PHP class names are case-insensitive, so `PDF` collides with the `Pdf` facade
use Carbon\CarbonInterface;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ExportController extends Controller
{
    /**
     * PDF of the History page — exactly the rows its current filters show.
     */
    public function transactions(Request $request): Response
    {
        $filters = $request->validate([
            'range' => ['nullable', 'in:today,week,month,all,custom'],
            'type' => ['nullable', 'in:all,cash_in,cash_out'],
            'search' => ['nullable', 'string', 'max:50'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $query = Transaction::query();
        $this->applyTransactionFilters($query, $filters);

        // Totals cover EVERY matching row, even when the table below is capped —
        // a total that silently stopped at the cap would be wrong.
        $summary = [
            'cash_in' => (float) (clone $query)->where('type', Transaction::TYPE_CASH_IN)->sum('amount'),
            'cash_out' => (float) (clone $query)->where('type', Transaction::TYPE_CASH_OUT)->sum('amount'),
            'service_charge' => (float) (clone $query)->sum('service_charge'),
            'count' => (clone $query)->count(),
        ];

        $rows = (clone $query)
            ->with('user')
            ->orderBy('created_at')
            ->orderBy('id')
            ->limit($this->maxRows())
            ->get();

        return $this->pdf('exports.transactions', [
            'title' => 'Transaction History',
            'scope' => $this->describeTransactionScope($filters),
            'rows' => $rows,
            'summary' => $summary,
        ], 'gtrack-transactions-'.now()->format('Y-m-d').'.pdf');
    }

    /**
     * PDF of the Balance page — one row per day in the selected range.
     */
    public function sessions(Request $request): Response
    {
        $range = $request->validate([
            'range' => ['nullable', 'in:week,month,last30,all'],
        ])['range'] ?? 'week';

        $inRange = DailySession::query();

        match ($range) {
            'week' => $inRange->where('started_at', '>=', now()->startOfWeek()),
            'month' => $inRange->where('started_at', '>=', now()->startOfMonth()),
            'last30' => $inRange->where('started_at', '>=', now()->subDays(30)),
            default => null,
        };

        $sessionIds = (clone $inRange)->select('id');

        $totals = [
            'days' => (clone $inRange)->count(),
            'transactions' => Transaction::whereIn('daily_session_id', $sessionIds)->count(),
            'earned' => (float) Transaction::whereIn('daily_session_id', $sessionIds)->sum('service_charge'),
        ];

        $rows = (clone $inRange)
            ->withCount('transactions')
            ->withSum('transactions', 'service_charge')
            ->orderBy('started_at')
            ->limit($this->maxRows())
            ->get();

        return $this->pdf('exports.sessions', [
            'title' => 'Daily Balance Summary',
            'scope' => match ($range) {
                'week' => 'This week · '.$this->span(now()->startOfWeek(), now()),
                'month' => 'This month · '.now()->format('F Y'),
                'last30' => 'Last 30 days · '.$this->span(now()->subDays(30), now()),
                default => 'All time',
            },
            'rows' => $rows,
            'totals' => $totals,
        ], 'gtrack-daily-balances-'.now()->format('Y-m-d').'.pdf');
    }

    /**
     * The most table rows a PDF will lay out. See config/gtrack.php for the
     * memory measurements behind the number.
     */
    private function maxRows(): int
    {
        return (int) config('gtrack.export_max_rows');
    }

    private function pdf(string $view, array $data, string $filename): Response
    {
        $pdf = Pdf::loadView($view, $data + [
            'maxRows' => $this->maxRows(),
            'generatedAt' => now(),
            'generatedBy' => auth()->user()?->name,
        ])
            ->setPaper('a4', 'landscape')
            // Embed only the glyphs actually used. Without this, dompdf embeds
            // the whole DejaVu font family and a one-page report is 1.1 MB.
            ->setOption('isFontSubsettingEnabled', true);

        $this->stampPageNumbers($pdf);

        return $pdf->download($filename);
    }

    /**
     * "Page X of Y" at the right of the footer.
     *
     * CSS `counter(pages)` prints "of 0" in dompdf, because the total isn't
     * known until layout finishes. So the page is laid out first, then this
     * stamps every page — dompdf fills in {PAGE_NUM} and {PAGE_COUNT} itself.
     */
    private function stampPageNumbers(PdfDocument $pdf): void
    {
        $pdf->render();

        $dompdf = $pdf->getDomPDF();
        $canvas = $dompdf->getCanvas();
        $metrics = $dompdf->getFontMetrics();
        $font = $metrics->getFont('DejaVu Sans');
        $size = 7.5;
        $mm = 72 / 25.4;

        // page_text can't right-align, so measure a typical label and place it
        // against the 14mm page margin. Exact up to 9 pages; beyond that it
        // runs a few points into the margin, still on the page.
        $width = $metrics->getTextWidth('Page 9 of 9', $font, $size);

        $canvas->page_text(
            $canvas->get_width() - 14 * $mm - $width,
            $canvas->get_height() - 11.1 * $mm,  // on the same line as the footer text
            'Page {PAGE_NUM} of {PAGE_COUNT}',
            $font,
            $size,
            [0.612, 0.639, 0.686],               // #9CA3AF, matching the footer
        );
    }

    /**
     * A plain sentence for the PDF header saying what the report covers, so a
     * printed copy still makes sense on its own.
     */
    private function describeTransactionScope(array $filters): string
    {
        $parts = [match ($filters['range'] ?? 'today') {
            'today' => 'Today · '.now()->format('M j, Y'),
            'week' => 'This week · '.$this->span(now()->startOfWeek(), now()),
            'month' => 'This month · '.now()->format('F Y'),
            'custom' => $this->span(
                isset($filters['from']) ? now()->parse($filters['from']) : null,
                isset($filters['to']) ? now()->parse($filters['to']) : null,
            ),
            default => 'All time',
        }];

        $parts[] = match ($filters['type'] ?? 'all') {
            'cash_in' => 'Cash in only',
            'cash_out' => 'Cash out only',
            default => 'All types',
        };

        if (! empty($filters['search'])) {
            $parts[] = 'Matching "'.$filters['search'].'"';
        }

        return implode(' · ', $parts);
    }

    private function span(?CarbonInterface $from, ?CarbonInterface $to): string
    {
        return match (true) {
            $from && $to => $from->format('M j').' – '.$to->format('M j, Y'),
            (bool) $from => 'From '.$from->format('M j, Y'),
            (bool) $to => 'Up to '.$to->format('M j, Y'),
            default => 'All time',
        };
    }

    private function applyTransactionFilters(Builder $query, array $filters): void
    {
        match ($filters['range'] ?? 'today') {
            'today' => $query->whereDate('created_at', today()),
            'week' => $query->where('created_at', '>=', now()->startOfWeek()),
            'month' => $query->where('created_at', '>=', now()->startOfMonth()),
            'custom' => $this->applyCustomRange($query, $filters),
            default => null,
        };

        if (($filters['type'] ?? 'all') !== 'all') {
            $query->where('type', $filters['type']);
        }

        if ($search = $filters['search'] ?? null) {
            $query->where(function (Builder $q) use ($search) {
                $q->where('reference_number', 'like', "%{$search}%")
                    ->orWhere('mobile_number', 'like', "%{$search}%");
            });
        }
    }

    private function applyCustomRange(Builder $query, array $filters): void
    {
        if ($filters['from'] ?? null) {
            $query->whereDate('created_at', '>=', $filters['from']);
        }

        if ($filters['to'] ?? null) {
            $query->whereDate('created_at', '<=', $filters['to']);
        }
    }
}
