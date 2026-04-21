<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>طباعة قيد محاسبي - {{ $journalEntry->entry_number }}</title>
    <!-- Use a minimal bootstrap for print -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #fff;
            color: #000;
        }
        
        .print-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }

        .print-header {
            border-bottom: 2px solid #000;
            padding-bottom: 15px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
        }

        .company-info h2 {
            margin: 0 0 5px 0;
            font-weight: bold;
        }

        .company-info p {
            margin: 0;
            color: #555;
        }

        .report-title {
            text-align: left;
        }

        .report-title h1 {
            margin: 0;
            font-size: 24px;
            font-weight: bold;
            color: #333;
        }

        .report-title p {
            margin: 5px 0 0 0;
            color: #666;
            font-size: 14px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin-bottom: 30px;
            background: #f9f9f9;
            padding: 15px;
            border-radius: 8px;
            border: 1px solid #eee;
        }

        .info-item strong {
            display: block;
            font-size: 13px;
            color: #666;
            margin-bottom: 3px;
        }

        .info-item span {
            font-size: 15px;
            font-weight: bold;
            color: #000;
        }

        .table th {
            background-color: #f2f2f2 !important;
            color: #000;
            border-bottom: 2px solid #000;
            font-size: 14px;
        }

        .table td {
            border-bottom: 1px solid #ddd;
            font-size: 14px;
            vertical-align: middle;
        }
        
        .totals-row td {
            font-weight: bold;
            background-color: #f9f9f9 !important;
            border-top: 2px solid #000;
        }

        .footer {
            margin-top: 50px;
            padding-top: 20px;
            border-top: 1px dashed #ccc;
            text-align: center;
            font-size: 12px;
            color: #666;
        }
        
        .signatures {
            margin-top: 60px;
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            text-align: center;
            gap: 20px;
        }
        
        .signature-box {
            border-top: 1px solid #000;
            padding-top: 10px;
            margin: 0 20px;
        }

        @media print {
            body {
                background: none;
            }
            .print-container {
                width: 100%;
                max-width: none;
                padding: 0;
                margin: 0;
            }
            @page {
                margin: 1.5cm;
            }
            .btn-print {
                display: none;
            }
        }
        
        .btn-print {
            position: fixed;
            bottom: 30px;
            right: 30px;
            z-index: 1000;
            padding: 10px 20px;
            border-radius: 5px;
            background: #2563eb;
            color: #fff;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            text-decoration: none;
            font-weight: bold;
            border: none;
            cursor: pointer;
        }
        
        .btn-print:hover {
            background: #1d4ed8;
        }
    </style>
</head>
<body>
    <button onclick="window.print()" class="btn-print">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-printer me-2" viewBox="0 0 16 16" style="display:inline-block; vertical-align: middle;">
          <path d="M2.5 8a.5.5 0 1 0 0-1 .5.5 0 0 0 0 1z"/>
          <path d="M5 1a2 2 0 0 0-2 2v2H2a2 2 0 0 0-2 2v3a2 2 0 0 0 2 2h1v1a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2v-1h1a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-1V3a2 2 0 0 0-2-2H5zM4 3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2H4V3zm1 5a2 2 0 0 0-2 2v1H2a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1h-1v-1a2 2 0 0 0-2-2H5zm7 2v3a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1z"/>
        </svg>
        طباعة القيد
    </button>

    <div class="print-container">
        <div class="print-header">
            <div class="company-info">
                <h2>{{ $company->name }}</h2>
                @if($company->tax_number)
                <p>الرقم الضريبي: {{ $company->tax_number }}</p>
                @endif
                @if($company->address || $company->city)
                <p>{{ $company->address }} {{ $company->city ? ' - '.$company->city : '' }}</p>
                @endif
            </div>
            <div class="report-title">
                <h1>قيد يومية</h1>
                <p>Journal Entry</p>
            </div>
        </div>

        <div class="info-grid">
            <div class="info-item">
                <strong>رقم القيد</strong>
                <span>{{ $journalEntry->entry_number }}</span>
            </div>
            <div class="info-item">
                <strong>تاريخ القيد</strong>
                <span>{{ \Illuminate\Support\Carbon::parse($journalEntry->entry_date)->format('Y-m-d') }}</span>
            </div>
            <div class="info-item">
                <strong>المرجع</strong>
                <span>{{ $journalEntry->reference ?: 'بدون مرجع' }}</span>
            </div>
            <div class="info-item">
                <strong>الحالة</strong>
                <span>{{ $journalEntry->status === 'draft' ? 'مسودة' : ($journalEntry->status === 'posted' ? 'مرحّل' : 'مستعاد') }}</span>
            </div>
            <div class="info-item" style="grid-column: span 2;">
                <strong>البيان / الوصف</strong>
                <span>{{ $journalEntry->description ?: '-' }}</span>
            </div>
        </div>

        <table class="table mb-0">
            <thead>
                <tr>
                    <th style="width: 15%">رقم الحساب</th>
                    <th style="width: 35%">اسم الحساب</th>
                    <th style="width: 25%">البيان</th>
                    <th style="width: 12.5%" class="text-start">مدين</th>
                    <th style="width: 12.5%" class="text-start">دائن</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($journalEntry->lines as $line)
                <tr>
                    <td>{{ $line->account?->code }}</td>
                    <td>{{ $line->account?->name_ar ?? $line->account?->name }}</td>
                    <td>{{ $line->description ?: '-' }}</td>
                    <td class="text-start">{{ number_format((float) $line->debit, 2) }}</td>
                    <td class="text-start">{{ number_format((float) $line->credit, 2) }}</td>
                </tr>
                @endforeach
                <tr class="totals-row">
                    <td colspan="3" class="text-end">الإجمالي</td>
                    <td class="text-start">{{ number_format((float) $journalEntry->total_debit, 2) }} {{ $company->currency }}</td>
                    <td class="text-start">{{ number_format((float) $journalEntry->total_credit, 2) }} {{ $company->currency }}</td>
                </tr>
            </tbody>
        </table>

        <div class="signatures">
            <div>
                <div class="signature-box">المُعِدّ</div>
                <div style="margin-top:5px; font-size:12px; color:#555;">{{ $journalEntry->creator?->name ?? '________' }}</div>
            </div>
            <div>
                <div class="signature-box">المُراجِع</div>
            </div>
            <div>
                <div class="signature-box">المُعتَمِد</div>
            </div>
        </div>

        <div class="footer">
            تمت الطباعة بواسطة {{ auth()->user()->name }} في {{ now()->format('Y-m-d H:i') }}
        </div>
    </div>
    
    <script>
        // Auto-print prompt when opened in a new tab if print=1 is passed
        window.onload = function() {
            setTimeout(function() {
                window.print();
            }, 500);
        };
    </script>
</body>
</html>
