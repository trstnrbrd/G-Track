{{-- The owner's shop settings and backup. Only included for the owner, and every
     route behind these forms is also guarded by the `owner` middleware.

     Expects: $shop (see SettingsController::shopSettings()).

     Saved values override config/gtrack.php; "Back to default" removes the
     override. A change only affects NEW transactions — past ones kept the fee
     they were recorded with. --}}

@php
    $input = 'px-3 py-2 rounded-lg border bg-gray-50 text-gray-900 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition';
    $backupAge = $shop['backup']['at']?->diffInDays(now());
    $backupStale = $shop['backup']['at'] === null || ((int) $backupAge) >= 7;

    $changed = fn ($setting) => $setting
        ? 'Last changed by '.($setting->updatedBy?->name ?? 'the owner').' · '.$setting->updated_at->format('M j, Y g:i A')
        : null;
@endphp

<section class="mt-5 overflow-hidden rounded-2xl border border-gray-200 bg-surface" aria-labelledby="shop-settings-heading">
    <div class="border-b border-gray-100 px-5 py-4 sm:px-6">
        <h3 id="shop-settings-heading" class="text-sm font-semibold text-gray-900">Shop</h3>
        <p class="mt-1 text-sm text-gray-500">Rates and rules for the whole shop. Only the owner sees this.</p>
    </div>

    <div class="divide-y divide-gray-100">

        {{-- ===== Service charge rates ===== --}}
        <div x-data="ratesEditor({ rows: @js($shop['brackets']), serverError: @js($errors->rates->first()) })" class="px-5 py-4 sm:px-6">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-sm font-medium text-gray-800">Service charge rates</p>
                    <p class="mt-0.5 text-sm text-gray-500">The fee for each amount. Used by the Cash In / Cash Out form and recorded with every transaction.</p>
                </div>
                <span class="shrink-0 rounded-full px-2.5 py-1 text-xs font-semibold {{ $shop['bracketsCustom'] ? 'bg-blue-50 text-blue-700' : 'bg-gray-100 text-gray-600' }}">
                    {{ $shop['bracketsCustom'] ? 'Custom' : 'Default' }}
                </span>
            </div>
            @if ($shop['bracketsCustom'] && $changed($shop['bracketsChange']))
                <p class="mt-1 text-xs text-gray-500">{{ $changed($shop['bracketsChange']) }}</p>
            @endif

            <form method="post" action="{{ route('settings.shop.rates') }}" @submit.prevent="confirmSave($event)" class="mt-4" novalidate>
                @csrf
                @method('put')

                <div class="space-y-2">
                    <template x-for="(row, i) in rows" :key="row.id">
                        <div :id="'rate-row-' + row.id" class="rounded-lg transition" :class="highlight === row.id ? 'ring-2 ring-blue-300 ring-offset-2' : ''">
                            <div class="flex items-center gap-2">
                                <span class="w-14 shrink-0 text-xs font-medium leading-tight text-gray-600" x-text="i === last ? 'Above ₱' + aboveText : 'Up to ₱'"></span>

                                <template x-if="i !== last">
                                    <input type="number" inputmode="decimal" min="1" step="any" x-model="row.up_to" :name="'brackets[' + i + '][up_to]'"
                                           aria-label="Upper limit" placeholder="0"
                                           class="{{ $input }} min-w-0 flex-1 tabular-nums" :class="rowError(i) ? 'border-red-400 ring-1 ring-red-200' : 'border-gray-200'">
                                </template>
                                <template x-if="i === last">
                                    <span class="min-w-0 flex-1 truncate rounded-lg bg-gray-100 px-3 py-2 text-sm text-gray-500">everything higher</span>
                                </template>

                                <span class="shrink-0 text-xs font-medium text-gray-600">fee ₱</span>
                                <input type="number" inputmode="decimal" min="0" step="any" x-model="row.fee" :name="'brackets[' + i + '][fee]'"
                                       aria-label="Fee" placeholder="0"
                                       class="{{ $input }} w-[4.5rem] shrink-0 tabular-nums" :class="rowError(i) ? 'border-red-400 ring-1 ring-red-200' : 'border-gray-200'">

                                <button type="button" @click="remove(i)" x-show="i !== last && rows.length > 1" x-cloak
                                        :aria-label="'Remove row ' + (i + 1)"
                                        class="shrink-0 rounded-lg p-1.5 text-gray-400 transition hover:bg-gray-100 hover:text-gray-600">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                                <span x-show="i === last || rows.length === 1" class="w-7 shrink-0"></span>
                            </div>
                            <p x-show="rowError(i)" x-cloak x-text="rowError(i)" class="mt-1 pl-16 text-xs font-medium text-red-500"></p>
                        </div>
                    </template>
                </div>

                {{-- A row that was removed by mistake can be put back. --}}
                <p x-show="removed" x-cloak class="mt-3 flex flex-wrap items-center gap-2 rounded-lg bg-gray-50 px-3 py-2 text-xs text-gray-600">
                    <span>Removed the <span class="font-semibold" x-text="removed ? removed.label : ''"></span> row.</span>
                    <button type="button" @click="undo()" class="font-semibold text-blue-600 hover:text-blue-700">Undo</button>
                </p>

                {{-- Limits edited out of order: one tap puts them right. --}}
                <p x-show="outOfOrder" x-cloak class="mt-3 flex flex-wrap items-center gap-2 rounded-lg bg-amber-50 px-3 py-2 text-xs text-amber-700">
                    <span>The limits aren't in order, lowest first.</span>
                    <button type="button" @click="sortRows()" class="font-semibold text-blue-600 hover:text-blue-700">Sort rows</button>
                </p>

                {{-- New rows go in by themselves, in the right place — type the
                     limit and fee here and it is slotted between the rows around it. --}}
                <div class="mt-3">
                    <button type="button" @click="openAdd()" x-show="! addOpen && rows.length < 12"
                            class="rounded-lg px-3 py-2 text-xs font-semibold text-blue-600 transition hover:bg-blue-50">
                        + Add a row
                    </button>

                    <div x-show="addOpen" x-cloak class="rounded-xl border border-gray-200 bg-gray-50 p-3">
                        <p class="text-xs font-semibold text-gray-700">New row <span class="font-normal text-gray-500">— it goes in the right place by itself</span></p>

                        <div class="mt-2 flex flex-wrap items-center gap-2">
                            <span class="text-xs font-medium text-gray-600">Up to ₱</span>
                            <input type="number" inputmode="decimal" min="1" step="any" x-ref="newLimit" x-model="newLimit"
                                   @keydown.enter.prevent="confirmAdd()" aria-label="New upper limit" placeholder="0"
                                   class="{{ $input }} w-28 tabular-nums" :class="newLimitError ? 'border-red-400 ring-1 ring-red-200' : 'border-gray-200'">
                            <span class="text-xs font-medium text-gray-600">fee ₱</span>
                            <input type="number" inputmode="decimal" min="0" step="any" x-model="newFee"
                                   @keydown.enter.prevent="confirmAdd()" aria-label="New fee" placeholder="0"
                                   class="{{ $input }} w-20 tabular-nums">
                        </div>

                        <p x-show="newLimitError" x-cloak x-text="newLimitError" class="mt-2 text-xs font-medium text-red-500"></p>

                        <div class="mt-3 flex flex-wrap items-center gap-2">
                            <button type="button" data-add-row @click="confirmAdd()" :disabled="!! addBlockedReason"
                                    class="rounded-lg bg-blue-600 px-3 py-2 text-xs font-semibold text-white transition hover:bg-blue-700 disabled:opacity-40 disabled:cursor-not-allowed">
                                Add row
                            </button>
                            <button type="button" @click="addOpen = false"
                                    class="rounded-lg px-3 py-2 text-xs font-semibold text-gray-600 transition hover:bg-gray-100">Cancel</button>
                            <p x-show="addBlockedReason && ! newLimitError" x-cloak x-text="addBlockedReason" class="text-xs text-gray-500"></p>
                        </div>
                    </div>
                </div>

                {{-- Try it before saving. --}}
                <div class="mt-3 flex flex-wrap items-center gap-2 rounded-xl border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm text-gray-600">
                    <span>Try an amount: ₱</span>
                    <input type="number" inputmode="decimal" min="1" x-model="preview" aria-label="Amount to try"
                           class="w-24 rounded-lg border border-gray-200 bg-surface px-2 py-1 text-sm tabular-nums focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <span>→ fee</span>
                    <span class="font-semibold text-gray-900 tabular-nums" x-text="previewFee === null ? '—' : '₱' + previewFee.toLocaleString('en-PH')"></span>
                </div>

                @if ($errors->rates->any())
                    <p x-show="serverError" x-cloak class="mt-2 text-xs font-medium text-red-500">{{ $errors->rates->first() }}</p>
                @endif

                <div class="mt-4 flex flex-wrap items-center gap-3">
                    <button type="submit" :disabled="!! blockedReason || submitting"
                            class="rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-700 disabled:opacity-40 disabled:cursor-not-allowed focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                        Save rates
                    </button>
                    <p x-show="blockedReason" x-cloak x-text="blockedReason" class="text-sm text-gray-500"></p>
                </div>
                <p class="mt-3 text-xs text-gray-500">New rates apply to new transactions only. Past ones keep the fee they were recorded with.</p>
            </form>

            @if ($shop['bracketsCustom'])
                <form method="post" action="{{ route('settings.shop.reset', 'rates') }}" class="mt-2"
                      @submit.prevent="Swal.fire({ title: 'Back to the default rates?', text: 'Your custom rates will be replaced by the original ones.', icon: 'question', showCancelButton: true, confirmButtonText: 'Use default', confirmButtonColor: '#1F5AE0' }).then(r => r.isConfirmed && $event.target.submit())">
                    @csrf
                    @method('delete')
                    <button type="submit" class="rounded-lg px-1 py-1 text-xs font-semibold text-gray-500 underline-offset-2 hover:text-gray-700 hover:underline">Back to default rates</button>
                </form>
            @endif
        </div>

        {{-- ===== Quick amounts ===== --}}
        <div x-data="quickAmountsEditor({ value: @js(implode(', ', $shop['quickAmounts'])) })" class="px-5 py-4 sm:px-6">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-sm font-medium text-gray-800">Quick amounts</p>
                    <p class="mt-0.5 text-sm text-gray-500">The one-tap buttons above the Amount field.</p>
                </div>
                <span class="shrink-0 rounded-full px-2.5 py-1 text-xs font-semibold {{ $shop['quickCustom'] ? 'bg-blue-50 text-blue-700' : 'bg-gray-100 text-gray-600' }}">
                    {{ $shop['quickCustom'] ? 'Custom' : 'Default' }}
                </span>
            </div>
            @if ($shop['quickCustom'] && $changed($shop['quickChange']))
                <p class="mt-1 text-xs text-gray-500">{{ $changed($shop['quickChange']) }}</p>
            @endif

            <form method="post" action="{{ route('settings.shop.quick-amounts') }}" class="mt-4" novalidate>
                @csrf
                @method('put')
                <label for="quick_amounts" class="block text-xs font-medium text-gray-600 mb-1.5">Amounts, separated by commas (up to 8)</label>
                <input id="quick_amounts" name="quick_amounts" type="text" x-model="value" autocomplete="off"
                       class="{{ $input }} w-full tabular-nums" :class="error ? 'border-red-400 ring-1 ring-red-200' : 'border-gray-200'">
                <p x-show="error" x-cloak x-text="error" class="mt-1.5 text-xs font-medium text-red-500"></p>
                @if ($errors->quickAmounts->any())
                    <p x-show="! error && ! touched" class="mt-1.5 text-xs font-medium text-red-500">{{ $errors->quickAmounts->first() }}</p>
                @endif

                <div class="mt-2 flex flex-wrap gap-1.5" aria-label="Preview of the buttons">
                    <template x-for="amount in amounts" :key="amount">
                        <span class="rounded-lg border border-gray-200 bg-surface px-2.5 py-1 text-xs font-semibold text-gray-600 tabular-nums" x-text="'₱' + amount.toLocaleString('en-PH')"></span>
                    </template>
                </div>

                <div class="mt-4 flex flex-wrap items-center gap-3">
                    <button type="submit" :disabled="!! blockedReason"
                            class="rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-700 disabled:opacity-40 disabled:cursor-not-allowed focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                        Save amounts
                    </button>
                    <p x-show="blockedReason && ! error" x-cloak x-text="blockedReason" class="text-sm text-gray-500"></p>
                </div>
            </form>

            @if ($shop['quickCustom'])
                <form method="post" action="{{ route('settings.shop.reset', 'quick-amounts') }}" class="mt-2">
                    @csrf
                    @method('delete')
                    <button type="submit" class="rounded-lg px-1 py-1 text-xs font-semibold text-gray-500 underline-offset-2 hover:text-gray-700 hover:underline">Back to default amounts</button>
                </form>
            @endif
        </div>

        {{-- ===== Auto-close ===== --}}
        <div x-data="autoCloseEditor({ hours: @js((string) $shop['autoClose']) })" class="px-5 py-4 sm:px-6">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-sm font-medium text-gray-800">Close a forgotten day after</p>
                    <p class="mt-0.5 text-sm text-gray-500">If End Day is never pressed, the day is closed automatically after this many hours. Make it longer than your longest day.</p>
                </div>
                <span class="shrink-0 rounded-full px-2.5 py-1 text-xs font-semibold {{ $shop['autoCloseCustom'] ? 'bg-blue-50 text-blue-700' : 'bg-gray-100 text-gray-600' }}">
                    {{ $shop['autoCloseCustom'] ? 'Custom' : 'Default' }}
                </span>
            </div>
            @if ($shop['autoCloseCustom'] && $changed($shop['autoCloseChange']))
                <p class="mt-1 text-xs text-gray-500">{{ $changed($shop['autoCloseChange']) }}</p>
            @endif

            <form method="post" action="{{ route('settings.shop.auto-close') }}" class="mt-4" novalidate>
                @csrf
                @method('put')
                <div class="flex items-center gap-2">
                    <input id="auto_close_hours" name="hours" type="number" inputmode="numeric" min="1" max="48" step="1" x-model="hours"
                           aria-label="Hours"
                           class="{{ $input }} w-24 tabular-nums" :class="error ? 'border-red-400 ring-1 ring-red-200' : 'border-gray-200'">
                    <span class="text-sm text-gray-600">hours</span>
                </div>
                <p x-show="error" x-cloak x-text="error" class="mt-1.5 text-xs font-medium text-red-500"></p>
                @if ($errors->autoClose->any())
                    <p x-show="! error && ! touched" class="mt-1.5 text-xs font-medium text-red-500">{{ $errors->autoClose->first() }}</p>
                @endif

                <div class="mt-4 flex flex-wrap items-center gap-3">
                    <button type="submit" :disabled="!! blockedReason"
                            class="rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-700 disabled:opacity-40 disabled:cursor-not-allowed focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                        Save
                    </button>
                    <p x-show="blockedReason && ! error" x-cloak x-text="blockedReason" class="text-sm text-gray-500"></p>
                </div>
            </form>

            @if ($shop['autoCloseCustom'])
                <form method="post" action="{{ route('settings.shop.reset', 'auto-close') }}" class="mt-2">
                    @csrf
                    @method('delete')
                    <button type="submit" class="rounded-lg px-1 py-1 text-xs font-semibold text-gray-500 underline-offset-2 hover:text-gray-700 hover:underline">Back to the default ({{ $shop['defaults']['autoClose'] }} hours)</button>
                </form>
            @endif
        </div>
    </div>
