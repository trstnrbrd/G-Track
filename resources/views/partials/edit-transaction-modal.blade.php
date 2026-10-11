{{-- Edit a recorded transaction's mobile number and reference.

     Staff often record one half at the counter and the other later (a cash in
     gets its reference after the money is sent; a cash out gets its number at
     the end of the day). Only those two fields are editable on purpose: the
     amount, type and fee already moved the balances, so they are shown locked.

     Opened with gtrackEditTransaction(payload) — see <x-edit-transaction-button>.
     Posts to transactions.update; the server re-checks everything.

     Needs the page to be inside <x-app-layout> (Alpine + CSRF). --}}

@php
    // A failed save reloads the page. Reopen the modal on the same row, with
    // what was typed, so the error appears where the fix is.
    $failedEdit = $errors->editTransaction->any()
        ? \App\Models\Transaction::find(old('transaction_id'))
        : null;
@endphp

<div x-data="editTransactionModal({
        failed: @js($failedEdit?->editPayload()),
        draft: @js(['mobile' => old('mobile_number'), 'reference' => old('reference_number')]),
        rejected: @js([
            'mobile' => $errors->editTransaction->has('mobile_number'),
            'reference' => $errors->editTransaction->has('reference_number'),
        ]),
     })"
     x-show="open"
     x-cloak
     @edit-transaction.window="show($event.detail)"
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
            <h3 class="text-xl font-bold text-gray-900">Edit details</h3>
            <button type="button" @click="open = false" aria-label="Close" class="text-gray-400 hover:text-gray-600 transition">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        {{-- Which row this is, and why the money isn't editable. --}}
        <div class="rounded-xl border border-gray-200 bg-gray-50 px-3 py-2.5 mb-4">
            <div class="flex items-center justify-between">
                <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-semibold"
                      :class="type === 'cash_in' ? 'bg-green-100 text-green-700' : 'bg-blue-100 text-blue-700'"
                      x-text="type === 'cash_in' ? 'Cash In' : 'Cash Out'"></span>
                <span class="text-sm font-bold text-gray-900 tabular-nums" x-text="amount"></span>
            </div>
            <p class="mt-1.5 text-xs text-gray-500">Amount, type and fee can't be changed here — they've already moved the balances.</p>
        </div>

        <form method="POST" :action="action" class="space-y-4" @submit="submitting = true">
            @csrf
            @method('PATCH')
            {{-- Lets the page reopen on this row if the server rejects the save. --}}
            <input type="hidden" name="transaction_id" :value="id">

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Customer Mobile Number
                    <span x-show="type === 'cash_in'" class="text-red-500">*</span>
                    <span x-show="type === 'cash_out'" class="text-gray-400 font-normal">(optional)</span>
                </label>
                <div class="relative">
                    <input type="tel" name="mobile_number" x-model="mobile" @input="serverErrors = false"
                        inputmode="numeric" autocomplete="off" placeholder="09xxxxxxxxx" maxlength="13"
                        class="w-full px-3 py-2 pr-9 border rounded-lg focus:ring-2 transition font-mono"
                        :class="mobileBad
                            ? 'border-red-400 focus:ring-red-400 focus:border-red-400'
                            : (mobileValid ? 'border-green-400 focus:ring-green-400 focus:border-green-400'
                                           : 'border-gray-300 focus:ring-gcash-500 focus:border-gcash-500')">

                    <span x-show="mobileValid && ! mobileBad" x-cloak class="absolute right-3 top-1/2 -translate-y-1/2 text-green-500">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                        </svg>
                    </span>
                    <span x-show="mobileBad" x-cloak class="absolute right-3 top-1/2 -translate-y-1/2 text-red-500">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/>
                        </svg>
                    </span>
                </div>

                <p x-show="mobileError" x-cloak class="mt-1 text-xs text-red-600" x-text="mobileError"></p>
                @error('mobile_number', 'editTransaction')
                    <p x-show="serverErrors" x-cloak class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Reference Number
                    <span x-show="type === 'cash_out'" class="text-red-500">*</span>
                    <span x-show="type === 'cash_in'" class="text-gray-400 font-normal">(optional)</span>
                </label>
                <input type="text" name="reference_number" x-model="reference" @input="serverErrors = false"
                    placeholder="GCash reference number" maxlength="50" autocomplete="off"
                    class="w-full px-3 py-2 border rounded-lg focus:ring-2 transition font-mono placeholder:font-sans"
                    :class="referenceBad
                        ? 'border-red-400 focus:ring-red-400 focus:border-red-400'
                        : 'border-gray-300 focus:ring-gcash-500 focus:border-gcash-500'">

                <p x-show="referenceError" x-cloak class="mt-1 text-xs text-red-600" x-text="referenceError"></p>
                @error('reference_number', 'editTransaction')
                    <p x-show="serverErrors" x-cloak class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex gap-3 pt-1">
                <button type="button" @click="open = false"
                        class="flex-1 py-2.5 rounded-xl font-semibold text-gray-700 bg-gray-100 hover:bg-gray-200 hover:text-gray-900 transition active:scale-95">
                    Cancel
                </button>
                <button type="submit"
                        :disabled="blocked || submitting"
                        class="flex-1 py-2.5 rounded-xl font-semibold text-white bg-gcash-600 hover:bg-gcash-700 shadow-md transition active:scale-95 disabled:opacity-40 disabled:cursor-not-allowed disabled:active:scale-100">
                    <span x-text="submitting ? 'Saving…' : 'Save changes'"></span>
                </button>
            </div>

            {{-- A disabled button should say why. Field problems are already
                 spelled out above, so this only covers "nothing changed". --}}
            <p x-show="unchanged && ! mobileError && ! referenceError" x-cloak class="text-xs text-gray-500 text-center">
                Change the number or the reference to save.
            </p>
        </form>
    </div>
</div>

<script>
    function gtrackEditTransaction(payload) {
        window.dispatchEvent(new CustomEvent('edit-transaction', { detail: payload }));
    }

    function editTransactionModal(config) {
        return {
            open: false,
            submitting: false,
            serverErrors: false,

            id: null,
            action: '',
            type: 'cash_in',
            amount: '',
            mobile: '',
            reference: '',
            original: { mobile: '', reference: '' },

            init() {
                // The server rejected the last save: reopen with what was typed.
                if (config.failed) {
                    this.show(config.failed, config.draft);
                    this.serverErrors = true;
                }
            },

            show(txn, draft = null) {
                this.id = txn.id;
                this.action = txn.url;
                this.type = txn.type;
                this.amount = txn.amount;
                this.original = { mobile: txn.mobile || '', reference: txn.reference || '' };
                this.mobile = draft ? (draft.mobile || '') : this.original.mobile;
                this.reference = draft ? (draft.reference || '') : this.original.reference;
                this.serverErrors = false;
                this.submitting = false;
                this.open = true;
            },

            // ---- Mobile number -------------------------------------------------
            // Mirrors UpdateTransactionRequest and the normalisation in
            // NormalisesTransactionInput (same rule as the record modal).

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

            get mobileError() {
                if (this.mobileDigits.length === 0) {
                    return this.type === 'cash_in'
                        ? 'Required for a cash in — that\'s where the money was sent.'
                        : null;
                }
                if (this.mobileValid) return null;

                const number = this.mobileNormalised;

                if (! number.startsWith('0') && ! number.startsWith('9')) {
                    return 'Must start with 09 (e.g. 09171234567).';
                }
                if (number.length < 11) return number.length + ' of 11 digits.';
                if (number.length > 11) return number.length + ' digits — a mobile number is 11.';

                return 'Must start with 09 (e.g. 09171234567).';
            },

            // ---- Reference number ----------------------------------------------

            get referenceError() {
                return this.type === 'cash_out' && this.reference.trim() === ''
                    ? 'Required for a cash out — it\'s the proof the money arrived.'
                    : null;
            },

            // ---- Field state ---------------------------------------------------
            // A field is "bad" for its own live rule, or because the server just
            // rejected it and the person hasn't touched anything since.

            get mobileBad() {
                return !! this.mobileError || (this.serverErrors && config.rejected.mobile);
            },

            get referenceBad() {
                return !! this.referenceError || (this.serverErrors && config.rejected.reference);
            },

            // ---- Save button ---------------------------------------------------

            get unchanged() {
                return this.mobileNormalised === this.original.mobile
                    && this.reference.trim() === this.original.reference;
            },

            get blocked() {
                return !! this.mobileError || !! this.referenceError || this.unchanged;
            },
        };
    }
</script>
