@php
    $appName = config('app.name', 'PlanB');
    $percent = $maxScore > 0 ? (int) round($score / $maxScore * 100) : 0;
    $accent = match ($readiness->color()) {
        'rose' => '#e11d48',
        'amber' => '#d97706',
        default => '#059669',
    };
    $answerLabel = fn (string $a) => \App\Support\Marketing\Nis2QuickCheckCatalog::answerLabel($a);
@endphp
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <title>NIS2-Auswertung</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; color: #18181b; font-size: 12px; line-height: 1.5; margin: 0; }
        .wrap { padding: 32px 40px; }
        h1 { font-size: 22px; margin: 0 0 4px; }
        h2 { font-size: 15px; margin: 24px 0 8px; border-bottom: 1px solid #e4e4e7; padding-bottom: 4px; }
        .muted { color: #71717a; }
        .badge { display: inline-block; padding: 4px 12px; border-radius: 999px; color: #fff; font-weight: bold; font-size: 12px; background: {{ $accent }}; }
        .scorebox { margin-top: 16px; padding: 16px; border: 1px solid #e4e4e7; border-radius: 10px; }
        .bar { height: 10px; background: #e4e4e7; border-radius: 999px; margin-top: 10px; overflow: hidden; }
        .bar > div { height: 10px; background: {{ $accent }}; width: {{ max($percent, 3) }}%; border-radius: 999px; }
        table { width: 100%; border-collapse: collapse; margin-top: 6px; }
        td { padding: 6px 8px; border-bottom: 1px solid #f4f4f5; vertical-align: top; }
        td.ans { text-align: right; white-space: nowrap; font-weight: bold; width: 90px; }
        .yes { color: #059669; } .partial { color: #d97706; } .no { color: #e11d48; }
        .rec { margin-top: 8px; padding: 10px 12px; background: #fff7ed; border-left: 3px solid #d97706; border-radius: 4px; font-size: 11px; }
        .footer { margin-top: 28px; padding-top: 12px; border-top: 1px solid #e4e4e7; font-size: 10px; color: #a1a1aa; }
    </style>
</head>
<body>
<div class="wrap">
    <div class="muted" style="font-size: 11px;">{{ $appName }} · NIS2 Quick-Check</div>
    <h1>Ihre NIS2-Auswertung</h1>
    @if ($lead->company_name)
        <div class="muted">für {{ $lead->company_name }}</div>
    @endif

    <div class="scorebox">
        <span class="badge">{{ $readiness->label() }}</span>
        <span style="float: right; font-weight: bold;">{{ $score }} / {{ $maxScore }} Punkte</span>
        <div class="bar"><div></div></div>
        <p style="margin: 12px 0 0;">{{ $readiness->description() }}</p>
    </div>

    <h2>Ihre Antworten im Detail</h2>
    @foreach ($dimensions as $dimension)
        @php($hasGap = collect($dimension['questions'])->contains(fn ($q) => ($answers[$q['key']] ?? 'no') !== 'yes'))
        <div style="margin-top: 14px;">
            <strong>{{ $dimension['title'] }}</strong>
            <table>
                @foreach ($dimension['questions'] as $question)
                    @php($ans = $answers[$question['key']] ?? 'no')
                    <tr>
                        <td>{{ $question['text'] }}</td>
                        <td class="ans {{ $ans }}">{{ $answerLabel($ans) }}</td>
                    </tr>
                @endforeach
            </table>
            @if ($hasGap)
                <div class="rec"><strong>Empfehlung:</strong> {{ $dimension['recommendation'] }}</div>
            @endif
        </div>
    @endforeach

    <div class="footer">
        Diese Auswertung ist eine unverbindliche Selbsteinschätzung und ersetzt keine Rechts- oder Sicherheitsberatung.
        Erstellt am {{ now()->format('d.m.Y') }} · {{ $appName }}
    </div>
</div>
</body>
</html>
