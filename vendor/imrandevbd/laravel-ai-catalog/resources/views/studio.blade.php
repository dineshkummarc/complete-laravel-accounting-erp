<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Laravel AI Catalog Studio</title>
    <style>
        :root { color-scheme: light dark; --bg:#0f172a; --card:#1e293b; --line:#334155; --text:#e2e8f0; --muted:#94a3b8; --accent:#14b8a6; --warn:#f59e0b; --bad:#f87171; }
        * { box-sizing: border-box; }
        body { margin:0; font-family: ui-sans-serif, system-ui, sans-serif; background:var(--bg); color:var(--text); }
        header { padding:1.25rem 2rem; border-bottom:1px solid var(--line); display:flex; justify-content:space-between; align-items:center; }
        header strong { color:var(--accent); }
        main { padding:1.5rem 2rem 3rem; display:grid; gap:1.25rem; }
        .grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); gap:1rem; }
        .card { background:var(--card); border:1px solid var(--line); border-radius:12px; padding:1rem 1.1rem; }
        h2 { font-size:0.95rem; margin:0 0 0.75rem; color:var(--muted); font-weight:600; letter-spacing:.04em; text-transform:uppercase; }
        table { width:100%; border-collapse:collapse; font-size:0.9rem; }
        th, td { text-align:left; padding:.45rem .3rem; border-bottom:1px solid var(--line); }
        .badge { display:inline-block; padding:.1rem .45rem; border-radius:999px; font-size:.75rem; background:#334155; }
        .badge.draft { background:#334155; }
        .badge.in_review { background:#854d0e; }
        .badge.approved { background:#14532d; }
        .badge.failed { background:#7f1d1d; }
        .muted { color:var(--muted); }
        a { color:var(--accent); }
    </style>
</head>
<body>
<header>
    <div><strong>Laravel AI Catalog</strong> Studio · review, bulk, cost</div>
    <div class="muted">Nothing auto-publishes</div>
</header>
<main>
    <section class="grid">
        <div class="card"><div class="muted">30-day spend</div><div style="font-size:1.6rem">${{ number_format($summary['cost_usd'], 4) }}</div></div>
        <div class="card"><div class="muted">Requests</div><div style="font-size:1.6rem">{{ $summary['requests'] }}</div></div>
        <div class="card"><div class="muted">Tokens</div><div style="font-size:1.6rem">{{ number_format($summary['tokens']) }}</div></div>
        <div class="card"><div class="muted">Failed</div><div style="font-size:1.6rem">{{ $summary['failed'] }}</div></div>
    </section>

    <section class="card">
        <h2>Review queue (confidence &lt; {{ $lowConfidence }} or draft)</h2>
        <table>
            <thead><tr><th>ID</th><th>Title</th><th>Status</th><th>Confidence</th><th>Flags</th></tr></thead>
            <tbody>
            @forelse ($queue as $row)
                <tr>
                    <td>{{ $row->id }}</td>
                    <td>{{ $row->listing()->title ?: '—' }}</td>
                    <td><span class="badge {{ $row->status?->value }}">{{ $row->status?->value }}</span></td>
                    <td>{{ $row->overall_confidence }}</td>
                    <td class="muted">{{ implode(', ', $row->flags ?? []) }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="muted">Queue is clear.</td></tr>
            @endforelse
            </tbody>
        </table>
    </section>

    <section class="card">
        <h2>Bulk jobs</h2>
        <table>
            <thead><tr><th>File</th><th>Status</th><th>Progress</th></tr></thead>
            <tbody>
            @forelse ($bulk as $job)
                <tr>
                    <td>{{ $job->filename }}</td>
                    <td><span class="badge">{{ $job->status }}</span></td>
                    <td>{{ $job->processed }}/{{ $job->total }} ({{ $job->progressPercent() }}%) · failed {{ $job->failed_count }}</td>
                </tr>
            @empty
                <tr><td colspan="3" class="muted">No bulk imports yet. <code>php artisan ai-catalog:bulk file.csv</code></td></tr>
            @endforelse
            </tbody>
        </table>
    </section>

    <section class="card">
        <h2>Recent generations</h2>
        <table>
            <thead><tr><th>When</th><th>Mode</th><th>Title</th><th>USD</th><th>Provider</th></tr></thead>
            <tbody>
            @forelse ($recent as $row)
                <tr>
                    <td class="muted">{{ $row->created_at?->diffForHumans() }}</td>
                    <td>{{ $row->input_mode?->value }}</td>
                    <td>{{ $row->listing()->title ?: '—' }}</td>
                    <td>${{ number_format((float) $row->cost_usd, 5) }}</td>
                    <td class="muted">{{ $row->provider }} {{ $row->model }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="muted">Generate with <code>AiCatalog::fromKeyword()</code></td></tr>
            @endforelse
            </tbody>
        </table>
    </section>
</main>
</body>
</html>
