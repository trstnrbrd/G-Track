@props([
    'name',
    'model',
    'id' => null,
    'placeholder' => '',
    'autocomplete' => 'new-password',
    'invalid' => 'false',
])

{{-- A password field with the show/hide eye the login page has.

     It binds to a variable of the Alpine scope it sits in (`model`), so the
     page can validate it live and a <x-password-strength> can read it.
     `invalid` is an Alpine expression: truthy puts a red outline on the field.
     Extra attributes (e.g. @input) land on the <input>. --}}
<div x-data="{ show: false }" class="relative">
    <input
        id="{{ $id ?? $name }}"
        name="{{ $name }}"
        :type="show ? 'text' : 'password'"
        x-model="{{ $model }}"
        placeholder="{{ $placeholder }}"
        autocomplete="{{ $autocomplete }}"
        {{ $attributes->merge(['class' => 'w-full px-4 py-3 pr-12 rounded-xl border bg-gray-50 text-gray-900 text-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition']) }}
        :class="{{ $invalid }} ? 'border-red-400 ring-1 ring-red-200' : 'border-gray-200'"
    />
    <button
        type="button"
        @click="show = ! show"
        :aria-label="show ? 'Hide password' : 'Show password'"
        class="absolute inset-y-0 right-0 flex items-center pr-4 text-gray-400 hover:text-gray-600 transition focus:outline-none"
    >
        {{-- Eye (show) --}}
        <svg x-show="! show" xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12s3.75-7.5 9.75-7.5 9.75 7.5 9.75 7.5-3.75 7.5-9.75 7.5S2.25 12 2.25 12z" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
        </svg>
        {{-- Eye-off (hide) --}}
        <svg x-show="show" x-cloak xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 002.25 12s3.75 7.5 9.75 7.5c1.32 0 2.57-.26 3.72-.72M6.53 6.53A9.96 9.96 0 0112 4.5c6 0 9.75 7.5 9.75 7.5a13.46 13.46 0 01-2.51 3.47M6.53 6.53L3 3m3.53 3.53l3.94 3.94m0 0a3 3 0 104.24 4.24m-4.24-4.24l4.24 4.24m0 0L21 21" />
        </svg>
    </button>
</div>
