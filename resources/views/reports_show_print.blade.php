<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $reportPayload['title'] }} - {{ $company->name }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #1e293b;
            --accent-color: #2563eb;
            --border-color: #e2e8f0;
            --text-main: #334155;
            --text-muted: #64748b;
        }

        * { 
            box-sizing: border-box; 
            font-family: 'Cairo', sans-serif; 
        }

        body { 
            margin: 0; 
            background: #f8fafc; 
            color: var(--text-main); 
            padding: 40px; 
            line-height: 1.6;
        }

        .document-wrapper { 
            max-width: 1000px; 
            margin: 0 auto; 
            background: #fff; 
            padding: 50px; 
            border-radius: 4px; 
            box-shadow: 0 10px 25px rgba(0,0,0,0.05);
            position: relative;
            min-height: 1200px;
        }

        /* Header Styles */
        .doc-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid var(--primary-color);
            padding-bottom: 20px;
            margin-bottom: 30px;
        }

        .company-info h1 {
            margin: 0;
            font-size: 24px;
            color: var(--primary-color);
            font-weight: 800;
        }

        .company-info p {
            margin: 2px 0;
            font-size: 14px;
            color: var(--text-muted);
        }

        .document-title-box {
            text-align: left;
        }

        .document-title-box h2 {
            margin: 0;
            font-size: 28px;
            color: var(--accent-color);
            text-transform: uppercase;
        }

        /* Info Grid */
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            margin-bottom: 30px;
        }

        .info-section h3 {
            font-size: 14px;
            text-transform: uppercase;
            color: var(--text-muted);
            border-bottom: 1px solid var(--border-color);
            padding-bottom: 5px;
            margin-bottom: 10px;
        }

        .info-section p {
            margin: 4px 0;
            font-size: 15px;
            font-weight: 600;
        }

        /* Stats Cards */
        .stats-row {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: #f1f5f9;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
        }

        .stat-card .label {
            display: block;
            font-size: 12px;
            color: var(--text-muted);
            margin-bottom: 5px;
        }

        .stat-card .value {
            display: block;
            font-size: 18px;
            font-weight: 700;
            color: var(--primary-color);
        }

        /* Table Styles */
        table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-bottom: 40px;
        }

        thead th { 
            background: #f8fafc; 
            color: var(--primary-color); 
            padding: 12px 10px; 
            text-align: right; 
            font-size: 13px;
            border-bottom: 2px solid var(--border-color);
            font-weight: 700;
        }

        tbody td { 
            padding: 12px 10px; 
            border-bottom: 1px solid var(--border-color);
            font-size: 13px;
        }

        .number {
            font-family: 'Courier New', Courier, monospace;
            font-weight: 700;
            white-space: nowrap;
        }

        .running-balance {
            background: #fdfdfd;
            font-weight: 800;
            color: var(--accent-color);
        }

        /* Footer / Signatures */
        .doc-footer {
            margin-top: 60px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 100px;
            text-align: center;
        }

        .signature-box {
            border-top: 1px solid var(--text-muted);
            padding-top: 10px;
        }

        .signature-box p {
            margin: 0;
            font-size: 14px;
            color: var(--text-muted);
        }

        .stamp-area {
            height: 100px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px dashed var(--border-color);
            color: var(--border-color);
            border-radius: 50%;
            width: 100px;
            margin: 0 auto 10px auto;
            font-size: 12px;
        }

        @media print {
            body { padding: 0; background: #fff; }
            .document-wrapper { 
                max-width: 100%; 
                box-shadow: none; 
                padding: 20px; 
                min-height: auto;
            }
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    @php
        $formatMetric = static function ($value, string $format) use ($company): string {
            return match ($format) {
                'currency' => number_format((float) $value, 2) . ' ' . $company->currency,
                'number' => number_format((float) $value, 2),
                'date' => blank($value) ? '—' : \Illuminate\Support\Carbon::parse($value)->format('Y-m-d'),
                default => blank($value) ? '—' : (string) $value,
            };
        };
        $defaultValueFormat = $reportPayload['value_format'] ?? 'currency';
    @endphp

    <div class="document-wrapper">
        <!-- Header -->
        <header class="doc-header">
            <div class="company-info">
                <h1>{{ $company->name }}</h1>
                <p>الرقم الضريبي: {{ $company->tax_number ?? '—' }}</p>
                <p>{{ $company->address ?? '' }}</p>
            </div>
            <div class="document-title-box">
                <h2>{{ $reportPayload['title'] }}</h2>
                <p class="muted">تاريخ الإصدار: {{ now()->format('Y-m-d') }}</p>
            </div>
        </header>

        <!-- Context Info -->
        <div class="info-grid">
            <div class="info-section">
                <h3>بيانات العميل</h3>
                <p>{{ $reportPayload['customer']->name ?? 'جميع العملاء' }}</p>
                @if(isset($reportPayload['customer']->tax_number))
                    <p>الرقم الضريبي: {{ $reportPayload['customer']->tax_number }}</p>
                @endif
                <p>{{ $reportPayload['customer']->address ?? '' }}</p>
            </div>
            <div class="info-section" style="text-align: left;">
                <h3>فترة التقرير</h3>
                <p>{{ $reportPayload['date_range_label'] }}</p>
            </div>
        </div>

        <!-- Highlights -->
        <div class="stats-row">
            @foreach ($reportPayload['highlights'] as $highlight)
                <div class="stat-card">
                    <span class="label">{{ $highlight['label'] }}</span>
                    <span class="value">{{ $formatMetric($highlight['value'], $highlight['format'] ?? $defaultValueFormat) }}</span>
                </div>
            @endforeach
        </div>

        <!-- Transactions Table -->
        <table>
            <thead>
                <tr>
                    @foreach ($reportPayload['columns'] as $column)
                        <th style="{{ in_array($column['key'], ['debit', 'credit', 'balance']) ? 'text-align: left;' : '' }}">
                            {{ $column['label'] }}
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @forelse ($reportPayload['rows'] as $row)
                    <tr>
                        @foreach ($reportPayload['columns'] as $column)
                            @php
                                $columnKey = $column['key'];
                                $columnFormat = $column['format'] ?? ($columnKey === 'meta' ? 'text' : ($row['format'] ?? $defaultValueFormat));
                                $columnValue = $row[$columnKey] ?? '';
                                $isNumber = in_array($columnFormat, ['currency', 'number']);
                            @endphp
                            <td class="{{ $isNumber ? 'number' : '' }} {{ $columnKey === 'balance' ? 'running-balance' : '' }}" 
                                style="{{ $isNumber ? 'text-align: left;' : '' }}">
                                {{ $formatMetric($columnValue, $columnFormat) }}
                            </td>
                        @endforeach
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ count($reportPayload['columns']) }}" style="text-align: center; padding: 40px;">
                            {{ $reportPayload['empty_message'] }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <!-- Signatures -->
        <footer class="doc-footer">
            <div class="signature-section">
                <div class="stamp-area">الختم الرسمي</div>
                <div class="signature-box">
                    <p>توقيع المحاسب المسئول</p>
                </div>
            </div>
            <div class="signature-section">
                <div style="height: 100px;"></div> <!-- Spacer -->
                <div class="signature-box">
                    <p>اعتماد الإدارة</p>
                </div>
            </div>
        </footer>
    </div>

    @if ($printMode ?? false)
        <script>
            window.addEventListener('load', () => {
                setTimeout(() => window.print(), 500);
            });
        </script>
    @endif
</body>
</html>
