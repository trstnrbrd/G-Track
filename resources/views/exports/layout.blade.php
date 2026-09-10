<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $title }} · GTrack</title>
    <style>
        /*
         * Rendered by dompdf, which understands roughly CSS 2.1: tables, floats
         * and fixed positioning work; flexbox and grid do NOT. Lay out with tables.
         *
         * The font MUST be DejaVu. dompdf's default (Helvetica) has no peso sign,
         * so every amount would print as "?500.00". DejaVu ships with dompdf and
         * includes ₱ in every weight.
         */
        @page { margin: 16mm 14mm 18mm 14mm; }

        body {
            margin: 0;
            font-family: "DejaVu Sans", sans-serif;
            font-size: 8.5pt;
            line-height: 1.35;
            color: #111827;
        }

        .mono { font-family: "DejaVu Sans Mono", monospace; font-size: 8pt; }
        .muted { color: #6B7280; }
        .right { text-align: right; }

        /* ---- Footer, repeated on every page ---- */
        .footer {
            position: fixed;
            bottom: -11mm;
            left: 0;
            right: 0;
            padding-top: 2mm;
            border-top: 0.5pt solid #E5E7EB;
            font-size: 7.5pt;
            color: #9CA3AF;
        }
        .footer table { width: 100%; border-collapse: collapse; }

        /* ---- Title block ---- */
        .brand { font-size: 9pt; font-weight: bold; color: #1F5AE0; }
        h1 { margin: 1mm 0 0; font-size: 16pt; font-weight: bold; }
        .scope { margin-top: 1mm; font-size: 9pt; color: #4B5563; }

        /* ---- Summary figures ---- */
        table.summary { width: 100%; margin: 6mm 0; border-collapse: collapse; }
        table.summary td {
            padding: 3mm 4mm;
            vertical-align: top;
            border: 0.5pt solid #E5E7EB;
            border-top-width: 2pt;
        }
        /* Same color meaning as the app: green = cash, blue = GCash. */
        table.summary td.cash   { border-top-color: #127A49; }
        table.summary td.gcash  { border-top-color: #1F5AE0; }
        table.summary td.amber  { border-top-color: #D97706; }
        table.summary td.purple { border-top-color: #9333EA; }
        .label { font-size: 7pt; letter-spacing: 0.4pt; text-transform: uppercase; color: #6B7280; }
        .figure { margin-top: 1mm; font-size: 13pt; font-weight: bold; }

        /* ---- Data table ---- */
        table.data { width: 100%; border-collapse: collapse; }
        table.data thead { display: table-header-group; } /* repeat the header on every page */
        table.data th {
            padding: 2mm 2.5mm;
            text-align: left;
            font-size: 7pt;
            font-weight: bold;
            letter-spacing: 0.4pt;
            text-transform: uppercase;
            color: #6B7280;
            background: #F9FAFB;
            border-bottom: 0.75pt solid #D1D5DB;
        }
        table.data th.right { text-align: right; }
        table.data td { padding: 1.8mm 2.5mm; vertical-align: middle; border-bottom: 0.5pt solid #F0F0EE; }
        table.data tr { page-break-inside: avoid; }
        table.data tr.alt td { background: #FAFAF9; }
        .wrap { word-break: break-all; }
        .nowrap { white-space: nowrap; }

        .dot { display: inline-block; width: 5pt; height: 5pt; margin-right: 3pt; border-radius: 2.5pt; }
        .dot.cash  { background: #127A49; }
        .dot.gcash { background: #1F5AE0; }

        .pill { font-size: 7.5pt; font-weight: bold; }
        .pill.active { color: #1F5AE0; }
        .pill.closed { color: #6B7280; }
        .pill.auto   { color: #B45309; }

        .note {
            margin-top: 4mm;
            padding: 3mm 4mm;
            font-size: 8pt;
            color: #92400E;
            background: #FFFBEB;
            border: 0.5pt solid #FDE68A;
        }
        .empty { padding: 12mm 0; text-align: center; color: #6B7280; }
    </style>
</head>
<body>
    <div class="footer">
        <table>
            <tr>
                <td>
                    GTrack · {{ $title }} · Generated {{ $generatedAt->format('M j, Y g:i A') }}
                    @if ($generatedBy) by {{ $generatedBy }} @endif
                </td>
                {{-- "Page X of Y" is stamped here by ExportController::stampPageNumbers(). --}}
            </tr>
        </table>
    </div>

    <div class="brand">GTrack</div>
    <h1>{{ $title }}</h1>
    <div class="scope">{{ $scope }}</div>

    @yield('content')
</body>
</html>
