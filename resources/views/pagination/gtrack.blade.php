{{-- The pagination bar for every list in the app (History, Store, Capital,
     Balance, User Management). Set as the default view in AppServiceProvider.

     Laravel's stock view shows only Previous/Next on a phone and hides the
     counts, which is the size this app is used at. This one shows "Showing
     26–50 of 120" and a compact window of page numbers (first, last, and one
     either side of the current page) at every width.

     $paginator->url() carries the query string because each controller calls
     withQueryString(), so the page links keep every active filter. --}}

@if ($paginator->total() > 0)
    @php
        $current = $paginator->currentPage();
        $last = $paginator->lastPage();

        $pages = collect([1, $last, $current - 1, $current, $current + 1])
            ->filter(fn ($page) => $page >= 1 && $page <= $last)
            ->unique()
            ->sort()
            ->values();

        $box = 'inline-flex items-center justify-center min-w-9 h-9 px-2 rounded-lg border text-sm font-semibold tabular-nums transition';
    @endphp

    <nav role="navigation" aria-label="Pagination" class="flex flex-col items-center gap-3 sm:flex-row sm:justify-between">
        <p class="text-sm text-gray-600">Showing <span class="font-semibold text-gray-900 tabular-nums">{{ $paginator->firstItem() }}–{{ $paginator->lastItem() }}</span> of <span class="font-semibold text-gray-900 tabular-nums">{{ $paginator->total() }}</span></p>

        @if ($paginator->hasPages())
            <div class="flex items-center gap-1">
                {{-- Previous --}}
                @if ($paginator->onFirstPage())
                    <span aria-disabled="true" aria-label="Previous page"
                          class="{{ $box }} border-gray-200 text-gray-300 cursor-not-allowed">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/></svg>
                    </span>
                @else
                    <a href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Previous page"
                       class="{{ $box }} border-gray-300 bg-surface text-gray-700 hover:bg-gray-50 active:scale-95">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/></svg>
                    </a>
                @endif

                {{-- Page numbers, with an ellipsis wherever pages were skipped --}}
                @foreach ($pages as $index => $page)
                    @if ($index > 0 && $page - $pages[$index - 1] > 1)
                        <span class="px-1 text-gray-400" aria-hidden="true">…</span>
                    @endif

                    @if ($page === $current)
                        <span aria-current="page" class="{{ $box }} border-gcash-600 bg-gcash-600 text-white">{{ $page }}</span>
                    @else
                        <a href="{{ $paginator->url($page) }}" aria-label="Go to page {{ $page }}"
                           class="{{ $box }} border-gray-300 bg-surface text-gray-700 hover:bg-gray-50 active:scale-95">{{ $page }}</a>
                    @endif
                @endforeach

                {{-- Next --}}
                @if ($paginator->hasMorePages())
                    <a href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Next page"
                       class="{{ $box }} border-gray-300 bg-surface text-gray-700 hover:bg-gray-50 active:scale-95">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
                    </a>
                @else
                    <span aria-disabled="true" aria-label="Next page"
                          class="{{ $box }} border-gray-200 text-gray-300 cursor-not-allowed">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
                    </span>
                @endif
            </div>
        @endif
    </nav>
@endif
