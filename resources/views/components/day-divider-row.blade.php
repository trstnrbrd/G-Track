@props(['date', 'colspan' => 1])

{{-- The same divider for a table: a full-width row that names the day. --}}
<tr role="separator">
    <td colspan="{{ $colspan }}" class="bg-gray-50 px-4 py-2 text-xs font-semibold text-gray-700">
        {{ \App\Support\DayLabel::for($date) }}
    </td>
</tr>
