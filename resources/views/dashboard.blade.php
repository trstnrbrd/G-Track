<x-app-layout>

    @php
        $hour = (int) now()->format('H');
        $greeting = $hour < 12 ? 'Good morning' : ($hour < 18 ? 'Good afternoon' : 'Good evening');
        $firstName = explode(' ', trim(Auth::user()->name))[0];
        $sessionActive = (bool) $session;

        // Balance figures show the centavos quieter than the pesos, so the part
        // that matters reads first.
        [$gcashPesos, $gcashCentavos] = explode('.', number_format((float) $balance->gcash_balance, 2));
        [$cashPesos, $cashCentavos] = explode('.', number_format((float) $balance->cash_balance, 2));

        // Tray icons for the Cash in / Cash out buttons.
        $iconCashIn = 'M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3';
        $iconCashOut = 'M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5';
    @endphp

    {{-- Hidden form that Start Day / End Day submits (action depends on current state) --}}
    <form id="day-form" method="POST" action="{{ $sessionActive ? route('day.end') : route('day.start') }}" class="hidden">
        @csrf
        <input type="hidden" name="starting_gcash" id="form-starting-gcash">
        <input type="hidden" name="starting_cash" id="form-starting-cash">
    </form>

    {{-- ============================================================ --}}
    {{-- ===============   WEB / DESKTOP VIEW (lg+)   =============== --}}
    {{-- ============================================================ --}}
    <div class="hidden lg:block max-w-7xl mx-auto px-8 py-8">

        {{-- Greeting, date, and the day's state --}}
        <div class="flex items-end justify-between mb-6">
            <div>
                <p class="text-sm text-gray-600">{{ $greeting }}, {{ $firstName }}</p>
                <h1 class="mt-0.5 text-2xl font-semibold tracking-tight text-gray-900">{{ now()->format('l, F j') }}</h1>
            </div>

            <div class="flex items-center gap-4">
                @if ($sessionActive)
                    <p class="flex items-center gap-2 text-sm text-gray-600">
                        <span class="h-2 w-2 rounded-full bg-gcash-600"></span>
                        Day open since {{ $session->started_at->format('g:i A') }}
                        @if ($session->duration())
                            <span class="text-gray-500">· {{ $session->duration() }}</span>
                        @endif
                    </p>
                    {{-- Pressed once a day, and irreversible — so it stays quiet
                         rather than being the loudest thing on the page. --}}
                    <button type="button" onclick="confirmEndDay()"
                            class="h-9 px-3.5 rounded-lg border border-gray-200 bg-surface text-sm font-medium text-red-600 transition hover:bg-gray-50">
                        End day
                    </button>
                @else
                    <p class="flex items-center gap-2 text-sm text-gray-600">
                        <span class="h-2 w-2 rounded-full bg-gray-300"></span>
                        Day not started
                    </p>
                    <button type="button" onclick="confirmStartDay()"
                            class="h-9 px-4 rounded-lg bg-gcash-600 text-sm font-medium text-white transition hover:bg-gcash-700">
                        Start day
                    </button>
                @endif
            </div>
        </div>

        {{-- The two balances: the only large blocks of color on the page --}}
        <div class="grid grid-cols-2 gap-4 mb-4">
            <div class="glass-card glass-gcash rounded-2xl px-6 py-5 text-white">
                <p class="text-xs font-medium uppercase tracking-wide text-white/85">GCash wallet</p>
                <p class="mt-2 text-4xl font-semibold tracking-tight tabular-nums">
                    <span class="text-white/70">₱</span>{{ $gcashPesos }}<span class="text-2xl text-white/60">.{{ $gcashCentavos }}</span>
                </p>
                <p class="mt-2 text-sm text-white/70 tabular-nums">
                    {{ $sessionActive ? 'Opened at ₱'.number_format($session->opening_gcash_balance, 2) : 'Carries over from last close' }}
                </p>
            </div>

            <div class="glass-card glass-cash rounded-2xl px-6 py-5 text-white">
                <p class="text-xs font-medium uppercase tracking-wide text-white/85">Cash on hand</p>
                <p class="mt-2 text-4xl font-semibold tracking-tight tabular-nums">
                    <span class="text-white/70">₱</span>{{ $cashPesos }}<span class="text-2xl text-white/60">.{{ $cashCentavos }}</span>
                </p>
                <p class="mt-2 text-sm text-white/70 tabular-nums">
                    {{ $sessionActive ? 'Opened at ₱'.number_format($session->opening_cash_balance, 2) : 'Counted fresh each morning' }}
                </p>
            </div>
        </div>

        {{-- Today's totals — same component as the History page --}}
        <div class="mb-6">
            @include('partials.stat-cards', ['totals' => $stats])
        </div>

        {{-- Recent activity and the two actions --}}
        <div class="grid grid-cols-3 gap-6 items-start">
            <div class="col-span-2 rounded-xl border border-gray-200 bg-surface">
                <div class="flex items-center justify-between px-5 py-4 border-b border-gray-200">
                    <h2 class="text-[15px] font-semibold text-gray-900">Recent transactions</h2>
                    <a href="{{ route('history') }}" class="text-sm font-medium text-gcash-600 hover:text-gcash-700">View all</a>
                </div>
                @include('partials.recent-transactions', ['recent' => $recent, 'sessionActive' => $sessionActive])
            </div>

            <div class="space-y-3">
                <button type="button" onclick="cashAction('cash_in')"
                        class="flex w-full h-14 items-center justify-center gap-2 rounded-xl bg-cash-600 text-[15px] font-medium text-white transition hover:bg-cash-700">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $iconCashIn }}"/></svg>
                    Cash in
                </button>
                <button type="button" onclick="cashAction('cash_out')"
                        class="flex w-full h-14 items-center justify-center gap-2 rounded-xl bg-gcash-600 text-[15px] font-medium text-white transition hover:bg-gcash-700">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $iconCashOut }}"/></svg>
                    Cash out
                </button>
                @unless ($sessionActive)
                    <p class="px-1 text-sm text-gray-600">Start the day to record transactions.</p>
                @endunless
            </div>
        </div>
    </div>

    {{-- ============================================================ --}}
    {{-- ===============   MOBILE VIEW (below lg)     =============== --}}
    {{-- ============================================================ --}}
    <div class="lg:hidden max-w-md mx-auto min-h-screen bg-canvas px-4 pt-5 pb-safe-nav">

        {{-- Greeting, date, day action --}}
        <div class="flex items-start justify-between gap-3">
            <div>
                <p class="text-sm text-gray-600">{{ $greeting }}, {{ $firstName }}</p>
                <h1 class="mt-0.5 text-xl font-semibold tracking-tight text-gray-900">{{ now()->format('D, M j') }}</h1>
            </div>
            @if ($sessionActive)
                <button type="button" onclick="confirmEndDay()"
                        class="h-9 px-3.5 rounded-lg border border-gray-200 bg-surface text-sm font-medium text-red-600 transition hover:bg-gray-50">
                    End day
                </button>
            @else
                <button type="button" onclick="confirmStartDay()"
                        class="h-9 px-4 rounded-lg bg-gcash-600 text-sm font-medium text-white transition hover:bg-gcash-700">
                    Start day
                </button>
            @endif
        </div>

        <p class="mt-2 flex items-center gap-2 text-[13px] text-gray-600">
            <span class="h-2 w-2 rounded-full {{ $sessionActive ? 'bg-gcash-600' : 'bg-gray-300' }}"></span>
            @if ($sessionActive)
                Day open since {{ $session->started_at->format('g:i A') }}
                @if ($session->duration())<span class="text-gray-500">· {{ $session->duration() }}</span>@endif
            @else
                Day not started
            @endif
        </p>

        {{-- Balances --}}
        <div class="mt-4 grid grid-cols-2 gap-3">
            <div class="glass-card glass-gcash rounded-2xl p-4 text-white">
                <p class="text-[11px] font-medium uppercase tracking-wide text-white/85">GCash wallet</p>
                <p class="mt-1.5 text-xl font-semibold tracking-tight tabular-nums">
                    <span class="text-white/70">₱</span>{{ $gcashPesos }}<span class="text-sm text-white/60">.{{ $gcashCentavos }}</span>
                </p>
            </div>
            <div class="glass-card glass-cash rounded-2xl p-4 text-white">
                <p class="text-[11px] font-medium uppercase tracking-wide text-white/85">Cash on hand</p>
                <p class="mt-1.5 text-xl font-semibold tracking-tight tabular-nums">
                    <span class="text-white/70">₱</span>{{ $cashPesos }}<span class="text-sm text-white/60">.{{ $cashCentavos }}</span>
                </p>
            </div>
        </div>

        {{-- The two actions, right under the balances so they're on the first
             screen without scrolling. --}}
        <div class="mt-3 grid grid-cols-2 gap-3">
            <button type="button" onclick="cashAction('cash_in')"
                    class="flex h-12 items-center justify-center gap-2 rounded-xl bg-cash-600 text-[15px] font-medium text-white transition active:bg-cash-700">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $iconCashIn }}"/></svg>
                Cash in
            </button>
            <button type="button" onclick="cashAction('cash_out')"
                    class="flex h-12 items-center justify-center gap-2 rounded-xl bg-gcash-600 text-[15px] font-medium text-white transition active:bg-gcash-700">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $iconCashOut }}"/></svg>
                Cash out
            </button>
        </div>

        {{-- Today's totals — same component as the History page --}}
        <div class="mt-4">
            @include('partials.stat-cards', ['totals' => $stats, 'compact' => true])
        </div>

        {{-- Recent --}}
        <div class="mt-4 rounded-xl border border-gray-200 bg-surface">
            <div class="flex items-center justify-between px-5 py-3.5 border-b border-gray-200">
                <h2 class="text-[15px] font-semibold text-gray-900">Recent</h2>
                <a href="{{ route('history') }}" class="text-sm font-medium text-gcash-600">View all</a>
            </div>
            @include('partials.recent-transactions', ['recent' => $recent, 'sessionActive' => $sessionActive])
        </div>
    </div>

    @include('partials.transaction-modal')

    {{-- ===== Dashboard interactions (SweetAlert2) ===== --}}
    <script>
        window.GTRACK_SESSION_ACTIVE = @json($sessionActive);

        // Hex values of the gcash-600 / red-600 tokens, for SweetAlert's own buttons.
        const GTRACK_ACCENT = '#1F5AE0';
        const GTRACK_DANGER = '#DC2626';

        function cashAction(type) {
            if (!window.GTRACK_SESSION_ACTIVE) {
                Swal.fire({
                    icon: 'info',
                    title: 'Start the day first',
                    text: 'Press “Start day” and enter your opening balances before recording a transaction.',
                    confirmButtonText: 'Got it',
                    confirmButtonColor: GTRACK_ACCENT,
                });
                return;
            }
            // Open the cash modal
            window.dispatchEvent(new CustomEvent('open-cash-modal', { detail: { mode: type } }));
        }

        function confirmStartDay() {
            Swal.fire({
                title: 'Start the day',
                html: `
                    <div class="text-left space-y-4 mt-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Opening GCash balance</label>
                            <div class="relative">
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500">₱</span>
                                <input type="number" id="starting_gcash" step="0.01" min="0"
                                    class="w-full pl-8 pr-3 py-2 border border-gray-300 rounded-lg tabular-nums focus:ring-2 focus:ring-gcash-500 focus:border-gcash-500"
                                    placeholder="0.00" value="{{ (float) $balance->gcash_balance }}">
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Opening cash on hand</label>
                            <div class="relative">
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500">₱</span>
                                <input type="number" id="starting_cash" step="0.01" min="0"
                                    class="w-full pl-8 pr-3 py-2 border border-gray-300 rounded-lg tabular-nums focus:ring-2 focus:ring-gcash-500 focus:border-gcash-500"
                                    placeholder="0.00" value="{{ (float) $balance->cash_balance }}">
                            </div>
                        </div>
                        <p class="text-xs text-gray-500">Count the drawer and check the GCash app. These become today's opening balances.</p>
                    </div>
                `,
                showCancelButton: true,
                confirmButtonText: 'Start day',
                cancelButtonText: 'Cancel',
                customClass: {
                    popup: 'rounded-xl',
                    confirmButton: 'px-5 py-2.5 bg-gcash-600 hover:bg-gcash-700 text-white font-medium rounded-lg transition',
                    cancelButton: 'px-5 py-2.5 bg-surface border border-gray-300 hover:bg-gray-50 text-gray-700 font-medium rounded-lg transition'
                },
                buttonsStyling: false,
                preConfirm: () => {
                    const gcash = parseFloat(document.getElementById('starting_gcash').value);
                    const cash = parseFloat(document.getElementById('starting_cash').value);

                    if (isNaN(gcash) || isNaN(cash)) {
                        Swal.showValidationMessage('Please enter both starting balances');
                        return false;
                    }

                    if (gcash < 0 || cash < 0) {
                        Swal.showValidationMessage('Starting balances cannot be negative');
                        return false;
                    }

                    return { gcash, cash };
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    // Pass the entered starting balances to the backend
                    document.getElementById('form-starting-gcash').value = result.value.gcash;
                    document.getElementById('form-starting-cash').value = result.value.cash;
                    document.getElementById('day-form').submit();
                }
            });
        }

        function confirmEndDay() {
            Swal.fire({
                title: 'End the day?',
                html: 'This closes the session and records the closing balances. The cash balance resets to ₱0; the GCash balance carries over to tomorrow.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'End day',
                confirmButtonColor: GTRACK_DANGER,
                cancelButtonText: 'Cancel',
            }).then((r) => { if (r.isConfirmed) document.getElementById('day-form').submit(); });
        }
    </script>

</x-app-layout>
