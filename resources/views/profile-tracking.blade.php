<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tra cứu hồ sơ sinh viên</title>
    <link rel="stylesheet" href="{{ asset('css/profile-tracking.css') }}">
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
                                <div class="result-header">Kết quả tra cứu cho mã</div>
                                <div class="result-code">{{ $student->profile_code }}</div>
                            </div>
                            <div class="right-align">
                                <div class="small-label">Trạng thái hồ sơ:</div>
                                <span class="badge">{{ $statusLabel }}</span>
                            </div>
                        </div>

                        <div class="progress-card">
                            <div class="progress-head">
                                <strong>Tiến độ hồ sơ</strong>
                                <span class="progress-pct">{{ $progressValue }}%</span>
                            </div>
                            <div class="progress-track">
                                <div class="progress-fill w-{{ $progressValue }}"></div>
                            </div>
                        </div>

                        <div class="grid">
                            <div class="info-card">
                                <strong class="info-label">Thông tin hồ sơ</strong>
                                <div class="kv"><span class="muted">Họ và tên</span><span class="strong">{{ $student->full_name }}</span></div>
                                <div class="kv"><span class="muted">Số điện thoại</span><span class="strong">{{ $student->phone }}</span></div>
                                <div class="kv"><span class="muted">Ngày sinh</span><span class="strong">{{ $student->dob ? \Carbon\Carbon::parse($student->dob)->format('d/m/Y') : 'Chưa cập nhật' }}</span></div>
                                <div class="kv"><span class="muted">Địa chỉ</span><span class="strong info-value">{{ $student->address ?? 'Chưa cập nhật' }}</span></div>
                                <div class="kv"><span class="muted">Đợt tuyển sinh</span><span class="strong">{{ $student->intake?->name ?? 'Chưa cập nhật' }}</span></div>
                                <div class="kv"><span class="muted">Ngành - Hệ</span><span class="strong">{{ $student->quota?->major_name ?? $student->major ?? 'Chưa cập nhật' }} - {{ $programTypeLabel ?? 'Chưa cập nhật' }}</span></div>
                                <div class="kv"><span class="muted">Tình trạng xử lý</span><span class="strong">{{ $applicationStatusLabel }}</span></div>
                                <div class="kv"><span class="muted">Người giới thiệu</span><span class="strong">{{ $student->collaborator?->full_name ?? 'Không có' }}</span></div>
                                @if($student->collaborator?->phone)
                                    <div class="kv"><span class="muted">SĐT người giới thiệu</span><span class="strong">{{ $student->collaborator->phone }}</span></div>
                                @endif
                            </div>

                            <div class="info-card">
                                <strong class="info-label">Thanh toán</strong>
                                <div class="payment-note {{ $isPaymentVerified ? 'ok' : '' }}">
                                    <strong>{{ $isPaymentVerified ? 'Đã xác nhận thanh toán' : 'Chưa xác nhận thanh toán' }}</strong><br>
                                    {{ $isPaymentVerified ? 'Hệ thống đã ghi nhận phí đăng ký tuyển sinh.' : 'Đang chờ kế toán/chủ đơn vị xác nhận.' }}
                                </div>
                                <div class="kv"><span class="muted">Số tiền</span><span class="strong">{{ $paymentAmountLabel }}</span></div>
                                <div class="kv"><span class="muted">Ngày gửi bill</span><span class="strong">{{ $student->payment?->receipt_uploaded_at?->format('d/m/Y H:i') ?? 'Chưa cập nhật' }}</span></div>
                                <div class="kv"><span class="muted">Ngày xác nhận tiền</span><span class="strong">{{ $student->payment?->verified_at?->format('d/m/Y H:i') ?? 'Chưa xác nhận' }}</span></div>

                                <div class="grid-actions">
                                    @if($billUrl)
                                        <div>
                                            <div class="action-btn-label">Minh chứng thanh toán:</div>
                                            <a href="{{ $billUrl }}" target="_blank" class="action-btn">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="icon-svg primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                </svg>
                                                Xem minh chứng
                                            </a>
                                        </div>
                                    @endif

                                    @if($receiptUrl)
                                        <div>
                                            <div class="action-btn-label">Phiếu thu chính thức:</div>
                                            <a href="{{ $receiptUrl }}" target="_blank" class="action-btn success">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="icon-svg success" fill="none" viewBox="0 0 24 24" stroke="currentColor">
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
