@props(['date'])

{{-- A small heading between the days in a card list (phone view), so it is clear
     which day the cards below belong to. Place it when the day changes. --}}
<div class="flex items-center gap-2 pt-2 pb-1 first:pt-0" role="separator" aria-label="{{ \App\Support\DayLabel::for($date) }}">
    <span class="text-xs font-semibold text-gray-700">{{ \App\Support\DayLabel::for($date) }}</span>
    <span class="h-px flex-1 bg-gray-300" aria-hidden="true"></span>
</div>
