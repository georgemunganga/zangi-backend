<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1" name="viewport">
    <title>Admin Report {{ ucfirst($report['period']) }}</title>
    <style>
        body {
            margin: 0;
            background: #f3f0eb;
            color: #172126;
            font-family: Poppins, Arial, sans-serif;
        }
        .page {
            max-width: 900px;
            margin: 0 auto;
            padding: 32px 20px 56px;
        }
        .card {
            background: #ffffff;
            border: 1px solid #d9d1c7;
            border-radius: 28px;
            padding: 28px;
            box-shadow: 0 18px 36px rgba(23, 33, 38, 0.08);
        }
        .eyebrow {
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.18em;
            text-transform: uppercase;
            color: #8b7866;
        }
        h1 {
            margin: 12px 0 8px;
            font-size: 34px;
            line-height: 1.1;
        }
        .meta {
            color: #5d5349;
            font-size: 15px;
            line-height: 1.7;
        }
        .grid {
            display: grid;
            gap: 14px;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            margin-top: 24px;
        }
        .panel {
            background: #f8f5f0;
            border-radius: 22px;
            padding: 18px;
        }
        .panel strong {
            display: block;
            font-size: 12px;
            letter-spacing: 0.16em;
            text-transform: uppercase;
            color: #8b7866;
            margin-bottom: 8px;
        }
        .section-title {
            margin-top: 28px;
            font-size: 14px;
            font-weight: 700;
            letter-spacing: 0.16em;
            text-transform: uppercase;
            color: #8b7866;
        }
    </style>
</head>
<body>
    <main class="page">
        <section class="card">
            <div class="eyebrow">Admin Report</div>
            <h1>{{ ucfirst($report['period']) }} Summary</h1>
            <p class="meta">Generated {{ $generatedAt }} for operational reporting and print review.</p>

            <div class="section-title">Cards</div>
            <div class="grid">
                @foreach ($report['cards'] as $card)
                    <div class="panel">
                        <strong>{{ $card['label'] }}</strong>
                        <div style="font-size:28px; font-weight:700;">{{ $card['value'] }}</div>
                        <div style="margin-top:8px; color:#5d5349; line-height:1.7;">{{ $card['detail'] }}</div>
                    </div>
                @endforeach
            </div>

            <div class="section-title">Operational Split</div>
            <div class="grid">
                @foreach ($report['splits'] as $split)
                    <div class="panel">
                        <strong>{{ $split['label'] }}</strong>
                        <div style="font-size:28px; font-weight:700;">{{ $split['value'] }}</div>
                        <div style="margin-top:8px; color:#5d5349; line-height:1.7;">{{ $split['detail'] }}</div>
                    </div>
                @endforeach
            </div>
        </section>
    </main>
</body>
</html>
