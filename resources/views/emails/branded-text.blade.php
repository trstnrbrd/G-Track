{{-- Plain-text alternative for clients that don't show HTML. --}}
Hi {!! $name !!},

@foreach ($lines as $line)
{!! str_replace('**', '', $line) !!}

@endforeach
{!! $actionText !!}: {!! $actionUrl !!}

{!! $footnote !!}@if ($footnoteLink) {!! $footnoteLink['label'] !!}: {!! $footnoteLink['url'] !!}@endif
