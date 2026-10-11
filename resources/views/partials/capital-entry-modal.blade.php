{{-- Borrow / Repay / Add capital (kumuha / nagbayad / dagdag puhunan).

     One modal for the three kinds of ledger entry. Posts to
     capital.entries.store. The preview mirrors CapitalEntry::deltasFor(),
     Balance::canAbsorb() and the "can't repay more than the utang" rule —
     the server re-checks all of them inside a locked transaction.

     Opened with gtrackOpenCapital(kind). Expects: $balance, $summary --}}

<div x-data="capitalEntryModal({
        gcash: {{ (float) $balance->gcash_balance }},
        cash: {{ (float) $balance->cash_balance }},
        puhunan: {{ (float) $summary['puhunan'] }},
        utang: {{ (float) $summary['utang'] }},
        dayOpen: @js((bool) $session),
        old: @js([
            'kind' => old('kind'),
            'amount' => old('amount'),
            'source' => old('source'),
            'purpose' => old('purpose'),
            'note' => old('note'),
        ]),
     })"
     x-show="open"
     x-cloak
     @open-capital.window="openFresh($event.detail.kind)"
     @keydown.escape.window="open = false"
     class="fixed inset-0 z-[60] flex items-center justify-center p-4">

    <div x-show="open" x-transition.opacity @click="open = false" class="absolute inset-0 bg-black/50 backdrop-blur-sm"></div>

    <div x-show="open"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95 translate-y-3"
         x-transition:enter-end="opacity-100 scale-100 translate-y-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         class="relative bg-surface rounded-2xl shadow-2xl w-full max-w-md max-h-[92vh] overflow-y-auto p-6">

        <div class="flex items-center justify-between mb-1">
            <h3 class="text-xl font-bold text-gray-900" x-text="title"></h3>
            <button type="button" @click="open = false" aria-label="Close" class="text-gray-400 hover:text-gray-600 transition">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        <p class="text-sm text-gray-500 mb-4" x-text="subtitle"></p>

        <form method="POST" action="{{ route('capital.entries.store') }}" class="space-y-4" @submit="submitting = true">
            @csrf
            <input type="hidden" name="kind" :value="kind">
            <input type="hidden" name="source" :value="source">
            <input type="hidden" name="purpose" :value="kind === 'borrow' ? purpose : ''">

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Amount <span class="text-red-500">*</span></label>
                <div class="relative">
                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500 font-medium">₱</span>
                    <input type="number" name="amount" x-ref="amount" x-model.number="amount"
                        step="0.01" min="0" inputmode="decimal" placeholder="0.00"
                        class="w-full pl-8 pr-3 py-2 border rounded-lg focus:ring-2 transition"
                        :class="amountError || problem
                            ? 'border-red-400 focus:ring-red-400 focus:border-red-400'
                            : 'border-gray-300 focus:ring-gcash-500 focus:border-gcash-500'">
                </div>
                <p x-show="amountError" x-cloak class="mt-1 text-xs text-red-600" x-text="amountError"></p>
                @error('amount')<p x-show="serverErrors" x-cloak class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            {{-- What it was for — only a borrow has one. Picking it suggests a
                 source when none is chosen yet (bills come out of the wallet). --}}
            <div x-show="kind === 'borrow'" x-cloak>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">What for? <span class="text-gray-400 font-normal">(optional)</span></label>
                <div class="flex flex-wrap gap-1.5">
                    @foreach (\App\Models\CapitalEntry::PURPOSES as $value => $label)
                        <button type="button" @click="pickPurpose('{{ $value }}')"
                                :class="purpose === '{{ $value }}'
                                    ? 'bg-gcash-600 text-white border-gcash-600'
                                    : 'bg-surface text-gray-600 border-gray-200 hover:border-gcash-300 hover:text-gcash-600'"
                                class="px-3 py-1.5 rounded-lg border text-xs font-semibold transition active:scale-95">
                            {{ $label }}
                        </button>
                    @endforeach
                </div>
            </div>

            {{-- Which balance physically moved. Chosen on purpose: a default here
                 would quietly put money in, or leave it out of, the wrong place. --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">
                    <span x-text="sourceQuestion"></span> <span class="text-red-500">*</span>
                </label>
                <div class="space-y-2">
                    <template x-for="option in sourceOptions" :key="option.value">
                        <button type="button" @click="source = option.value"
                                :class="source === option.value
                                    ? 'border-gcash-500 bg-gcash-50 text-gcash-900'
                                    : 'border-gray-200 bg-surface text-gray-600 hover:border-gray-300'"
                                class="w-full text-left px-3 py-2.5 rounded-xl border-2 transition">
                            <span class="block text-sm font-bold" x-text="option.label"></span>
                            <span class="block text-xs mt-0.5 opacity-70" x-text="option.hint"></span>
                        </button>
                    </template>
                </div>
                @error('source')<p x-show="serverErrors" x-cloak class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Note <span class="text-gray-400 font-normal">(optional)</span></label>
                <input type="text" name="note" x-model="note" maxlength="100" autocomplete="off"
                    placeholder="e.g. groceries at the market"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gcash-500 focus:border-gcash-500 transition">
            </div>

            {{-- What actually changes. --}}
            <div x-show="amount > 0 && source" x-cloak class="rounded-xl border border-gray-200 bg-gray-50 p-3 space-y-2">
                <p class="text-[11px] font-bold uppercase tracking-wide text-gray-500">What happens</p>

                <div class="space-y-1.5 text-sm">
                    <template x-if="source === 'gcash'">
                        <div class="flex items-center justify-between gap-3">
                            <span class="text-gray-600">GCash wallet</span>
                            <span class="font-bold tabular-nums whitespace-nowrap" :class="signed >= 0 ? 'text-green-600' : 'text-red-600'"
                                  x-text="(signed >= 0 ? '+' : '−') + peso(Math.abs(signed))"></span>
                        </div>
                    </template>
                    <template x-if="source === 'cash'">
                        <div class="flex items-center justify-between gap-3">
                            <span class="text-gray-600">Cash on hand (blue box)</span>
                            <span class="font-bold tabular-nums whitespace-nowrap" :class="signed >= 0 ? 'text-green-600' : 'text-red-600'"
                                  x-text="(signed >= 0 ? '+' : '−') + peso(Math.abs(signed))"></span>
                        </div>
                    </template>
                    <p x-show="source === 'none'" class="text-gray-600">Nothing moves in the wallet or cash — only the ledger changes.</p>

                    <div class="flex items-center justify-between gap-3 pt-1.5 border-t border-gray-200" x-show="kind !== 'capital'">
                        <span class="text-gray-500 text-xs">Debt</span>
                        <span class="text-xs font-semibold tabular-nums text-gray-900 whitespace-nowrap"
                              x-text="peso(utang) + ' → ' + peso(afterUtang)"></span>
                    </div>
                    <div class="flex items-center justify-between gap-3 pt-1.5 border-t border-gray-200" x-show="kind === 'capital'">
                        <span class="text-gray-500 text-xs">Capital</span>
                        <span class="text-xs font-semibold tabular-nums text-gray-900 whitespace-nowrap"
                              x-text="peso(puhunan) + ' → ' + peso(afterPuhunan)"></span>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <span class="text-gray-500 text-xs">Should be in GCash</span>
                        <span class="text-xs font-semibold tabular-nums text-gray-900 whitespace-nowrap" x-text="peso(afterPuhunan - afterUtang)"></span>
                    </div>
                </div>
            </div>

            <p x-show="problem" x-cloak class="flex items-start gap-1.5 text-xs text-red-600 font-medium">
                <svg class="w-4 h-4 shrink-0 mt-px" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/>
                </svg>
                <span x-text="problem"></span>
            </p>

            <div class="flex gap-3 pt-1">
                <button type="button" @click="open = false"
                        class="flex-1 py-2.5 rounded-xl font-semibold text-gray-700 bg-gray-100 hover:bg-gray-200 hover:text-gray-900 transition active:scale-95">
                    Cancel
                </button>
                <button type="submit"
                        :disabled="!! blockedReason || submitting"
                        class="flex-1 py-2.5 rounded-xl font-semibold text-white bg-gcash-600 hover:bg-gcash-700 shadow-md transition active:scale-95 disabled:opacity-40 disabled:cursor-not-allowed disabled:active:scale-100">
                    <span x-text="submitting ? 'Recording…' : 'Record'"></span>
                </button>
            </div>

            {{-- A disabled button should say why. --}}
            <p x-show="blockedReason && ! problem" x-cloak class="text-xs text-gray-500 text-center" x-text="blockedReason"></p>
        </form>
    </div>
</div>

<script>
    function gtrackOpenCapital(kind) {
        window.dispatchEvent(new CustomEvent('open-capital', { detail: { kind: kind } }));
    }

    function capitalEntryModal(config) {
        const round2 = (value) => Math.round((value + Number.EPSILON) * 100) / 100;
        const reopen = config.old.kind !== null && config.old.kind !== undefined;

        return {
            open: reopen,
            serverErrors: reopen,
            submitting: false,

            kind: reopen ? config.old.kind : 'borrow',
            amount: reopen && config.old.amount !== null && config.old.amount !== '' ? Number(config.old.amount) : null,
            source: reopen ? (config.old.source || '') : '',
            purpose: reopen ? (config.old.purpose || '') : '',
            note: reopen ? (config.old.note || '') : '',

            balances: { gcash: config.gcash, cash: config.cash },
            puhunan: config.puhunan,
            utang: config.utang,
            dayOpen: config.dayOpen,

            init() {
                this.$watch('amount', () => (this.serverErrors = false));
                this.$watch('source', () => (this.serverErrors = false));
            },

            openFresh(kind) {
                this.kind = kind;
                this.amount = null;
                this.source = '';
                this.purpose = '';
                this.note = '';
                this.serverErrors = false;
                this.submitting = false;
                this.open = true;
                this.$nextTick(() => this.$refs.amount.focus());
            },

            pickPurpose(value) {
                this.purpose = this.purpose === value ? '' : value;

                // Bills leave through the wallet; groceries are usually cash.
                if (this.purpose && ! this.source) {
                    this.source = (this.purpose === 'kuryente' || this.purpose === 'tubig') ? 'gcash' : 'cash';
                }
            },

            // ---- Wording -------------------------------------------------------

            get title() {
                return { borrow: 'Borrow', repay: 'Repay debt', capital: 'Add capital' }[this.kind];
            },

            get subtitle() {
                return {
                    borrow: 'Money taken out of the GCash capital — groceries, bills, or anything else. (Kumuha sa puhunan)',
                    repay: 'Money put back, from earnings or from the store. (Nagbayad ng utang)',
                    capital: 'The capital grew — savings, or earnings kept in. (Dagdag puhunan)',
                }[this.kind];
            },

            get sourceQuestion() {
                return { borrow: 'Where did it come from?', repay: 'Where did it go?', capital: 'Where did it go?' }[this.kind];
            },

            get sourceOptions() {
                const hints = {
                    borrow: { cash: 'Kinuha sa cash on hand', gcash: 'Binawas sa wallet (bills, send)' },
                    repay: { cash: 'Inilagay sa cash on hand', gcash: 'Pumasok sa wallet' },
                    capital: { cash: 'Idinagdag sa cash on hand', gcash: 'Pumasok sa wallet' },
                }[this.kind];

                const options = [
                    { value: 'cash', label: 'Cash (blue box)', hint: hints.cash },
                    { value: 'gcash', label: 'GCash wallet', hint: hints.gcash },
                ];

                if (this.kind !== 'borrow') {
                    options.push({
                        value: 'none',
                        label: 'Nothing moves',
                        hint: this.kind === 'repay'
                            ? 'Galing sa tubo na nasa wallet na — nababawasan lang ang utang'
                            : 'Tubo na iniipon — hindi gumagalaw ang pera',
                    });
                }

                return options;
            },

            // ---- Maths (mirrors CapitalEntry) ----------------------------------

            get signed() {
                const amount = Number(this.amount) || 0;
                return this.kind === 'borrow' ? -amount : amount;
            },

            get afterUtang() {
                const amount = Number(this.amount) || 0;
                if (this.kind === 'borrow') return round2(this.utang + amount);
                if (this.kind === 'repay') return round2(this.utang - amount);
                return this.utang;
            },

            get afterPuhunan() {
                const amount = Number(this.amount) || 0;
                return this.kind === 'capital' ? round2(this.puhunan + amount) : this.puhunan;
            },

            // ---- Checks (the server enforces these for real) -------------------

            get amountError() {
                if (this.amount === null || this.amount === '') return null;

                const value = Number(this.amount);

                if (Number.isNaN(value)) return 'Enter a valid number.';
                if (value < 0) return 'Amount cannot be negative.';
                if (value === 0) return 'Amount must be more than ₱0.';
                if (value > 10000000) return 'Amount cannot exceed ₱10,000,000.';

                return null;
            },

            get problem() {
                const amount = Number(this.amount) || 0;
                if (amount <= 0) return null;

                if (this.kind === 'repay' && amount > this.utang + 0.001) {
                    return 'The debt is only ' + this.peso(this.utang) + ' — you can\'t repay ' + this.peso(amount) + '.';
                }

                // Moving cash or GCash changes the till, so a day must be open.
                if (this.source && this.source !== 'none' && ! this.dayOpen) {
                    return 'Start the day on the Dashboard first — this moves cash or GCash.';
                }

                if (this.kind === 'borrow' && this.source) {
                    const have = this.source === 'gcash' ? this.balances.gcash : this.balances.cash;
                    if (round2(have - amount) < 0) {
                        return (this.source === 'gcash' ? 'Not enough GCash' : 'Not enough cash on hand')
                            + ' — you need ' + this.peso(amount) + ' but have ' + this.peso(have) + '.';
                    }
                }

                return null;
            },

            get blockedReason() {
                if (! this.amount) return 'Enter the amount to continue.';
                if (this.amountError) return this.amountError;
                if (! this.source) return 'Choose where the money came from or went to.';
                if (this.problem) return this.problem;
                return null;
            },

            peso(value) {
                return '₱' + Number(value).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            },
        };
    }
</script>
