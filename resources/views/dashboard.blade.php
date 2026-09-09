<x-app-layout>

    @php
        $hour = (int) now()->format('H');
        $greeting = $hour < 12 ? 'Good morning' : ($hour < 18 ? 'Good afternoon' : 'Good evening');
        $firstName = explode(' ', trim(Auth::user()->name))[0];
        $today = now()->format('l, F j, Y');
        $sessionActive = (bool) $session;

        // Stat cards (real values from controller)
        $statCards = [
            ['Total Cash In',  '₱'.number_format($stats['cash_in'], 2),        'bg-green-100',  'text-green-600',  'M12 19.5v-15m0 0l-6.75 6.75M12 4.5l6.75 6.75'],
            ['Total Cash Out', '₱'.number_format($stats['cash_out'], 2),       'bg-blue-100',   'text-blue-600',   'M12 4.5v15m0 0l6.75-6.75M12 19.5l-6.75-6.75'],
            ['Service Charge', '₱'.number_format($stats['service_charge'], 2), 'bg-amber-100',  'text-amber-600',  'M9 7.5l6 9M8.25 9h.008v.008H8.25V9zm7.5 6h.008v.008h-.008V15zM21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
            ['Transactions',   (string) $stats['count'],                       'bg-purple-100', 'text-purple-600', 'M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z'],
        ];
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

        {{-- Greeting + session / Start-End Day --}}
        <div class="flex items-end justify-between mb-7">
            <div>
                <p class="text-gray-400 text-sm font-medium">{{ $greeting }}, {{ $firstName }}</p>
                <h1 class="text-2xl font-bold text-gray-900 mt-0.5">{{ $today }}</h1>
            </div>
            <div class="flex items-center gap-3">
                @if ($sessionActive)
                    <span class="inline-flex items-center gap-1.5 bg-green-50 text-green-600 text-xs font-medium px-3 py-1.5 rounded-full">
                        <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span>
                        Active · Started {{ $session->started_at->format('g:i A') }}
                    </span>
                    <button type="button" onclick="confirmEndDay()" class="inline-flex items-center gap-1.5 text-white text-sm font-bold px-4 py-2 rounded-full shadow active:scale-95 transition" style="background: linear-gradient(135deg, #ef4444, #f87171);">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.718 9.718 0 0118 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 003 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 009.002-5.998z"/></svg>
                        End Day
                    </button>
                @else
                    <span class="inline-flex items-center gap-1.5 bg-gray-100 text-gray-500 text-xs font-medium px-3 py-1.5 rounded-full">
                        <span class="w-1.5 h-1.5 rounded-full bg-gray-400"></span>
                        Session Inactive
                    </span>
                    <button type="button" onclick="confirmStartDay()" class="inline-flex items-center gap-1.5 text-white text-sm font-bold px-4 py-2 rounded-full shadow active:scale-95 transition" style="background: linear-gradient(135deg, #0066FF, #00A6FF);">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386l-1.591 1.591M21 12h-2.25m-.386 6.364l-1.591-1.591M12 18.75V21m-4.773-4.227l-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z"/></svg>
                        Start Day
                    </button>
                @endif
            </div>
        </div>

        {{-- Balance cards --}}
        <div class="grid grid-cols-2 gap-5 mb-5">
            <div class="js-rise rounded-2xl p-6 text-white shadow-lg relative overflow-hidden"
                 style="background: linear-gradient(135deg, #0066FF, #2B8CFF);">
                <div class="absolute w-40 h-40 rounded-full bg-white/10 -top-12 -right-8"></div>
                <div class="relative flex items-center gap-2 mb-3">
                    <div class="w-10 h-10 rounded-full bg-white flex items-center justify-center shadow-sm shrink-0">
                        <img src="{{ asset('gcashLogo.png') }}" alt="GCash" class="w-6 h-6 object-contain">
                    </div>
                    <span class="text-blue-50 text-sm font-medium">GCash Balance</span>
                </div>
                <p class="relative text-3xl font-bold">₱{{ number_format($balance->gcash_balance, 2) }}</p>
            </div>

            <div class="js-rise rounded-2xl p-6 text-white shadow-lg relative overflow-hidden"
                 style="background: linear-gradient(135deg, #15a34a, #22c55e);">
                <div class="absolute w-40 h-40 rounded-full bg-white/10 -top-12 -right-8"></div>
                <div class="relative flex items-center gap-2 mb-3">
                    <div class="w-10 h-10 rounded-full bg-white flex items-center justify-center shadow-sm shrink-0">
                        <span class="text-green-600 font-bold text-lg">₱</span>
                    </div>
                    <span class="text-green-50 text-sm font-medium">On Hand Cash</span>
                </div>
                <p class="relative text-3xl font-bold">₱{{ number_format($balance->cash_balance, 2) }}</p>
            </div>
        </div>

        {{-- Stats row (4) --}}
        <div class="grid grid-cols-4 gap-5 mb-5">
            @foreach ($statCards as [$label, $value, $iconBg, $iconText, $path])
                <div class="js-rise bg-white rounded-2xl shadow-sm p-5 flex items-center gap-3.5">
                    <div class="w-11 h-11 rounded-full {{ $iconBg }} flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 {{ $iconText }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $path }}"/></svg>
                    </div>
                    <div>
                        <p class="text-gray-400 text-xs font-medium">{{ $label }}</p>
                        <p class="text-gray-900 text-xl font-bold">{{ $value }}</p>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Bottom: transactions (wide) + actions --}}
        <div class="grid grid-cols-3 gap-5">
            <div class="js-rise col-span-2 bg-white rounded-2xl shadow-sm p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-gray-800 text-lg font-bold">Recent Transactions</h2>
                    <a href="{{ route('history') }}" class="text-blue-600 text-sm font-semibold hover:text-blue-800 transition">View All</a>
                </div>
                @include('partials.recent-transactions', ['recent' => $recent])
            </div>

            <div class="js-rise space-y-4">
                <h2 class="text-gray-800 text-lg font-bold">Quick Actions</h2>
                <button type="button" onclick="cashAction('cash_in')" class="w-full flex items-center justify-center gap-2 py-4 rounded-2xl font-bold text-white shadow-md active:scale-95 transition"
                        style="background: linear-gradient(135deg, #16a34a, #22c55e);">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                    </svg>
                    Cash In
                </button>
                <button type="button" onclick="cashAction('cash_out')" class="w-full flex items-center justify-center gap-2 py-4 rounded-2xl font-bold text-white shadow-md active:scale-95 transition"
                        style="background: linear-gradient(135deg, #2563eb, #3b82f6);">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 12h-15"/>
                    </svg>
                    Cash Out
                </button>
            </div>
        </div>
    </div>

    {{-- ============================================================ --}}
    {{-- ===============   MOBILE VIEW (below lg)     =============== --}}
    {{-- ============================================================ --}}
    <div class="lg:hidden max-w-md mx-auto min-h-screen bg-gray-50 pb-safe-nav shadow-sm">

        {{-- Blue header --}}
        <div class="relative px-5 pt-7 pb-24 rounded-b-[2rem] text-white overflow-hidden"
             style="background: linear-gradient(135deg, #0066FF 0%, #2B8CFF 55%, #00B3FF 100%);">
            <div class="absolute w-48 h-48 rounded-full bg-white/10 -top-16 -right-10 pointer-events-none"></div>
            <div class="absolute w-32 h-32 rounded-full bg-white/10 bottom-8 -left-10 pointer-events-none"></div>

            <div class="relative mb-6">
                <p class="text-blue-100 text-sm font-medium">{{ $greeting }}, {{ $firstName }}</p>
                <h1 class="text-white text-xl font-bold mt-0.5">{{ $today }}</h1>
            </div>

            <div class="relative flex items-center justify-between">
                @if ($sessionActive)
                    <span class="inline-flex items-center gap-1.5 bg-white/15 text-blue-50 text-xs font-medium px-3 py-1.5 rounded-full">
                        <span class="w-1.5 h-1.5 rounded-full bg-green-300"></span>
                        Active · {{ $session->started_at->format('g:i A') }}
                    </span>
                    <button type="button" onclick="confirmEndDay()" class="inline-flex items-center gap-1.5 bg-white text-red-600 text-sm font-bold px-4 py-2 rounded-full shadow-md active:scale-95 transition">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.718 9.718 0 0118 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 003 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 009.002-5.998z"/></svg>
                        End Day
                    </button>
                @else
                    <span class="inline-flex items-center gap-1.5 bg-white/15 text-blue-50 text-xs font-medium px-3 py-1.5 rounded-full">
                        <span class="w-1.5 h-1.5 rounded-full bg-blue-200"></span>
                        Session Inactive
                    </span>
                    <button type="button" onclick="confirmStartDay()" class="inline-flex items-center gap-1.5 bg-white text-blue-600 text-sm font-bold px-4 py-2 rounded-full shadow-md active:scale-95 transition">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386l-1.591 1.591M21 12h-2.25m-.386 6.364l-1.591-1.591M12 18.75V21m-4.773-4.227l-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z"/></svg>
                        Start Day
                    </button>
                @endif
            </div>
        </div>

        {{-- Balance cards --}}
        <div class="px-5 -mt-16 relative z-10">
            <div class="grid grid-cols-2 gap-3">
                <div class="js-rise bg-white rounded-2xl shadow-lg p-4">
                    <div class="w-9 h-9 rounded-full bg-white flex items-center justify-center mb-2 shadow-sm ring-1 ring-gray-100">
                        <img src="{{ asset('gcashLogo.png') }}" alt="GCash" class="w-5 h-5 object-contain">
                    </div>
                    <p class="text-gray-400 text-xs font-medium">GCash Balance</p>
                    <p class="text-gray-900 text-lg font-bold mt-0.5">₱{{ number_format($balance->gcash_balance, 2) }}</p>
                </div>
                <div class="js-rise bg-white rounded-2xl shadow-lg p-4">
                    <div class="w-9 h-9 rounded-full bg-green-50 flex items-center justify-center mb-2 shadow-sm ring-1 ring-green-100">
                        <span class="text-green-600 font-bold text-base">₱</span>
                    </div>
                    <p class="text-gray-400 text-xs font-medium">On Hand Cash</p>
                    <p class="text-gray-900 text-lg font-bold mt-0.5">₱{{ number_format($balance->cash_balance, 2) }}</p>
                </div>
            </div>
        </div>

        {{-- Stats 2x2 --}}
        <div class="px-5 mt-5">
            <div class="grid grid-cols-2 gap-3">
                @foreach ($statCards as [$label, $value, $iconBg, $iconText, $path])
                    <div class="js-rise bg-white rounded-2xl shadow-sm p-4 flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full {{ $iconBg }} flex items-center justify-center shrink-0">
                            <svg class="w-5 h-5 {{ $iconText }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $path }}"/></svg>
                        </div>
                        <div class="min-w-0">
                            <p class="text-gray-400 text-xs font-medium">{{ $label }}</p>
                            <p class="text-gray-900 text-base font-bold">{{ $value }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Quick actions --}}
        <div class="px-5 mt-5">
            <div class="grid grid-cols-2 gap-3">
                <button type="button" onclick="cashAction('cash_in')" class="js-rise flex items-center justify-center gap-2 py-3.5 rounded-2xl font-bold text-white shadow-md active:scale-95 transition"
                        style="background: linear-gradient(135deg, #16a34a, #22c55e);">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                    </svg>
                    Cash In
                </button>
                <button type="button" onclick="cashAction('cash_out')" class="js-rise flex items-center justify-center gap-2 py-3.5 rounded-2xl font-bold text-white shadow-md active:scale-95 transition"
                        style="background: linear-gradient(135deg, #2563eb, #3b82f6);">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 12h-15"/>
                    </svg>
                    Cash Out
                </button>
            </div>
        </div>

        {{-- Recent transactions --}}
        <div class="px-5 mt-6">
            <div class="flex items-center justify-between mb-3">
                <h2 class="text-gray-800 text-base font-bold">Recent Transactions</h2>
                <a href="{{ route('history') }}" class="text-blue-600 text-sm font-semibold hover:text-blue-800 transition">View All</a>
            </div>
            <div class="js-rise bg-white rounded-2xl shadow-sm px-5">
                @include('partials.recent-transactions', ['recent' => $recent])
            </div>
        </div>
    </div>

    @include('partials.transaction-modal')

    {{-- ===== Dashboard interactions (SweetAlert2) ===== --}}
    <script>
        window.GTRACK_SESSION_ACTIVE = @json($sessionActive);

        function cashAction(type) {
            if (!window.GTRACK_SESSION_ACTIVE) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Start the day first',
                    text: 'You need to click “Start Day” before you can log a transaction.',
                    confirmButtonText: 'Got it',
                    confirmButtonColor: '#0066FF',
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
                            <label class="block text-sm font-medium text-gray-700 mb-1">Starting GCash Balance</label>
                            <div class="relative">
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500 font-medium">₱</span>
                                <input type="number" id="starting_gcash" step="0.01" min="0"
                                    class="w-full pl-8 pr-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                    placeholder="0.00" value="{{ (float) $balance->gcash_balance }}">
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Starting Cash Balance</label>
                            <div class="relative">
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500 font-medium">₱</span>
                                <input type="number" id="starting_cash" step="0.01" min="0"
                                    class="w-full pl-8 pr-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                    placeholder="0.00" value="{{ (float) $balance->cash_balance }}">
                            </div>
                        </div>
                        <p class="text-xs text-gray-500 mt-2">These will be recorded as your opening balances for the day.</p>
                    </div>
                `,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Start Day',
                confirmButtonColor: '#16a34a',
                cancelButtonText: 'Cancel',
                customClass: {
                    popup: 'rounded-2xl',
                    confirmButton: 'px-6 py-2.5 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-xl transition',
                    cancelButton: 'px-6 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold rounded-xl transition'
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
                html: 'This will <strong>close your session</strong> and stop transaction recording.<br><br>You will need to start a new day to log transactions again.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, end day',
                confirmButtonColor: '#dc2626',
                cancelButtonText: 'Cancel',
            }).then((r) => { if (r.isConfirmed) document.getElementById('day-form').submit(); });
        }
    </script>

</x-app-layout>
