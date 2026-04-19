<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tra cứu hồ sơ sinh viên</title>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: Inter, system-ui, -apple-system, Segoe UI, Roboto, sans-serif;
            background: linear-gradient(to bottom, #f8fafc, #f1f5f9);
            color: #0f172a;
            min-height: 100vh;
            padding: 32px 16px;
        }
        .container { max-width: 1040px; margin: 0 auto; }
        .card {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 12px 24px rgba(15, 23, 42, 0.08);
        }
        .hero {
            background: #0f172a;
            color: #fff;
            padding: 28px 32px;
        }
        .hero small { color: #cbd5e1; letter-spacing: .18em; text-transform: uppercase; }
        .hero h1 { margin: 10px 0 8px; font-size: 32px; }
        .hero p { margin: 0; color: #cbd5e1; font-size: 14px; }
        .content { padding: 24px 32px 28px; }
        .search-box {
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            background: #f8fafc;
            padding: 16px;
        }
        .label { display: block; font-weight: 600; margin-bottom: 8px; font-size: 14px; color: #334155; }
        .search-row { display: flex; gap: 8px; flex-wrap: wrap; }
        input[type="text"] {
            flex: 1;
            min-width: 240px;
            padding: 11px 12px;
            border: 1px solid #cbd5e1;
            border-radius: 12px;
            font-size: 14px;
            outline: none;
        }
        input[type="text"]:focus { border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37, 99, 235, .15); }
        .btn {
            border: none;
            background: #0f172a;
            color: #fff;
            border-radius: 12px;
            padding: 11px 20px;
            font-weight: 600;
            cursor: pointer;
        }
        .btn:hover { background: #1e293b; }
        .result-banner {
            margin-top: 18px;
            border-radius: 16px;
            padding: 14px 16px;
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            display: flex;
            justify-content: space-between;
            gap: 10px;
            align-items: center;
            flex-wrap: wrap;
        }
        .badge {
            display: inline-flex;
            align-items: center;
            padding: 6px 12px;
            border-radius: 999px;
            font-size: 13px;
            font-weight: 700;
            border: 1px solid #bfdbfe;
            background: #fff;
            color: #1d4ed8;
        }
        .progress-card, .info-card {
            margin-top: 16px;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            background: #fff;
            padding: 16px;
        }
        .progress-head { display: flex; justify-content: space-between; margin-bottom: 10px; }
        .progress-track { height: 10px; background: #e2e8f0; border-radius: 999px; overflow: hidden; }
        .progress-fill { height: 100%; background: linear-gradient(to right, #3b82f6, #6366f1); }
        .grid { margin-top: 16px; display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        .kv { display: flex; justify-content: space-between; gap: 12px; font-size: 14px; padding: 8px 0; border-bottom: 1px dashed #e2e8f0; }
        .kv:last-child { border-bottom: none; }
        .muted { color: #64748b; }
        .strong { font-weight: 700; text-align: right; }
        .payment-note {
            margin-top: 10px;
            border: 1px solid #fde68a;
            background: #fffbeb;
            border-radius: 12px;
            padding: 10px 12px;
            font-size: 14px;
            color: #92400e;
        }
        .payment-note.ok {
            border-color: #86efac;
            background: #ecfdf5;
            color: #166534;
        }
        .footer {
            margin-top: 14px;
            text-align: right;
            font-size: 12px;
            color: #64748b;
        }
        .error {
            margin-top: 16px;
            border-radius: 16px;
            border: 1px solid #fecaca;
            background: #fef2f2;
            color: #b91c1c;
            padding: 14px 16px;
            font-size: 14px;
        }
        @media (max-width: 900px) {
            .hero h1 { font-size: 26px; }
            .content { padding: 18px; }
            .grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body class="min-h-screen bg-gradient-to-b from-slate-50 to-slate-100 py-8 px-4 sm:px-6">
    @php
        $statusTone = match ($student?->status ?? null) {
            'enrolled' => 'emerald',
            'approved' => 'green',
            'submitted' => 'amber',
            'rejected', 'dropped' => 'rose',
            default => 'blue',
        };
        $progressValue = match ($student?->status ?? null) {
            'new' => 20,
            'contacted' => 35,
            'submitted' => 55,
            'approved' => 80,
            'enrolled' => 100,
            'rejected', 'dropped' => 100,
            default => 10,
        };
    @endphp

    <div class="container">
        <div class="card">
            <div class="hero">
                <div>
                    <small>Student Tracking</small>
                    <h1>Tra cứu hồ sơ sinh viên</h1>
                    <p>Nhập mã hồ sơ để theo dõi xử lý hồ sơ và xác nhận học phí theo thời gian thực.</p>
                </div>
            </div>

            <div class="content">
                <form method="GET" action="{{ route('public.profile.track.form') }}" class="search-box">
                    <label for="profile_code" class="label">Mã hồ sơ</label>
                    <div class="search-row">
                        <input
                            id="profile_code"
                            name="profile_code"
                            value="{{ $profileCode ?? '' }}"
                            placeholder="Ví dụ: HS2026000123"
                            type="text"
                        />
                        <button type="submit" class="btn">Tra cứu</button>
                    </div>
                </form>

                @if(($profileCode ?? '') !== '')
                    @if($student)
                        <div class="result-banner">
                            <div>
                                <div class="muted" style="font-size:14px;">Kết quả tra cứu cho mã</div>
                                <div style="font-size:20px;font-weight:800;color:#1e3a8a;">{{ $student->profile_code }}</div>
                            </div>
                            <div style="text-align: right;">
                                <div class="muted" style="font-size:12px; margin-bottom: 2px;">Trạng thái hồ sơ:</div>
                                <span class="badge">{{ $statusLabel }}</span>
                            </div>
                        </div>

                        <div class="progress-card">
                            <div class="progress-head">
                                <strong>Tiến độ hồ sơ</strong>
                                <span class="muted" style="font-weight:700">{{ $progressValue }}%</span>
                            </div>
                            <div class="progress-track">
                                <div class="progress-fill" style="width: {{ $progressValue }}%"></div>
                            </div>
                        </div>

                        <div class="grid">
                            <div class="info-card">
                                <strong style="color:#475569; text-transform:uppercase; letter-spacing:.08em; font-size:12px;">Thông tin hồ sơ</strong>
                                <div class="kv"><span class="muted">Họ và tên</span><span class="strong">{{ $student->full_name }}</span></div>
                                <div class="kv"><span class="muted">Số điện thoại</span><span class="strong">{{ $student->phone }}</span></div>
                                <div class="kv"><span class="muted">Ngày sinh</span><span class="strong">{{ $student->dob ? \Carbon\Carbon::parse($student->dob)->format('d/m/Y') : 'Chưa cập nhật' }}</span></div>
                                <div class="kv"><span class="muted">Địa chỉ</span><span class="strong" style="max-width: 60%; text-align: right;">{{ $student->address ?? 'Chưa cập nhật' }}</span></div>
                                <div class="kv"><span class="muted">Đợt tuyển sinh</span><span class="strong">{{ $student->intake?->name ?? 'Chưa cập nhật' }}</span></div>
                                <div class="kv"><span class="muted">Ngành - Hệ</span><span class="strong">{{ $student->quota?->major_name ?? $student->major ?? 'Chưa cập nhật' }} - {{ $programTypeLabel ?? 'Chưa cập nhật' }}</span></div>
                                <div class="kv"><span class="muted">Tình trạng xử lý</span><span class="strong">{{ $applicationStatusLabel }}</span></div>
                                <div class="kv"><span class="muted">Người giới thiệu</span><span class="strong">{{ $student->collaborator?->full_name ?? 'Không có' }}</span></div>
                                @if($student->collaborator?->phone)
                                    <div class="kv"><span class="muted">SĐT người giới thiệu</span><span class="strong">{{ $student->collaborator->phone }}</span></div>
                                @endif
                            </div>

                            <div class="info-card">
                                <strong style="color:#475569; text-transform:uppercase; letter-spacing:.08em; font-size:12px;">Thanh toán</strong>
                                <div class="payment-note {{ $isPaymentVerified ? 'ok' : '' }}">
                                    <strong>{{ $isPaymentVerified ? 'Đã xác nhận thanh toán' : 'Chưa xác nhận thanh toán' }}</strong><br>
                                    {{ $isPaymentVerified ? 'Hệ thống đã ghi nhận phí đăng ký tuyển sinh.' : 'Đang chờ kế toán/chủ đơn vị xác nhận.' }}
                                </div>
                                <div class="kv"><span class="muted">Số tiền</span><span class="strong">{{ $paymentAmountLabel }}</span></div>
                                <div class="kv"><span class="muted">Ngày gửi bill</span><span class="strong">{{ $student->payment?->receipt_uploaded_at?->format('d/m/Y H:i') ?? 'Chưa cập nhật' }}</span></div>
                                <div class="kv"><span class="muted">Ngày xác nhận tiền</span><span class="strong">{{ $student->payment?->verified_at?->format('d/m/Y H:i') ?? 'Chưa xác nhận' }}</span></div>

                                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-top: 24px;">
                                    @if($billUrl)
                                        <div>
                                            <div class="muted" style="font-size: 12px; margin-bottom: 8px;">Minh chứng thanh toán:</div>
                                            <a href="{{ $billUrl }}" target="_blank" style="display: flex; align-items: center; justify-content: center; gap: 8px; background: #f1f5f9; border: 1.5px solid #e2e8f0; border-radius: 12px; padding: 12px; text-decoration: none; transition: all 0.2s; color: #1e293b; font-weight: 600;" onmouseover="this.style.background='#e2e8f0'; this.style.borderColor='#cbd5e1'" onmouseout="this.style.background='#f1f5f9'; this.style.borderColor='#e2e8f0'">
                                                <svg xmlns="http://www.w3.org/2000/svg" style="width:18px; height:18px; color:#2563eb;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                </svg>
                                                Xem minh chứng
                                            </a>
                                        </div>
                                    @endif

                                    @if($receiptUrl)
                                        <div>
                                            <div class="muted" style="font-size: 12px; margin-bottom: 8px;">Phiếu thu chính thức:</div>
                                            <a href="{{ $receiptUrl }}" target="_blank" style="display: flex; align-items: center; justify-content: center; gap: 8px; background: #ecfdf5; border: 1.5px solid #d1fae5; border-radius: 12px; padding: 12px; text-decoration: none; transition: all 0.2s; color: #065f46; font-weight: 600;" onmouseover="this.style.background='#d1fae5'; this.style.borderColor='#a7f3d0'" onmouseout="this.style.background='#ecfdf5'; this.style.borderColor='#d1fae5'">
                                                <svg xmlns="http://www.w3.org/2000/svg" style="width:18px; height:18px; color:#059669;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                                Xem phiếu thu
                                            </a>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="footer">
                            Cập nhật lần cuối: <strong>{{ $student->updated_at?->format('d/m/Y H:i') }}</strong>
                        </div>
                    @else
                        <div class="error">
                            Không tìm thấy hồ sơ với mã <strong>{{ $profileCode }}</strong>. Vui lòng kiểm tra lại mã hồ sơ.
                        </div>
                    @endif
                @endif
            </div>
        </div>
    </div>
</body>
</html>
