<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.4;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
        }
        .header h1 {
            margin: 0;
            color: #333;
        }
        .header p {
            margin: 5px 0;
            color: #666;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f5f5f5;
            font-weight: bold;
        }
        .summary {
            margin-top: 30px;
            padding: 15px;
            background-color: #f9f9f9;
            border: 1px solid #ddd;
        }
        .summary h3 {
            margin-top: 0;
            color: #333;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 10px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $title }}</h1>
        <p>Ngày tạo: {{ now()->format('d/m/Y H:i:s') }}</p>
        <p>Khoảng thời gian: {{ $config['date_range'] ?? 'N/A' }}</p>
    </div>

    @if(!empty($data))
        <table>
            <thead>
                <tr>
                    @foreach(array_keys($data[0]) as $header)
                        <th>{{ ucfirst(str_replace('_', ' ', $header)) }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach($data as $row)
                    <tr>
                        @foreach($row as $value)
                            <td>{{ $value }}</td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="summary">
            <h3>Tóm tắt</h3>
            <p><strong>Tổng số bản ghi:</strong> {{ count($data) }}</p>
            
            @if($config['report_type'] === 'revenue')
                <p><strong>Tổng doanh thu:</strong> {{ number_format(array_sum(array_column($data, 'revenue'))) }} VND</p>
            @elseif($config['report_type'] === 'commission')
                <p><strong>Tổng hoa hồng:</strong> {{ number_format(array_sum(array_column($data, 'commission'))) }} VND</p>
            @elseif($config['report_type'] === 'students')
                <p><strong>Tổng học viên mới:</strong> {{ array_sum(array_column($data, 'new_students')) }}</p>
                <p><strong>Tổng học viên đã thanh toán:</strong> {{ array_sum(array_column($data, 'paid_students')) }}</p>
            @elseif($config['report_type'] === 'financial')
                <p><strong>Tổng doanh thu:</strong> {{ number_format(array_sum(array_column($data, 'revenue'))) }} VND</p>
                <p><strong>Tổng hoa hồng:</strong> {{ number_format(array_sum(array_column($data, 'commission'))) }} VND</p>
                <p><strong>Lợi nhuận ròng:</strong> {{ number_format(array_sum(array_column($data, 'net_profit'))) }} VND</p>
            @endif
        </div>
    @else
        <p>Không có dữ liệu để hiển thị.</p>
    @endif

    <div class="footer">
        <p>Báo cáo được tạo tự động bởi hệ thống CRM Liên Thông</p>
    </div>
</body>
</html>
