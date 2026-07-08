<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 28px; }
        body { font-family: DejaVu Sans, sans-serif; color: #1e293b; font-size: 9px; }
        h1 { margin: 0 0 4px; font-size: 18px; color: #1d4ed8; }
        .meta { margin-bottom: 16px; color: #64748b; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #1d4ed8; color: white; text-align: left; }
        th, td { border: 1px solid #cbd5e1; padding: 5px; vertical-align: top; }
        tr:nth-child(even) td { background: #f8fafc; }
        .footer { margin-top: 12px; color: #64748b; }
    </style>
</head>
<body>
    <h1>{{ $title }}</h1>
    <div class="meta">Periode {{ $filters['date_from'] }} sampai {{ $filters['date_to'] }} · Dicetak {{ now()->format('d-m-Y H:i') }}</div>
    <table>
        <thead><tr>@foreach($headings as $heading)<th>{{ $heading }}</th>@endforeach</tr></thead>
        <tbody>
            @forelse($rows as $row)
                <tr>@foreach($row as $cell)<td>{{ $cell }}</td>@endforeach</tr>
            @empty
                <tr><td colspan="{{ count($headings) }}">Tidak ada data.</td></tr>
            @endforelse
        </tbody>
    </table>
    <div class="footer">Total data: {{ number_format($rows->count()) }} · Mantau Umroh</div>
</body>
</html>
