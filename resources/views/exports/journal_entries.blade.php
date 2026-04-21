<table>
    <thead>
        <tr>
            <th colspan="7" style="text-align: center; font-weight: bold; font-size: 16px;">
                {{ $companyName }} - سجل القيود المحاسبية
            </th>
        </tr>
        <tr>
            <th>القيد</th>
            <th>التاريخ</th>
            <th>الوصف</th>
            <th>المرجع</th>
            <th>مدين ({{ $currency }})</th>
            <th>دائن ({{ $currency }})</th>
            <th>الحالة</th>
        </tr>
    </thead>
    <tbody>
        @foreach($entries as $entry)
            @php
                $statusText = match ($entry->status) {
                    'draft' => 'مسودة',
                    'posted' => 'مرحلة',
                    default => 'مستعادة',
                };
            @endphp
            <tr>
                <td>{{ $entry->entry_number }}</td>
                <td>{{ \Carbon\Carbon::parse($entry->entry_date)->format('Y-m-d') }}</td>
                <td>{{ $entry->description ?: '-' }}</td>
                <td>{{ $entry->reference ?: '-' }}</td>
                <td>{{ $entry->total_debit }}</td>
                <td>{{ $entry->total_credit }}</td>
                <td>{{ $statusText }}</td>
            </tr>
            @foreach($entry->lines as $line)
                <tr>
                    <td></td>
                    <td></td>
                    <td>- {{ $line->account?->name_ar ?? $line->account?->name }} ({{ $line->account?->code }})</td>
                    <td>{{ $line->description ?: '-' }}</td>
                    <td>{{ $line->debit > 0 ? $line->debit : '' }}</td>
                    <td>{{ $line->credit > 0 ? $line->credit : '' }}</td>
                    <td></td>
                </tr>
            @endforeach
            <tr>
                <td colspan="7"></td> <!-- Empty row for spacing between entries -->
            </tr>
        @endforeach
    </tbody>
</table>
