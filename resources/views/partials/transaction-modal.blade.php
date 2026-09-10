{{-- Cash In / Cash Out entry modal.

     Posts a real form to transactions.store. The service charge shown here is a
     preview for the operator — the server recomputes it from config/gtrack.php
     and ignores anything this form sends for it.

     Expects: $balance, $chargeBrackets --}}

<div x-data="transactionModal({
        brackets: @js($chargeBrackets),
        gcash: {{ (float) $balance->gcash_balance }},
        cash: {{ (float) $balance->cash_balance }},
     })"
     x-show="open"
     x-cloak
     @open-cash-modal.window="openWith($event.detail.mode)"
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

        <div class="flex items-center justify-between mb-4">
            <h3 class="text-xl font-bold text-gray-900" x-text="mode === 'cash_in' ? 'Cash In' : 'Cash Out'"></h3>
            <button type="button" @click="open = false" class="text-gray-400 hover:text-gray-600 transition">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <form method="POST" action="{{ route('transactions.store') }}" class="space-y-4" @submit="submitting = true">
            @csrf
            <input type="hidden" name="type" :value="mode">

            {{-- Mobile number: required for cash in (that's where the money goes),
                 optional for cash out (the customer sent to us). --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Customer Mobile Number
                    <span x-show="mode === 'cash_in'" class="text-red-500">*</span>
                    <span x-show="mode === 'cash_out'" class="text-gray-400 font-normal">(optional)</span>
                </label>
                <div class="relative">
                    {{-- Numbers we've served before, so suki customers are a tap
                         rather than eleven digits of typing. --}}
                    <input type="tel" name="mobile_number" x-model="mobile" inputmode="numeric"
                        list="known-customers" autocomplete="off"
                        placeholder="09xxxxxxxxx" maxlength="13"
                        class="w-full px-3 py-2 pr-9 border rounded-lg focus:ring-2 transition font-mono"
                        :class="mobileError
                            ? 'border-red-400 focus:ring-red-400 focus:border-red-400'
                            : (mobileValid ? 'border-green-400 focus:ring-green-400 focus:border-green-400'
                                           : 'border-gray-300 focus:ring-gcash-500 focus:border-gcash-500')">

                    {{-- Live status: a tick once the number is usable, a warning while it isn't. --}}
                    <span x-show="mobileValid" x-cloak class="absolute right-3 top-1/2 -translate-y-1/2 text-green-500">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                        </svg>
                    </span>
                    <span x-show="mobileError" x-cloak class="absolute right-3 top-1/2 -translate-y-1/2 text-red-500">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/>
                        </svg>
                    </span>
                </div>

                <datalist id="known-customers">
                    @foreach ($knownCustomers as $number)
                        <option value="{{ $number }}"></option>
                    @endforeach
                </datalist>

                <p x-show="mobileError" x-cloak class="mt-1 text-xs text-red-600" x-text="mobileError"></p>
                @error('mobile_number')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Amount <span class="text-red-500">*</span></label>
                <div class="relative">
                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500 font-medium">₱</span>
                    <input type="number" name="amount" x-model.number="amount" step="0.01" min="0" placeholder="0.00"
                        class="w-full pl-8 pr-3 py-2 border rounded-lg focus:ring-2 transition"
                        :class="amountError
                            ? 'border-red-400 focus:ring-red-400 focus:border-red-400'
                            : 'border-gray-300 focus:ring-gcash-500 focus:border-gcash-500'">
                </div>

                {{-- Most transactions at the counter are round numbers. --}}
                <div class="flex flex-wrap gap-1.5 mt-2">
                    @foreach ($quickAmounts as $quick)
                        <button type="button" @click="amount = {{ $quick }}"
                                :class="Number(amount) === {{ $quick }}
                                    ? 'bg-gcash-600 text-white border-gcash-600'
                                    : 'bg-surface text-gray-600 border-gray-200 hover:border-gcash-300 hover:text-gcash-600'"
                                class="px-2.5 py-1 rounded-lg border text-xs font-semibold transition active:scale-95">
                            ₱{{ number_format($quick) }}
                        </button>
                    @endforeach
                </div>

                <p x-show="amountError" x-cloak class="mt-1 text-xs text-red-600" x-text="amountError"></p>
                @error('amount')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            {{-- Which balance the fee lands in. This single field distinguishes
                 all four real-world scenarios. --}}
            <div>
                <label class="flex items-center justify-between text-sm font-medium text-gray-700 mb-1.5">
                    <span>Service Charge &middot; <span class="text-gcash-600 font-bold" x-text="peso(fee)"></span></span>
                    <button type="button" @click="showRates = ! showRates" class="text-xs text-gcash-600 hover:text-gcash-800 font-normal">
                        <span x-text="showRates ? 'Hide rates' : 'View rates'"></span>
                    </button>
                </label>

                <div class="grid grid-cols-2 gap-2">
                    <template x-for="option in chargeOptions" :key="option.value">
                        <button type="button" @click="chargePaidIn = option.value"
                                :class="chargePaidIn === option.value
                                    ? 'border-gcash-500 bg-gcash-50 text-gcash-900'
                                    : 'border-gray-200 bg-surface text-gray-600 hover:border-gray-300'"
                                class="text-left px-3 py-2.5 rounded-xl border-2 transition">
                            <span class="block text-xs font-bold" x-text="option.label"></span>
                            <span class="block text-[11px] mt-0.5 opacity-70" x-text="option.hint"></span>
                        </button>
                    </template>
                </div>
                <input type="hidden" name="charge_paid_in" :value="chargePaidIn">
                @error('charge_paid_in')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror

                <div x-show="showRates" x-cloak class="mt-2 p-3 bg-gcash-50 border border-gcash-200 rounded-lg">
                    <p class="text-xs font-semibold text-gcash-900 mb-2">Service Charge Rates</p>
                    <div class="text-xs text-gcash-800 space-y-1">
                        <template x-for="row in rateRows" :key="row.label">
                            <div class="flex justify-between">
                                <span x-text="row.label"></span>
                                <span class="font-semibold" x-text="peso(row.fee)"></span>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            {{-- Reference number: the customer's e-receipt is the only proof a
                 cash out actually arrived, so it is mandatory there. --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Reference Number
                    <span x-show="mode === 'cash_out'" class="text-red-500">*</span>
                    <span x-show="mode === 'cash_in'" class="text-gray-400 font-normal">(optional)</span>
                </label>
                <input type="text" name="reference_number" x-model="reference"
                    placeholder="GCash reference number" maxlength="50"
                    class="w-full px-3 py-2 border rounded-lg focus:ring-2 transition font-mono placeholder:font-sans"
                    :class="referenceError
                        ? 'border-red-400 focus:ring-red-400 focus:border-red-400'
                        : 'border-gray-300 focus:ring-gcash-500 focus:border-gcash-500'">

                <p x-show="referenceError" x-cloak class="mt-1 text-xs text-red-600" x-text="referenceError"></p>
                @error('reference_number')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            {{-- Live effect: what actually moves. Catching a wrong entry here is
                 far cheaper than reconciling it at closing time. --}}
            <div x-show="amount > 0" x-cloak class="rounded-xl border border-gray-200 bg-gray-50 p-3 space-y-2">
                <p class="text-[11px] font-bold uppercase tracking-wide text-gray-500">What happens</p>

                <div class="space-y-1.5 text-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-gray-600" x-text="cashLabel"></span>
                        <span class="font-bold tabular-nums" :class="deltas.cash >= 0 ? 'text-green-600' : 'text-red-600'"
                              x-text="signed(deltas.cash)"></span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-600" x-text="gcashLabel"></span>
                        <span class="font-bold tabular-nums" :class="deltas.gcash >= 0 ? 'text-green-600' : 'text-red-600'"
                              x-text="signed(deltas.gcash)"></span>
                    </div>
                    <div class="flex items-center justify-between pt-1.5 border-t border-gray-200">
                        <span class="text-gray-500 text-xs">You earn</span>
                        <span class="font-bold text-gcash-600 tabular-nums" x-text="peso(fee)"></span>
                    </div>
                </div>

                <p x-show="shortfall" x-cloak class="flex items-start gap-1.5 text-xs text-red-600 font-medium pt-1">
                    <svg class="w-4 h-4 shrink-0 mt-px" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/>
                    </svg>
                    <span x-text="shortfall"></span>
                </p>
            </div>

            <div class="flex gap-3 pt-1">
                <button type="button" @click="open = false"
                        class="flex-1 py-2.5 rounded-xl font-semibold text-gray-700 bg-gray-100 hover:bg-gray-200 hover:text-gray-900 transition active:scale-95">
                    Cancel
                </button>
                <button type="submit"
                        :disabled="! amount || !! amountError || !! shortfall || mobileBlocks || referenceBlocks || submitting"
                        class="flex-1 py-2.5 rounded-xl font-semibold text-white shadow-md transition active:scale-95 disabled:opacity-40 disabled:cursor-not-allowed disabled:active:scale-100"
                        :class="mode === 'cash_in' ? 'bg-cash-600 hover:bg-cash-700' : 'bg-gcash-600 hover:bg-gcash-700'">
                    <span x-text="submitting ? 'Recording…' : 'Record Transaction'"></span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function gtrackRound2(value) {
        return Math.round((value + Number.EPSILON) * 100) / 100;
    }

    function gtrackPeso(value) {
        return '₱' + Number(value).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function transactionModal(config) {
        return {
            open: false,
            submitting: false,
            showRates: false,
            mode: 'cash_in',
            amount: null,
            mobile: '',
            reference: '',
            chargePaidIn: 'cash',
            brackets: config.brackets,
            balances: { gcash: config.gcash, cash: config.cash },

            openWith(mode) {
                this.mode = mode;
                this.amount = null;
                this.mobile = '';
                this.reference = '';
                this.chargePaidIn = 'cash';
                this.showRates = false;
                this.submitting = false;
                this.open = true;
            },

            // ---- Amount --------------------------------------------------------
            // Mirrors the 'gt:0' / 'max:1000000' rules in StoreTransactionRequest.
            // The submit button is disabled either way, but a disabled button with
            // no explanation just reads as broken.

            get amountError() {
                if (this.amount === null || this.amount === '') return null;

                const value = Number(this.amount);

                if (Number.isNaN(value)) return 'Enter a valid number.';
                if (value < 0) return 'Amount cannot be negative.';
                if (value === 0) return 'Amount must be more than ₱0.';
                if (value > 1000000) return 'Amount cannot exceed ₱1,000,000.';

                return null;
            },

            // ---- Mobile number -------------------------------------------------
            // Mirrors StoreTransactionRequest::normaliseMobile() so the operator
            // never sees the field go green here and get rejected by the server.

            get mobileDigits() {
                return (this.mobile || '').replace(/\D/g, '');
            },

            get mobileNormalised() {
                const digits = this.mobileDigits;

                // +63 917 123 4567 / 63917… / 9171234567 all mean the same number.
                if (digits.startsWith('63') && digits.length === 12) return '0' + digits.slice(2);
                if (digits.length === 10 && digits.startsWith('9')) return '0' + digits;

                return digits;
            },

            get mobileValid() {
                return /^09\d{9}$/.test(this.mobileNormalised);
            },

            // Stays quiet until they have typed something, then updates on every
            // keystroke — including clearing itself the moment it becomes valid.
            get mobileError() {
                if (this.mobileDigits.length === 0 || this.mobileValid) return null;

                const number = this.mobileNormalised;

                if (! number.startsWith('0') && ! number.startsWith('9')) {
                    return 'Must start with 09 (e.g. 09171234567).';
                }
                if (number.length < 11) {
                    return number.length + ' of 11 digits.';
                }
                if (number.length > 11) {
                    return number.length + ' digits — a mobile number is 11.';
                }
                return 'Must start with 09 (e.g. 09171234567).';
            },

            get mobileBlocks() {
                // Required for a cash in; optional for a cash out, but a half-typed
                // number is still wrong.
                return this.mode === 'cash_in'
                    ? ! this.mobileValid
                    : this.mobileDigits.length > 0 && ! this.mobileValid;
            },

            // ---- Reference number ----------------------------------------------

            get referenceError() {
                return this.mode === 'cash_out' && this.reference.trim() === '' && this.amount > 0
                    ? 'Required for a cash out — ask for the customer\'s GCash receipt.'
                    : null;
            },

            get referenceBlocks() {
                return this.mode === 'cash_out' && this.reference.trim() === '';
            },

            // Mirrors Transaction::serviceChargeFor() — same brackets, same order.
            get fee() {
                const amount = Number(this.amount) || 0;
                if (amount <= 0) return 0;

                for (const bracket of this.brackets) {
                    if (bracket.up_to === null || amount <= bracket.up_to) return bracket.fee;
                }
                return 0;
            },

            // Mirrors Transaction::deltasFor().
            get deltas() {
                const amount = Number(this.amount) || 0;
                const gcashSign = this.mode === 'cash_in' ? -1 : 1;

                const deltas = { gcash: gcashSign * amount, cash: -gcashSign * amount };
                deltas[this.chargePaidIn === 'gcash' ? 'gcash' : 'cash'] += this.fee;

                return { gcash: gtrackRound2(deltas.gcash), cash: gtrackRound2(deltas.cash) };
            },

            get chargeOptions() {
                return this.mode === 'cash_in'
                    ? [
                        { value: 'cash', label: 'Paid in cash', hint: 'Dagdag sa bayad' },
                        { value: 'gcash', label: 'Deducted', hint: 'Bawas sa ipapadala' },
                    ]
                    : [
                        { value: 'cash', label: 'Paid in cash', hint: 'Hiwalay na bayad' },
                        { value: 'gcash', label: 'Sent via GCash', hint: 'Kasama sa sinend' },
                    ];
            },

            get cashLabel() {
                return this.deltas.cash >= 0 ? 'Cash you receive' : 'Cash you hand out';
            },

            get gcashLabel() {
                return this.deltas.gcash >= 0 ? 'GCash you receive' : 'GCash you send';
            },

            // Client-side courtesy check; the server enforces this for real.
            get shortfall() {
                const d = this.deltas;

                if (gtrackRound2(this.balances.gcash + d.gcash) < 0) {
                    return 'Not enough GCash — you need ' + gtrackPeso(Math.abs(d.gcash)) + ' but have ' + gtrackPeso(this.balances.gcash) + '.';
                }
                if (gtrackRound2(this.balances.cash + d.cash) < 0) {
                    return 'Not enough cash — you need ' + gtrackPeso(Math.abs(d.cash)) + ' but have ' + gtrackPeso(this.balances.cash) + '.';
                }
                return null;
            },

            get rateRows() {
                let floor = 0;
                return this.brackets.map((bracket) => {
                    const label = bracket.up_to === null
                        ? 'Above ' + gtrackPeso(floor)
                        : gtrackPeso(floor + 1) + ' – ' + gtrackPeso(bracket.up_to);
                    floor = bracket.up_to === null ? floor : bracket.up_to;
                    return { label: label, fee: bracket.fee };
                });
            },

            peso: (value) => gtrackPeso(value),
            signed: (value) => (value >= 0 ? '+' : '−') + gtrackPeso(Math.abs(value)),
        };
    }
</script>