</section>

{{-- ===== Backup ===== --}}
<section class="mt-5 overflow-hidden rounded-2xl border border-gray-200 bg-surface" aria-labelledby="backup-heading">
    <div class="border-b border-gray-100 px-5 py-4 sm:px-6">
        <h3 id="backup-heading" class="text-sm font-semibold text-gray-900">Backup</h3>
        <p class="mt-1 text-sm text-gray-500">A copy of every transaction, balance, and account. Keep one somewhere other than this PC.</p>
    </div>

    <div class="px-5 py-4 sm:px-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="min-w-0">
                @if ($shop['backup']['at'])
                    <p class="text-sm font-medium {{ $backupStale ? 'text-amber-600' : 'text-gray-800' }}">
                        Last backup: {{ $shop['backup']['at']->diffForHumans() }}
                    </p>
                    <p class="mt-0.5 text-xs text-gray-500">
                        {{ $shop['backup']['at']->format('M j, Y g:i A') }}
                        · {{ $shop['backup']['via'] === 'download' ? 'downloaded' : 'saved automatically on this PC' }}
                        @if ($shop['backup']['rows'] !== null) · {{ number_format($shop['backup']['rows']) }} rows @endif
                    </p>
                @else
                    <p class="text-sm font-medium text-amber-600">No backup yet</p>
                    <p class="mt-0.5 text-xs text-gray-500">Download one now — it takes a few seconds.</p>
                @endif
            </div>
            <a href="{{ route('settings.backup') }}"
               class="inline-flex shrink-0 items-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
                Download backup
            </a>
        </div>

        <div class="mt-4 space-y-1.5 border-t border-gray-100 pt-4 text-xs text-gray-500">
            <p>
                A copy is also saved on this PC every night at 11:30 PM
                ({{ $shop['backup']['filesOnThisPc'] }} kept in <span class="font-mono">storage/app/private/backups</span>) —
                that needs the Task Scheduler set up, and it doesn't survive losing the PC.
            </p>
            <p>The file contains password hashes. Keep it private — don't send it by chat or email.</p>
            <p>To restore: <span class="font-mono">php artisan migrate:fresh</span> (without <span class="font-mono">--seed</span>), then import the file in phpMyAdmin.</p>
        </div>
    </div>
