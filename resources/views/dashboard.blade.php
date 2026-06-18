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
                    <a href="#" class="text-blue-600 text-sm font-semibold hover:text-blue-800 transition">View All</a>
                </div>
                @include('partials.recent-transactions', ['recent' => $recent])
            </div>

            <div class="js-rise space-y-4">
                <h2 class="text-gray-800 text-lg font-bold">Quick Actions</h2>
                <button type="button" onclick="cashAction('cash_in')" class="w-full flex items-center justify-center gap-2 py-4 rounded-2xl font-bold text-white shadow-md active:scale-95 transition"
                        style="background: linear-gradient(135deg, #16a34a, #22c55e);">
                    <div class="w-6 h-6 rounded-full bg-white flex items-center justify-center shrink-0">
                        <span class="text-green-600 font-bold text-sm">₱</span>
                    </div>
                    Cash In
                </button>
                <button type="button" onclick="cashAction('cash_out')" class="w-full flex items-center justify-center gap-2 py-4 rounded-2xl font-bold text-white shadow-md active:scale-95 transition"
                        style="background: linear-gradient(135deg, #2563eb, #3b82f6);">
                    <div class="w-6 h-6 rounded-full bg-white flex items-center justify-center shrink-0">
                        <img src="{{ asset('gcashLogo.png') }}" alt="GCash" class="w-4 h-4 object-contain">
                    </div>
                    Cash Out
                </button>
            </div>
        </div>
    </div>

    {{-- ============================================================ --}}
    {{-- ===============   MOBILE VIEW (below lg)     =============== --}}
    {{-- ============================================================ --}}
    <div class="lg:hidden max-w-md mx-auto min-h-screen bg-gray-50 pb-10 shadow-sm">

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
                    <div class="w-6 h-6 rounded-full bg-white flex items-center justify-center shrink-0">
                        <span class="text-green-600 font-bold text-sm">₱</span>
                    </div>
                    Cash In
                </button>
                <button type="button" onclick="cashAction('cash_out')" class="js-rise flex items-center justify-center gap-2 py-3.5 rounded-2xl font-bold text-white shadow-md active:scale-95 transition"
                        style="background: linear-gradient(135deg, #2563eb, #3b82f6);">
                    <div class="w-6 h-6 rounded-full bg-white flex items-center justify-center shrink-0">
                        <img src="{{ asset('gcashLogo.png') }}" alt="GCash" class="w-4 h-4 object-contain">
                    </div>
                    Cash Out
                </button>
            </div>
        </div>

        {{-- Recent transactions --}}
        <div class="px-5 mt-6">
            <div class="flex items-center justify-between mb-3">
                <h2 class="text-gray-800 text-base font-bold">Recent Transactions</h2>
                <a href="#" class="text-blue-600 text-sm font-semibold hover:text-blue-800 transition">View All</a>
            </div>
            <div class="js-rise bg-white rounded-2xl shadow-sm px-5">
                @include('partials.recent-transactions', ['recent' => $recent])
            </div>
        </div>
    </div>

    {{-- ===== Cash In/Out Modal ===== --}}
    <div x-data="{ open: false, mode: 'cash_in', entryMode: 'manual' }"
         @open-cash-modal.window="open = true; mode = $event.detail.mode; entryMode = 'manual'"
         x-show="open"
         x-cloak
         @keydown.escape.window="open = false"
         class="fixed inset-0 z-[60] flex items-center justify-center p-4">

        {{-- Overlay --}}
        <div x-show="open" x-transition.opacity @click="open = false" class="absolute inset-0 bg-black/50 backdrop-blur-sm"></div>

        {{-- Dialog --}}
        <div x-show="open"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95 translate-y-3"
             x-transition:enter-end="opacity-100 scale-100 translate-y-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md p-6">

            {{-- Header --}}
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-xl font-bold text-gray-900" x-text="mode === 'cash_in' ? 'Cash In' : 'Cash Out'"></h3>
                <button @click="open = false" class="text-gray-400 hover:text-gray-600 transition">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            {{-- Mode Toggle --}}
            <div class="flex gap-2 mb-6 p-1 bg-gray-100 rounded-xl">
                <button @click="entryMode = 'manual'"
                        :class="entryMode === 'manual' ? 'bg-white shadow-sm' : 'text-gray-600'"
                        class="flex-1 py-2 px-3 rounded-lg text-sm font-semibold transition">
                    <svg class="w-4 h-4 inline-block mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10"/>
                    </svg>
                    Manual Entry
                </button>
                <button @click="entryMode = 'scan'"
                        :class="entryMode === 'scan' ? 'bg-white shadow-sm' : 'text-gray-600'"
                        class="flex-1 py-2 px-3 rounded-lg text-sm font-semibold transition">
                    <svg class="w-4 h-4 inline-block mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 015.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 00-1.134-.175 2.31 2.31 0 01-1.64-1.055l-.822-1.316a2.192 2.192 0 00-1.736-1.039 48.774 48.774 0 00-5.232 0 2.192 2.192 0 00-1.736 1.039l-.821 1.316z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 11-9 0 4.5 4.5 0 019 0zM18.75 10.5h.008v.008h-.008V10.5z"/>
                    </svg>
                    Scan Receipt
                </button>
            </div>

            {{-- Manual Entry Form --}}
            <form x-show="entryMode === 'manual'" class="space-y-4" @submit.prevent="submitTransaction">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Customer Mobile Number</label>
                    <input type="tel" id="customer_mobile" placeholder="09xxxxxxxxx" maxlength="11"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Amount</label>
                    <div class="relative">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500 font-medium">₱</span>
                        <input type="number" id="amount" step="0.01" min="0" placeholder="0.00"
                            @input="calculateServiceCharge"
                            class="w-full pl-8 pr-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                </div>

                <div>
                    <label class="flex items-center justify-between text-sm font-medium text-gray-700 mb-1">
                        <span>Service Charge</span>
                        <button type="button" @click="$refs.ratesGuide.classList.toggle('hidden')"
                                class="text-xs text-blue-600 hover:text-blue-800 font-normal">
                            View Rates
                        </button>
                    </label>
                    <div class="relative">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500 font-medium">₱</span>
                        <input type="number" id="service_charge" step="0.01" min="0" placeholder="0.00" readonly
                            class="w-full pl-8 pr-3 py-2 border border-gray-300 rounded-lg bg-gray-50 text-gray-700">
                    </div>

                    {{-- Service Charge Rates Guide --}}
                    <div x-ref="ratesGuide" class="hidden mt-2 p-3 bg-blue-50 border border-blue-200 rounded-lg">
                        <p class="text-xs font-semibold text-blue-900 mb-2">Service Charge Rates</p>
                        <div class="text-xs text-blue-800 space-y-1">
                            <div class="flex justify-between"><span>₱1 - ₱500</span><span class="font-semibold">₱10</span></div>
                            <div class="flex justify-between"><span>₱501 - ₱1,000</span><span class="font-semibold">₱15</span></div>
                            <div class="flex justify-between"><span>₱1,001 - ₱2,500</span><span class="font-semibold">₱20</span></div>
                            <div class="flex justify-between"><span>₱2,501 - ₱5,000</span><span class="font-semibold">₱25</span></div>
                            <div class="flex justify-between"><span>₱5,001 - ₱10,000</span><span class="font-semibold">₱30</span></div>
                            <div class="flex justify-between"><span>₱10,001 - ₱20,000</span><span class="font-semibold">₱50</span></div>
                            <div class="flex justify-between"><span>Above ₱20,000</span><span class="font-semibold">₱100</span></div>
                        </div>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Reference Number</label>
                    <input type="text" id="reference_number" placeholder="Enter GCash reference number"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>

                <div class="flex gap-3 pt-2">
                    <button type="button" @click="open = false"
                            class="flex-1 py-2.5 rounded-xl font-semibold text-gray-700 bg-gray-100 hover:bg-gray-200 hover:text-gray-900 transition-all duration-200 active:scale-95">
                        Cancel
                    </button>
                    <button type="submit"
                            class="flex-1 py-2.5 rounded-xl font-semibold text-white shadow-md transition-all duration-200 active:scale-95"
                            :class="mode === 'cash_in' ? 'bg-green-600 hover:bg-green-700' : 'bg-blue-600 hover:bg-blue-700'">
                        Submit
                    </button>
                </div>
            </form>

            {{-- Scan Receipt Mode --}}
            <div x-show="entryMode === 'scan'" class="space-y-4">
                <div class="border-2 border-dashed border-gray-300 rounded-xl p-8 text-center bg-gray-50">
                    <svg class="w-16 h-16 mx-auto text-gray-400 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 015.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 00-1.134-.175 2.31 2.31 0 01-1.64-1.055l-.822-1.316a2.192 2.192 0 00-1.736-1.039 48.774 48.774 0 00-5.232 0 2.192 2.192 0 00-1.736 1.039l-.821 1.316z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 11-9 0 4.5 4.5 0 019 0zM18.75 10.5h.008v.008h-.008V10.5z"/>
                    </svg>
                    <p class="text-sm font-medium text-gray-700 mb-2">Take a photo or upload receipt</p>
                    <p class="text-xs text-gray-500 mb-4">System will automatically extract transaction details</p>

                    <div class="flex gap-2 justify-center">
                        <button type="button"
                                class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-lg transition active:scale-95">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 015.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 00-1.134-.175 2.31 2.31 0 01-1.64-1.055l-.822-1.316a2.192 2.192 0 00-1.736-1.039 48.774 48.774 0 00-5.232 0 2.192 2.192 0 00-1.736 1.039l-.821 1.316z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 11-9 0 4.5 4.5 0 019 0z"/>
                            </svg>
                            Take Photo
                        </button>
                        <button type="button"
                                class="inline-flex items-center gap-2 px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white text-sm font-semibold rounded-lg transition active:scale-95">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"/>
                            </svg>
                            Upload File
                        </button>
                    </div>
                </div>

                <p class="text-xs text-gray-500 text-center">
                    <svg class="w-4 h-4 inline-block text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z"/>
                    </svg>
                    After scanning, you can review and edit the details before submitting
                </p>

                <button type="button" @click="open = false"
                        class="w-full py-2.5 rounded-xl font-semibold text-gray-700 bg-gray-100 hover:bg-gray-200 hover:text-gray-900 transition-all duration-200 active:scale-95">
                    Cancel
                </button>
            </div>
        </div>
    </div>

    {{-- ===== Dashboard interactions (SweetAlert2) ===== --}}
    <script>
        window.GTRACK_SESSION_ACTIVE = @json($sessionActive);

        function calculateServiceCharge() {
            const amount = parseFloat(document.getElementById('amount').value) || 0;
            let serviceCharge = 0;

            if (amount >= 1 && amount <= 500) {
                serviceCharge = 10;
            } else if (amount >= 501 && amount <= 1000) {
                serviceCharge = 15;
            } else if (amount >= 1001 && amount <= 2500) {
                serviceCharge = 20;
            } else if (amount >= 2501 && amount <= 5000) {
                serviceCharge = 25;
            } else if (amount >= 5001 && amount <= 10000) {
                serviceCharge = 30;
            } else if (amount >= 10001 && amount <= 20000) {
                serviceCharge = 50;
            } else if (amount > 20000) {
                serviceCharge = 100;
            }

            document.getElementById('service_charge').value = serviceCharge.toFixed(2);
        }

        function submitTransaction() {
            // For now, just show a success message
            // Backend integration will be done later
            Swal.fire({
                icon: 'success',
                title: 'Transaction Recorded',
                text: 'The transaction has been successfully recorded.',
                confirmButtonColor: '#0066FF',
            });
        }

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
                                    placeholder="0.00" value="${<?php echo $balance->gcash_balance; ?>}">
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Starting Cash Balance</label>
                            <div class="relative">
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500 font-medium">₱</span>
                                <input type="number" id="starting_cash" step="0.01" min="0"
                                    class="w-full pl-8 pr-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                    placeholder="0.00" value="${<?php echo $balance->cash_balance; ?>}">
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
                    const gcash = document.getElementById('starting_gcash').value;
                    const cash = document.getElementById('starting_cash').value;

                    if (!gcash || !cash) {
                        Swal.showValidationMessage('Please enter both starting balances');
                        return false;
                    }

                    if (parseFloat(gcash) < 0 || parseFloat(cash) < 0) {
                        Swal.showValidationMessage('Balances cannot be negative');
                        return false;
                    }

                    return { gcash, cash };
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    // For now, just submit the form (backend already handles it)
                    // In the future, you can append these values to the form if needed
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
