<x-app-layout>

    @php
        $hour = (int) now()->format('H');
        $greeting = $hour < 12 ? 'Good morning' : ($hour < 18 ? 'Good afternoon' : 'Good evening');
        $firstName = explode(' ', trim(Auth::user()->name))[0];
        $today = now()->format('l, F j, Y');
    @endphp

    {{-- ============================================================ --}}
    {{-- ===============   WEB / DESKTOP VIEW (lg+)   =============== --}}
    {{-- ============================================================ --}}
    <div class="hidden lg:block max-w-7xl mx-auto px-8 py-8">

            {{-- Greeting + session / Start Day --}}
            <div class="flex items-end justify-between mb-7">
                <div>
                    <p class="text-gray-400 text-sm font-medium">{{ $greeting }}, {{ $firstName }}</p>
                    <h1 class="text-2xl font-bold text-gray-900 mt-0.5">{{ $today }}</h1>
                </div>
                <div class="flex items-center gap-3">
                    <span class="inline-flex items-center gap-1.5 bg-gray-100 text-gray-500 text-xs font-medium px-3 py-1.5 rounded-full">
                        <span class="w-1.5 h-1.5 rounded-full bg-gray-400"></span>
                        Session Inactive
                    </span>
                    <button class="inline-flex items-center gap-1.5 text-white text-sm font-bold px-4 py-2 rounded-full shadow active:scale-95 transition"
                            style="background: linear-gradient(135deg, #0066FF, #00A6FF);">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M6.3 2.8A1 1 0 004.8 3.7v12.6a1 1 0 001.5.9l10.5-6.3a1 1 0 000-1.8L6.3 2.8z"/></svg>
                        Start Day
                    </button>
                </div>
            </div>

            {{-- Balance cards (2 large) --}}
            <div class="grid grid-cols-2 gap-5 mb-5">
                <div class="js-rise rounded-2xl p-6 text-white shadow-lg relative overflow-hidden"
                     style="background: linear-gradient(135deg, #0066FF, #2B8CFF);">
                    <div class="absolute w-40 h-40 rounded-full bg-white/10 -top-12 -right-8"></div>
                    <div class="relative flex items-center gap-2 mb-3">
                        <div class="w-10 h-10 rounded-full bg-white/20 flex items-center justify-center font-extrabold">G</div>
                        <span class="text-blue-50 text-sm font-medium">GCash Balance</span>
                    </div>
                    <p class="relative text-3xl font-bold">₱0.00</p>
                </div>

                <div class="js-rise rounded-2xl p-6 text-white shadow-lg relative overflow-hidden"
                     style="background: linear-gradient(135deg, #15a34a, #22c55e);">
                    <div class="absolute w-40 h-40 rounded-full bg-white/10 -top-12 -right-8"></div>
                    <div class="relative flex items-center gap-2 mb-3">
                        <div class="w-10 h-10 rounded-full bg-white/20 flex items-center justify-center">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6M3.75 6h16.5a1.5 1.5 0 011.5 1.5v9a1.5 1.5 0 01-1.5 1.5H3.75a1.5 1.5 0 01-1.5-1.5v-9A1.5 1.5 0 013.75 6z"/></svg>
                        </div>
                        <span class="text-green-50 text-sm font-medium">On Hand Cash</span>
                    </div>
                    <p class="relative text-3xl font-bold">₱0.00</p>
                </div>
            </div>

            {{-- Stats row (4) --}}
            <div class="grid grid-cols-4 gap-5 mb-5">
                @php
                    // Full literal class names so Tailwind's scanner detects them
                    $stats = [
                        ['Total Cash In', '₱0.00', 'bg-green-100', 'text-green-600', 'M12 19.5v-15m0 0l-6.75 6.75M12 4.5l6.75 6.75'],
                        ['Total Cash Out', '₱0.00', 'bg-blue-100', 'text-blue-600', 'M12 4.5v15m0 0l6.75-6.75M12 19.5l-6.75-6.75'],
                        ['Service Charge', '₱0.00', 'bg-amber-100', 'text-amber-600', 'M9 7.5l6 9M8.25 9h.008v.008H8.25V9zm7.5 6h.008v.008h-.008V15zM21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
                        ['Transactions', '0', 'bg-purple-100', 'text-purple-600', 'M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z'],
                    ];
                @endphp
                @foreach ($stats as [$label, $value, $iconBg, $iconText, $path])
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
                    <div class="py-16 flex flex-col items-center text-center">
                        <div class="w-16 h-16 rounded-full bg-gray-100 flex items-center justify-center mb-3">
                            <svg class="w-8 h-8 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6"><path stroke-linecap="round" stroke-linejoin="round" d="M9 17.25v1.007a3 3 0 01-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0115 18.257V17.25m6-12V15a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 15V5.25m18 0A2.25 2.25 0 0018.75 3H5.25A2.25 2.25 0 003 5.25m18 0V12a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 12V5.25"/></svg>
                        </div>
                        <p class="text-gray-400 text-sm font-medium">No transactions yet.</p>
                        <p class="text-gray-300 text-xs mt-1">Start your day to begin logging transactions.</p>
                    </div>
                </div>

                <div class="js-rise space-y-4">
                    <h2 class="text-gray-800 text-lg font-bold">Quick Actions</h2>
                    <button class="w-full flex items-center justify-center gap-2 py-4 rounded-2xl font-bold text-white shadow-md active:scale-95 transition"
                            style="background: linear-gradient(135deg, #16a34a, #22c55e);">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                        Cash In
                    </button>
                    <button class="w-full flex items-center justify-center gap-2 py-4 rounded-2xl font-bold text-white shadow-md active:scale-95 transition"
                            style="background: linear-gradient(135deg, #2563eb, #3b82f6);">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 12h-15"/></svg>
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
                <span class="inline-flex items-center gap-1.5 bg-white/15 text-blue-50 text-xs font-medium px-3 py-1.5 rounded-full">
                    <span class="w-1.5 h-1.5 rounded-full bg-blue-200"></span>
                    Session Inactive
                </span>
                <button class="inline-flex items-center gap-1.5 bg-white text-blue-600 text-sm font-bold px-4 py-2 rounded-full shadow-md hover:shadow-lg active:scale-95 transition">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M6.3 2.8A1 1 0 004.8 3.7v12.6a1 1 0 001.5.9l10.5-6.3a1 1 0 000-1.8L6.3 2.8z"/></svg>
                    Start Day
                </button>
            </div>
        </div>

        {{-- Balance cards --}}
        <div class="px-5 -mt-16 relative z-10">
            <div class="grid grid-cols-2 gap-3">
                <div class="js-rise bg-white rounded-2xl shadow-lg p-4">
                    <div class="w-9 h-9 rounded-full bg-blue-100 flex items-center justify-center mb-2">
                        <span class="text-blue-600 font-extrabold text-sm">G</span>
                    </div>
                    <p class="text-gray-400 text-xs font-medium">GCash Balance</p>
                    <p class="text-gray-900 text-lg font-bold mt-0.5">₱0.00</p>
                </div>
                <div class="js-rise bg-white rounded-2xl shadow-lg p-4">
                    <div class="w-9 h-9 rounded-full bg-green-100 flex items-center justify-center mb-2">
                        <svg class="w-5 h-5 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6M3.75 6h16.5a1.5 1.5 0 011.5 1.5v9a1.5 1.5 0 01-1.5 1.5H3.75a1.5 1.5 0 01-1.5-1.5v-9A1.5 1.5 0 013.75 6z"/></svg>
                    </div>
                    <p class="text-gray-400 text-xs font-medium">On Hand Cash</p>
                    <p class="text-gray-900 text-lg font-bold mt-0.5">₱0.00</p>
                </div>
            </div>
        </div>

        {{-- Stats 2x2 --}}
        <div class="px-5 mt-5">
            <div class="grid grid-cols-2 gap-3">
                @foreach ($stats ?? [] as [$label, $value, $iconBg, $iconText, $path])
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
                <button class="js-rise flex items-center justify-center gap-2 py-3.5 rounded-2xl font-bold text-white shadow-md active:scale-95 transition"
                        style="background: linear-gradient(135deg, #16a34a, #22c55e);">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                    Cash In
                </button>
                <button class="js-rise flex items-center justify-center gap-2 py-3.5 rounded-2xl font-bold text-white shadow-md active:scale-95 transition"
                        style="background: linear-gradient(135deg, #2563eb, #3b82f6);">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 12h-15"/></svg>
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
            <div class="js-rise bg-white rounded-2xl shadow-sm py-12 px-6 flex flex-col items-center text-center">
                <div class="w-14 h-14 rounded-full bg-gray-100 flex items-center justify-center mb-3">
                    <svg class="w-7 h-7 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 17.25v1.007a3 3 0 01-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0115 18.257V17.25m6-12V15a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 15V5.25m18 0A2.25 2.25 0 0018.75 3H5.25A2.25 2.25 0 003 5.25m18 0V12a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 12V5.25"/></svg>
                </div>
                <p class="text-gray-400 text-sm font-medium">No transactions yet.</p>
                <p class="text-gray-300 text-xs mt-1">Start your day to begin logging transactions.</p>
            </div>
        </div>
    </div>

</x-app-layout>