</section>

<script>
    // Service charge rates. Mirrors Transaction::serviceChargeFor() and the
    // rules in ShopSettings::validBrackets() so mistakes show as you type; the
    // server re-checks everything. The preview here uses the rows being
    // edited, so you can see what a rate would charge before saving it.
    function ratesEditor(config) {
        // Each row has an id so Alpine can tell rows apart as they move.
        let nextId = 1;

        const toRows = (list) => list.map((r) => ({
            id: nextId++,
            up_to: r.up_to === null ? '' : String(r.up_to),
            fee: String(r.fee),
        }));

        // What the server would receive: the rows without their ids.
        const plain = (rows) => rows.map((r) => ({ up_to: r.up_to, fee: r.fee }));

        return {
            rows: toRows(config.rows),
            saved: JSON.stringify(plain(toRows(config.rows))),
            serverError: config.serverError || '',
            preview: '1000',
            submitting: false,

            addOpen: false,
            newLimit: '',
            newFee: '',
            highlight: null,
            removed: null,

            get last() {
                return this.rows.length - 1;
            },

            num(value) {
                const n = Number(value);

                return value === '' || value === null || Number.isNaN(n) ? null : n;
            },

            // The last row reads "Above ₱{the row before it}".
            get aboveText() {
                const prev = this.rows.length > 1 ? this.num(this.rows[this.last - 1].up_to) : 0;

                return prev === null ? '…' : prev.toLocaleString('en-PH');
            },

            rowError(i) {
                const row = this.rows[i];
                const fee = this.num(row.fee);

                if (fee === null) return 'Enter the fee.';
                if (fee < 0) return "A fee can't be negative.";
                if (i === this.last) return '';

                const upTo = this.num(row.up_to);

                if (upTo === null) return 'Enter the upper limit.';
                if (upTo <= 0) return 'The limit must be more than ₱0.';

                const above = i === 0 ? 0 : this.num(this.rows[i - 1].up_to);

                if (above !== null && upTo <= above) {
                    return 'Must be more than ₱' + above.toLocaleString('en-PH') + ', the row above.';
                }

                return '';
            },

            get firstProblem() {
                for (let i = 0; i < this.rows.length; i++) {
                    const error = this.rowError(i);
                    if (error) return 'Row ' + (i + 1) + ': ' + error;
                }

                return '';
            },

            get unchanged() {
                return JSON.stringify(plain(this.rows)) === this.saved;
            },

            // Two limited rows where a later one is not higher than the one before.
            get outOfOrder() {
                const limits = this.rows.slice(0, this.last).map((r) => this.num(r.up_to)).filter((n) => n !== null);

                return limits.some((limit, i) => i > 0 && limit < limits[i - 1]);
            },

            get blockedReason() {
                if (this.firstProblem) return this.firstProblem;
                if (this.unchanged) return 'Change a rate to save.';

                return '';
            },

            get previewFee() {
                const amount = this.num(this.preview);

                if (amount === null || amount <= 0 || this.firstProblem) return null;

                for (let i = 0; i < this.rows.length; i++) {
                    const upTo = i === this.last ? null : this.num(this.rows[i].up_to);

                    if (upTo === null || amount <= upTo) return this.num(this.rows[i].fee);
                }

                return null;
            },

            // ---- adding: the new row slots itself in -----------------------------

            openAdd() {
                this.addOpen = true;
                this.newLimit = '';
                this.newFee = '';
                this.$nextTick(() => this.$refs.newLimit.focus());
            },

            get newLimitError() {
                if (this.newLimit === '') return '';

                const limit = this.num(this.newLimit);

                if (limit === null || limit <= 0) return 'Enter an amount of more than ₱0.';

                const taken = this.rows.slice(0, this.last).some((r) => this.num(r.up_to) === limit);

                return taken ? 'There is already a row up to ₱' + limit.toLocaleString('en-PH') + '.' : '';
            },

            get addBlockedReason() {
                if (this.newLimit === '') return 'Enter the upper limit.';
                if (this.newLimitError) return this.newLimitError;

                const fee = this.num(this.newFee);

                if (fee === null) return 'Enter the fee.';
                if (fee < 0) return "A fee can't be negative.";

                return '';
            },

            confirmAdd() {
                if (this.addBlockedReason) return;

                const limit = this.num(this.newLimit);
                const row = { id: nextId++, up_to: String(limit), fee: String(this.num(this.newFee)) };

                // Before the first row with a higher limit; otherwise at the end of
                // the limited rows, just above the catch-all.
                let at = this.last;

                for (let i = 0; i < this.last; i++) {
                    const upTo = this.num(this.rows[i].up_to);

                    if (upTo === null || upTo > limit) {
                        at = i;
                        break;
                    }
                }

                this.rows.splice(at, 0, row);
                this.removed = null;
                this.addOpen = false;
                this.newLimit = '';
                this.newFee = '';

                // Show where it went.
                this.highlight = row.id;
                setTimeout(() => (this.highlight = null), 2500);
                this.$nextTick(() => {
                    const el = document.getElementById('rate-row-' + row.id);
                    if (el) el.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
                });
            },

            // ---- removing, with a way back ---------------------------------------

            remove(i) {
                if (i === this.last) return;

                const row = this.rows[i];
                const limit = this.num(row.up_to);

                this.removed = {
                    row,
                    index: i,
                    label: limit === null ? 'blank' : '₱' + limit.toLocaleString('en-PH'),
                };
                this.rows.splice(i, 1);
            },

            undo() {
                if (! this.removed) return;

                const at = Math.min(this.removed.index, this.last);

                this.rows.splice(at, 0, this.removed.row);
                this.highlight = this.removed.row.id;
                setTimeout(() => (this.highlight = null), 2500);
                this.removed = null;
            },

            // Lowest limit first; a row with no limit yet stays at the end of the
            // limited rows. The catch-all never moves.
            sortRows() {
                const limited = this.rows.slice(0, this.last);
                const withLimit = limited.filter((r) => this.num(r.up_to) !== null)
                    .sort((a, b) => this.num(a.up_to) - this.num(b.up_to));
                const blank = limited.filter((r) => this.num(r.up_to) === null);

                this.rows = [...withLimit, ...blank, this.rows[this.last]];
            },

            confirmSave(event) {
                const form = event.target;

                if (this.blockedReason) return;

                Swal.fire({
                    title: 'Save these rates?',
                    text: 'New transactions will use them right away. Past transactions keep the fee they were recorded with.',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Save rates',
                    confirmButtonColor: '#1F5AE0',
                }).then((result) => {
                    if (result.isConfirmed) {
                        this.submitting = true;
                        form.submit();
                    }
                });
            },
        };
    }

    // Quick amounts: "100, 200, 500" → 1–8 different whole peso amounts.
    function quickAmountsEditor(config) {
        return {
            value: config.value,
            saved: config.value,
            touched: false,

            init() {
                this.$watch('value', () => (this.touched = true));
            },

            get parts() {
                return this.value.split(/[\s,]+/).filter((part) => part !== '');
            },

            get amounts() {
                return this.parts
                    .map(Number)
                    .filter((n) => ! Number.isNaN(n) && n >= 1 && n <= 1000000)
                    .filter((n, i, all) => all.indexOf(n) === i)
                    .sort((a, b) => a - b);
            },

            get error() {
                if (this.parts.length === 0) return 'Enter at least one amount.';
                if (this.parts.some((part) => Number.isNaN(Number(part)))) return 'Use numbers only, separated by commas.';
                if (this.parts.some((part) => Number(part) < 1 || Number(part) > 1000000)) return 'Each amount must be between ₱1 and ₱1,000,000.';
                if (this.parts.length > 8) return 'Up to 8 amounts.';
                if (new Set(this.parts.map(Number)).size !== this.parts.length) return 'Each amount can only appear once.';

                return '';
            },

            get blockedReason() {
                if (this.error) return this.error;
                if (this.amounts.join(',') === this.saved.split(/[\s,]+/).map(Number).sort((a, b) => a - b).join(',')) return 'Change an amount to save.';

                return '';
            },
        };
    }

    // Auto-close hours: a whole number, 1–48.
    function autoCloseEditor(config) {
        return {
            hours: config.hours,
            saved: config.hours,
            touched: false,

            init() {
                this.$watch('hours', () => (this.touched = true));
            },

            get error() {
                const value = String(this.hours).trim();

                if (value === '') return 'Enter the number of hours.';
                if (! /^\d+$/.test(value)) return 'Use a whole number of hours.';
                if (Number(value) < 1 || Number(value) > 48) return 'Choose between 1 and 48 hours.';

                return '';
            },

            get blockedReason() {
                if (this.error) return this.error;
                if (String(this.hours).trim() === String(this.saved)) return 'Change the hours to save.';

                return '';
            },
        };
    }
</script>
