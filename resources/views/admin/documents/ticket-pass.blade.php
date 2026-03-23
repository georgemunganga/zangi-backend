<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1" name="viewport">
    <title>Ticket Pass {{ $ticket['code'] }}</title>
    <style>
        body {
            margin: 0;
            background: #f3f0eb;
            color: #172126;
            font-family: Poppins, Arial, sans-serif;
        }
        .page {
            max-width: 760px;
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
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            margin-top: 24px;
        }
        .panel {
            background: #f8f5f0;
            border-radius: 22px;
            padding: 18px 18px 16px;
        }
        .panel strong {
            display: block;
            font-size: 12px;
            letter-spacing: 0.16em;
            text-transform: uppercase;
            color: #8b7866;
            margin-bottom: 8px;
        }
        .code {
            margin-top: 24px;
            padding: 18px 20px;
            border-radius: 24px;
            background: #172126;
            color: #ffffff;
        }
        .code p {
            margin: 0;
        }
        .code .value {
            margin-top: 8px;
            font-size: 28px;
            font-weight: 700;
            letter-spacing: 0.08em;
        }
    </style>
</head>
<body>
    <main class="page">
        <section class="card">
            <div class="eyebrow">Admin Ticket Pass</div>
            <h1>{{ $ticket['eventTitle'] }}</h1>
            <p class="meta">
                Generated {{ $generatedAt }} for operational delivery and print fallback.
            </p>

            <div class="code">
                <p>Ticket Code</p>
                <p class="value">{{ $ticket['code'] }}</p>
            </div>

            <div class="grid">
                <div class="panel">
                    <strong>Holder</strong>
                    <div>{{ $ticket['holderName'] }}</div>
                    <div>{{ $ticket['buyerName'] }}</div>
                    <div>{{ $ticket['email'] ?: 'No email recorded' }}</div>
                    <div>{{ $ticket['phone'] ?: 'No phone recorded' }}</div>
                </div>
                <div class="panel">
                    <strong>Event</strong>
                    <div>{{ $ticket['eventDateLabel'] }}</div>
                    <div>{{ $ticket['timeLabel'] ?: 'Time not recorded' }}</div>
                    <div>{{ $ticket['venue'] }}</div>
                    <div>{{ $ticket['ticketType'] }}</div>
                </div>
                <div class="panel">
                    <strong>Payment</strong>
                    <div>{{ $ticket['currency'] }} {{ number_format((float) $ticket['amount'], 2) }}</div>
                    <div>{{ $ticket['paymentMethod'] }}</div>
                    <div>Status: {{ ucfirst($ticket['paymentStatus']) }}</div>
                    <div>Source: {{ ucfirst(str_replace('_', ' ', $ticket['source'])) }}</div>
                </div>
                <div class="panel">
                    <strong>Lifecycle</strong>
                    <div>Issued: {{ $ticket['issuedAt'] ?: 'Not recorded' }}</div>
                    <div>Used: {{ $ticket['usedAt'] ?: 'Not recorded' }}</div>
                    <div>Delivery: {{ $ticket['deliveryMethod'] }}</div>
                    <div>State: {{ ucfirst($ticket['status']) }}</div>
                </div>
            </div>
        </section>
    </main>
</body>
</html>
