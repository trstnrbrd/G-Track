{{-- "Item paid via GCash" modal.

     Posts to store.sales.store. The preview mirrors StoreSale::deltasFor()
     (GCash +amount, cash −amount) and Balance::canAbsorb() for the drawer —
     the server re-checks both inside a locked transaction.

     Opened with gtrackOpenStoreSale(). Expects: $balance --}}

<div x-data="storeSaleModal({
        cash: {{ (float) $balance->cash_balance }},
        reopen: @js($errors->any() || session()->hasOldInput('amount')),
        old: @js(['amount' => old('amount'), 'reference' => old('reference_number'), 'note' => old('note')]),
     })"
     x-show="open"
     x-cloak
     @open-store-sale.window="openFresh()"
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
            <h3 class="text-xl font-bold text-gray-900">Item paid via GCash</h3>
            <button type="button" @click="open = false" aria-label="Close" class="text-gray-400 hover:text-gray-600 transition">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        <p class="text-sm text-gray-500 mb-4">Binayaran ng GCash ang paninda — ililipat ang cash sa tindahan.</p>

        <form method="POST" action="{{ route('store.sales.store') }}" class="space-y-4" @submit="submitting = true">
            @csrf

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Amount <span class="text-red-500">*</span></label>
                <div class="relative">
                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500 font-medium">₱</span>
                    <input type="number" name="amount" x-ref="amount" x-model.number="amount"
                        step="0.01" min="0" inputmode="decimal" placeholder="0.00"
                        class="w-full pl-8 pr-3 py-2 border rounded-lg focus:ring-2 transition"
                        :class="amountError || shortfall
                            ? 'border-red-400 focus:ring-red-400 focus:border-red-400'
                            : 'border-gray-300 focus:ring-gcash-500 focus:border-gcash-500'">
                </div>
                <p x-show="amountError" x-cloak class="mt-1 text-xs text-red-600" x-text="amountError"></p>
                @error('amount')<p x-show="serverErrors" x-cloak class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Reference Number <span class="text-gray-400 font-normal">(optional)</span>
                </label>
                <input type="text" name="reference_number" x-model="reference" @input="serverErrors = false"
                    placeholder="From the customer's GCash receipt" maxlength="50" autocomplete="off"
                    class="w-full px-3 py-2 border rounded-lg focus:ring-2 transition font-mono placeholder:font-sans"
                    :class="serverErrors && rejectedReference
                        ? 'border-red-400 focus:ring-red-400 focus:border-red-400'
                        : 'border-gray-300 focus:ring-gcash-500 focus:border-gcash-500'">
                @error('reference_number')<p x-show="serverErrors" x-cloak class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Item <span class="text-gray-400 font-normal">(optional)</span>
                </label>
                <input type="text" name="note" x-model="note" maxlength="100" autocomplete="off"
                    placeholder="e.g. 1 sack rice"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gcash-500 focus:border-gcash-500 transition">
                @error('note')<p x-show="serverErrors" x-cloak class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            {{-- What actually moves, so the physical step isn't forgotten. --}}
            <div x-show="amount > 0" x-cloak class="rounded-xl border border-gray-200 bg-gray-50 p-3 space-y-2">
                <p class="text-[11px] font-bold uppercase tracking-wide text-gray-500">What happens</p>

                <div class="space-y-1.5 text-sm">
                    <div class="flex items-center justify-between gap-3">
                        <span class="text-gray-600">GCash you receive</span>
                        <span class="font-bold tabular-nums whitespace-nowrap text-green-600" x-text="'+' + peso(amount)"></span>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <span class="text-gray-600">Cash to the store</span>
                        <span class="font-bold tabular-nums whitespace-nowrap text-red-600" x-text="'−' + peso(amount)"></span>
                    </div>
                    <p class="pt-1.5 border-t border-gray-200 text-xs text-gray-500">No service charge — this is a store sale, not a GCash service.</p>
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
                        :disabled="blocked || submitting"
                        class="flex-1 py-2.5 rounded-xl font-semibold text-white bg-gcash-600 hover:bg-gcash-700 shadow-md transition active:scale-95 disabled:opacity-40 disabled:cursor-not-allowed disabled:active:scale-100">
                    <span x-text="submitting ? 'Recording…' : 'Record sale'"></span>
                </button>
            </div>

            {{-- A disabled button should say why; field problems are already shown above. --}}
            <p x-show="! amount && ! amountError" x-cloak class="text-xs text-gray-500 text-center">Enter the item's amount to continue.</p>
        </form>
    </div>
</div>

<script>
    function gtrackOpenStoreSale() {
        window.dispatchEvent(new CustomEvent('open-store-sale'));
    }

    function storeSaleModal(config) {
        const round2 = (value) => Math.round((value + Number.EPSILON) * 100) / 100;

        return {
            open: config.reopen,
            submitting: false,
            serverErrors: config.reopen,
            rejectedReference: @js($errors->has('reference_number')),
            cash: config.cash,

            amount: config.old.amount !== null && config.old.amount !== '' ? Number(config.old.amount) : null,
            reference: config.old.reference || '',
            note: config.old.note || '',

            init() {
                // Any change to the amount means the server's old complaint is stale.
                this.$watch('amount', () => (this.serverErrors = false));
            },

            openFresh() {
                this.amount = null;
                this.reference = '';
                this.note = '';
                this.serverErrors = false;
                this.submitting = false;
                this.open = true;
                this.$nextTick(() => this.$refs.amount.focus());
            },

            // Mirrors 'gt:0' / 'max:1000000' in RecordStoreSaleRequest.
            get amountError() {
                if (this.amount === null || this.amount === '') return null;

                const value = Number(this.amount);

                if (Number.isNaN(value)) return 'Enter a valid number.';
                if (value < 0) return 'Amount cannot be negative.';
                if (value === 0) return 'Amount must be more than ₱0.';
                if (value > 1000000) return 'Amount cannot exceed ₱1,000,000.';

                return null;
            },

            // Mirrors Balance::canAbsorb() for StoreSale::deltasFor(): only the
            // drawer can run short. A courtesy check — the server enforces it.
            get shortfall() {
                const amount = Number(this.amount) || 0;

                if (amount > 0 && round2(this.cash - amount) < 0) {
                    return 'Not enough cash on hand — you need ' + this.peso(amount) + ' but the drawer only has ' + this.peso(this.cash) + '.';
                }
                return null;
            },

            get blocked() {
                return ! this.amount || !! this.amountError || !! this.shortfall;
            },

            peso(value) {
                return '₱' + Number(value).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            },
        };
    }
</script>
