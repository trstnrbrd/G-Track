@props(['value'])

{{-- The weak → strong bar under a new-password field.

     `value` is the name of the Alpine variable holding the password. The
     scoring lives in resources/js/password-strength.js. It is advice only —
     the server just requires 8 characters.

     Colour carries meaning: red is weak, amber a warning, blue (the app's
     accent) is good or strong. Green is reserved for physical cash. --}}
<div x-data="{ get s() { return window.gtrackPasswordStrength({{ $value }}); } }"
     x-show="s.level > 0" x-cloak class="mt-2" role="status" aria-live="polite">
    <div class="flex gap-1" aria-hidden="true">
        <template x-for="i in 4" :key="i">
            <span class="h-1.5 flex-1 rounded-full transition-colors duration-200"
                  :class="i <= s.level
                      ? { 'bg-red-500': s.tone === 'red', 'bg-amber-500': s.tone === 'amber', 'bg-gcash-600': s.tone === 'blue' }
                      : 'bg-gray-200'"></span>
        </template>
    </div>
    <p class="mt-1.5 flex items-center justify-between gap-3 text-xs">
        <span class="font-semibold"
              :class="{ 'text-red-600': s.tone === 'red', 'text-amber-600': s.tone === 'amber', 'text-gcash-600': s.tone === 'blue' }"
              x-text="s.label"></span>
        <span class="text-gray-500 text-right" x-text="s.hint"></span>
    </p>
</div>
