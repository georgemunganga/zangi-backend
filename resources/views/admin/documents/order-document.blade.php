<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1" name="viewport">
    <title>{{ ucfirst($documentKind) }} {{ $order['reference'] }}</title>
    <style>
        body {
            margin: 0;
            background: #f3f0eb;
            color: #172126;
            font-family: Poppins, Arial, sans-serif;
        }
        .page {
            max-width: 860px;
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
        .header {
            display: flex;
            justify-content: space-between;
            gap: 24px;
            align-items: start;
            flex-wrap: wrap;
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
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 24px;
        }
        th, td {
            padding: 14px 12px;
            text-align: left;
            border-bottom: 1px solid #e8dfd4;
            vertical-align: top;
        }
        th {
            font-size: 12px;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            color: #8b7866;
        }
        .totals {
            margin-top: 24px;
            display: flex;
            justify-content: flex-end;
        }
        .total-card {
            min-width: 260px;
            background: #172126;
            color: #ffffff;
            border-radius: 24px;
            padding: 18px 20px;
        }
        .notes {
            margin-top: 24px;
            background: #f8f5f0;
            border-radius: 22px;
            padding: 18px;
            color: #5d5349;
            line-height: 1.7;
        }
    </style>
</head>
<body>
    <main class="page">
        <section class="card">
            <div class="header">
                <div>
                    <div class="eyebrow">Admin {{ ucfirst($documentKind) }}</div>
                    <h1>{{ ucfirst($documentKind) }}</h1>
                    <p class="meta">
                        Reference {{ $order['reference'] }}<br>
                        Generated {{ $generatedAt }}
                    </p>
                </div>
                <div class="panel">
                    <strong>Customer</strong>
                    <div>{{ $order['customerName'] }}</div>
                    <div>{{ $order['email'] ?: 'No email recorded' }}</div>
                    <div>{{ $order['phone'] ?: 'No phone recorded' }}</div>
                </div>
            </div>

            <div class="grid">
                <div class="panel">
                    <strong>Order status</strong>
                    <div>{{ ucfirst($order['status']) }}</div>
                    <div>Payment: {{ ucfirst($order['paymentStatus']) }}</div>
                    <div>Type: {{ ucfirst(str_replace('_', ' ', $order['type'])) }}</div>
                </div>
                <div class="panel">
                    <strong>Fulfillment</strong>
                    <div>{{ $order['fulfillment'] }}</div>
                </div>
                <div class="panel">
                    <strong>Relationship</strong>
                    <div>{{ $order['customerType'] }}</div>
                    <div>{{ $order['relationshipType'] }}</div>
                    <div>Source: {{ ucfirst(str_replace('_', ' ', $order['source'])) }}</div>
                </div>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Kind</th>
                        <th>Qty</th>
                        <th>Unit Price</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($order['lines'] as $line)
                        <tr>
                            <td>{{ $line['label'] }}</td>
                            <td>{{ ucfirst($line['kind']) }}</td>
                            <td>{{ $line['quantity'] }}</td>
                            <td>{{ $order['currency'] }} {{ number_format((float) $line['unitPrice'], 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="totals">
                <div class="total-card">
                    <div>Total</div>
                    <div style="margin-top: 8px; font-size: 28px; font-weight: 700;">
                        {{ $order['currency'] }} {{ number_format((float) $order['total'], 2) }}
                    </div>
                    <div style="margin-top: 8px;">Payment method: {{ $order['paymentMethod'] }}</div>
                </div>
            </div>

            <div class="notes">
                <strong style="display:block; margin-bottom:8px; color:#8b7866; letter-spacing:0.16em; text-transform:uppercase; font-size:12px;">
                    Notes
                </strong>
                {{ $order['notes'] ?: 'No notes recorded for this order.' }}
            </div>
        </section>
    </main>
</body>
</html>
