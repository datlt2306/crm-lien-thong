@php
    $success = session('success');
@endphp
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng ký xét tuyển - Liên thông Đại học</title>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: Inter, system-ui, -apple-system, Segoe UI, Roboto, sans-serif;
            background: linear-gradient(to bottom, #f8fafc, #eef2ff);
            color: #0f172a;
            min-height: 100vh;
            padding: 24px 14px;
        }
        .wrap { max-width: 760px; margin: 0 auto; }
        .card {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 16px 30px rgba(15, 23, 42, .08);
        }
        .hero {
            padding: 22px 24px;
            background: #0f172a;
            color: #fff;
        }
        .hero h1 { margin: 0 0 6px; font-size: 28px; }
        .hero p { margin: 0; font-size: 14px; color: #cbd5e1; }
        .content { padding: 20px; }
        .alert {
            border-radius: 12px;
            padding: 12px 14px;
            margin-bottom: 14px;
            font-size: 14px;
        }
        .alert-success { background: #ecfdf5; border: 1px solid #86efac; color: #166534; }
        .alert-error { background: #fef2f2; border: 1px solid #fecaca; color: #b91c1c; }
        .alert-error ul { margin: 0; padding-left: 18px; }
        .grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
        }
        .field { margin-bottom: 2px; }
        .field.full { grid-column: 1 / -1; }
        .label {
            display: block;
            margin-bottom: 6px;
            font-size: 14px;
            font-weight: 700;
            color: #334155;
        }
        .req { color: #dc2626; }
        .field-input, .field-select, .field-textarea {
            width: 100%;
            border: 1px solid #cbd5e1;
            border-radius: 12px;
            padding: 11px 12px;
            font-size: 15px;
            background: #fff;
            outline: none;
        }
        .field-textarea { min-height: 104px; resize: vertical; }
        .field-input:focus, .field-select:focus, .field-textarea:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, .16);
        }
        .helper { margin-top: 5px; font-size: 12px; color: #64748b; }
        .submit {
            width: 100%;
            border: none;
            border-radius: 12px;
            background: linear-gradient(to right, #2563eb, #4f46e5);
            color: #fff;
            font-size: 15px;
            font-weight: 700;
            padding: 12px;
            cursor: pointer;
            margin-top: 8px;
        }
        .submit:hover { filter: brightness(1.05); }
        .submit:disabled {
            background: #94a3b8;
            cursor: not-allowed;
            filter: none;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        /* Spinner styles */
        .spinner {
            display: none;
            width: 18px;
            height: 18px;
            border: 3px solid rgba(255, 255, 255, .3);
            border-radius: 50%;
            border-top-color: #fff;
            animation: spin 0.8s linear infinite;
            margin-right: 10px;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        .submit.loading .spinner { display: inline-block; }
        .submit.loading { pointer-events: none; }
        .footer {
            text-align: center;
            color: #64748b;
            font-size: 12px;
            margin-top: 14px;
        }
        .hidden { display: none; }
        .text-red-500 { color: #dc2626; }
        .text-sm { font-size: 12px; }
        .mt-1 { margin-top: 4px; }
        .border-red-500 { border-color: #dc2626 !important; box-shadow: 0 0 0 3px rgba(220, 38, 38, .14) !important; }

        /* Fee Info Styles */
        .fee-info-box {
            margin-top: 20px;
            padding: 16px;
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            font-size: 14px;
            color: #1e293b;
            animation: fadeIn 0.3s ease;
        }
        .field-select {
            width: 100%;
            padding: 12px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 16px;
            outline: none;
            transition: border-color 0.2s;
            background-color: white;
            color: #0f172a;
        }
        optgroup {
            font-weight: bold;
            color: #1e293b;
            font-style: normal;
            background-color: #f1f5f9;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .fee-title {
            font-weight: 700;
            color: #2563eb;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .fee-section {
            margin-bottom: 16px;
        }
        .fee-section-title {
            font-weight: 600;
            color: #334155;
            margin-bottom: 6px;
        }
        .fee-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .fee-item {
            display: flex;
            justify-content: space-between;
            padding: 4px 0;
            border-bottom: 1px dashed #e2e8f0;
        }
        .fee-item:last-child {
            border-bottom: none;
            font-weight: 700;
            color: #0f172a;
            font-size: 15px;
            padding-top: 8px;
        }
        .payment-info {
            background-color: #eff6ff;
            padding: 12px;
            border-radius: 8px;
            border-left: 4px solid #3b82f6;
            margin-top: 12px;
        }
        .payment-row {
            margin-bottom: 4px;
        }
        .payment-label {
            color: #64748b;
            font-size: 12px;
            display: inline-block;
            width: 100px;
        }
        .payment-value {
            font-weight: 600;
            color: #1e293b;
        }

        @media (max-width: 720px) {
            .grid { grid-template-columns: 1fr; }
            .hero h1 { font-size: 24px; }
            .content { padding: 16px; }
            .info-tab { padding: 10px 12px; font-size: 12px; }
        }

        /* Tab Styles */
        .info-tabs {
            display: flex;
            border-bottom: 2px solid #e2e8f0;
            margin-bottom: 16px;
            gap: 4px;
        }
        .info-tab {
            padding: 10px 16px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 600;
            color: #64748b;
            border-bottom: 2px solid transparent;
            margin-bottom: -2px;
            transition: all 0.2s;
        }
        .info-tab:hover {
            color: #2563eb;
        }
        .info-tab.active {
            color: #2563eb;
            border-bottom-color: #2563eb;
        }
        .tab-content {
            display: none;
            animation: fadeIn 0.3s ease;
        }
        .tab-content.active {
            display: block;
        }

        /* QR Code Styles */
        .payment-container {
            display: flex;
            gap: 30px;
            align-items: center; /* Căn giữa theo chiều dọc */
            margin-top: 15px;
            background: #fff;
            padding: 15px;
            border-radius: 12px;
            border: 1px solid #eef2f6;
        }
        .payment-details {
            flex: 1;
        }
        .payment-row {
            margin-bottom: 10px;
            display: flex;
            align-items: center;
        }
        .payment-row:last-child {
            margin-bottom: 0;
        }
        .payment-label {
            color: #64748b;
            font-size: 13px;
            display: inline-block;
            width: 100px;
            flex-shrink: 0;
        }
        .payment-value {
            font-weight: 700;
            color: #1e293b;
            font-size: 15px;
        }
        .qr-code-wrapper {
            width: 150px;
            background: white;
            padding: 8px;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 10px 15px -3px rgb(0 0 0 / 0.05);
            text-align: center;
            flex-shrink: 0;
        }
        .qr-code-wrapper img {
            width: 100%;
            height: auto;
            border-radius: 4px;
            display: block;
            margin-bottom: 6px;
        }
        .qr-code-label {
            font-size: 10px;
            color: #94a3b8;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        @media (max-width: 600px) {
            .payment-container {
                flex-direction: column;
                gap: 20px;
                text-align: center;
            }
            .payment-row {
                justify-content: center;
            }
            .payment-label {
                width: 90px;
                text-align: right;
                margin-right: 10px;
            }
            .payment-value {
                text-align: left;
            }
        }
    </style>
</head>

<body>
    <div class="wrap">
        <div class="card">
            <div class="hero">
                <h1>Đăng ký xét tuyển liên thông GTVT</h1>
                <p>Điền thông tin để gửi hồ sơ đăng ký nhanh cho bộ phận tuyển sinh.</p>
            </div>
            <div class="content">
        @if($success)
        <div class="alert alert-success">{{ $success }}</div>
        @endif
        @if(session('registered_student'))
            @php
                $st = session('registered_student');
            @endphp
            <div style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 24px; text-align: center; margin-bottom: 20px;">
                <div style="width: 64px; height: 64px; background-color: #10b981; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px; font-size: 32px;">
                    ✓
                </div>
                <h2 style="margin-top: 0; color: #000; font-size: 22px;">Đăng ký thành công!</h2>
                <p style="color: #475569; margin-bottom: 12px; line-height: 1.6; font-size: 15px;">
                    Kính gửi <strong>{{ $st['full_name'] }}</strong>,<br>
                    Chúng tôi đã tiếp nhận thông tin hồ sơ của bạn cho chương trình <br>
                    <span style="color: #2563eb; font-weight: 700;">{{ $st['major'] }} - Hệ {{ $st['program_type'] }}</span><br>
                    liên thông dự kiến tại <strong>{{ $st['intake_name'] }}</strong>.
                </p>
                <p style="color: #475569; margin-bottom: 16px;">Dưới đây là <strong>MÃ HỒ SƠ</strong> của bạn dùng để tra cứu trạng thái:</p>
                
                <div style="font-size: 26px; font-weight: 800; letter-spacing: 2px; color: #2563eb; background-color: #fff; padding: 16px 32px; border-radius: 8px; margin: 0 auto 20px; display: inline-block; border: 2px dashed #2563eb;">
                    {{ $st['profile_code'] }}
                </div>
                
                <p style="color: #64748b; font-size: 14px; margin-bottom: 24px;">Bạn có thể dùng mã này để tra cứu tại trang Tra cứu hồ sơ.</p>
                
                <a href="{{ route('public.profile.track.form') }}?profile_code={{ $st['profile_code'] }}" class="submit" style="display: inline-block; width: auto; padding: 12px 32px; text-decoration: none;">Tra cứu hồ sơ ngay</a>
                <div style="margin-top: 15px;">
                    <a href="{{ request()->fullUrl() }}" style="color: #64748b; font-size: 13px; text-decoration: underline;">Đăng ký hồ sơ khác</a>
                </div>
            </div>
        @else
            @if($errors->any())
            <div class="alert alert-error">
                <ul>
                    @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
            @endif
            <form id="student-form" method="POST" action="{{ route('public.ref.submit', ['ref_id' => $ref_id]) }}" enctype="multipart/form-data">
                @csrf
                <div class="grid">
                    <div class="field">
                        <label class="label">Họ và tên <span class="req">*</span></label>
                        <input type="text" name="full_name" value="{{ old('full_name') }}" class="field-input @error('full_name') border-red-500 @enderror" placeholder="VD: Trần Trung Kiên" required>
                        @error('full_name') <div class="text-red-500 text-sm mt-1">{{ $message }}</div> @enderror
                    </div>

                    <div class="field">
                        <label class="label">Số điện thoại <span class="req">*</span></label>
                        <input type="tel" name="phone" value="{{ old('phone') }}" class="field-input @error('phone') border-red-500 @enderror" placeholder="VD: 0934699191" required>
                        @error('phone') <div class="text-red-500 text-sm mt-1">{{ $message }}</div> @enderror
                    </div>

                    <div class="field">
                        <label class="label">Email</label>
                        <input type="email" name="email" value="{{ old('email') }}" class="field-input @error('email') border-red-500 @enderror" placeholder="VD: kien.tran@gmail.com">
                        @error('email') <div class="text-red-500 text-sm mt-1">{{ $message }}</div> @enderror
                    </div>

                    <div class="field">
                        <label class="label">Ngày tháng năm sinh <span class="req">*</span></label>
                        <input type="date" name="dob" value="{{ old('dob') }}" class="field-input @error('dob') border-red-500 @enderror" required>
                        @error('dob') <div class="text-red-500 text-sm mt-1">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="field">
                    <label class="label">Địa chỉ <span class="req">*</span></label>
                    <input type="text" name="address" value="{{ old('address') }}" class="field-input @error('address') border-red-500 @enderror" placeholder="VD: Số 123, Đường ABC, Quận XYZ, TP. Thái Bình" required>
                    @error('address') <div class="text-red-500 text-sm mt-1">{{ $message }}</div> @enderror
                </div>

                <div class="field" style="margin-top: 10px;">
                    <label class="label">Chương trình đào tạo (Ngành & Hệ) <span class="req">*</span></label>
                    <select id="program_selector" class="field-select @error('quota_id') border-red-500 @enderror" required>
                        <option value="">-- Chọn ngành học & hệ đào tạo --</option>
                        @php
                            $typeLabels = [
                                'REGULAR' => '🎓 HỆ CHÍNH QUY', 
                                'PART_TIME' => '💼 HỆ VỪA HỌC VỪA LÀM', 
                                'DISTANCE' => '🌐 HỆ ĐÀO TẠO TỪ XA'
                            ];
                            $groupedPrograms = collect($programs ?? [])->groupBy('program_name');
                        @endphp
                        @foreach($groupedPrograms as $type => $group)
                            <optgroup label="{{ $typeLabels[$type] ?? $type }}">
                                @foreach($group as $program)
                                    <option value="{{ $program['major_name'] }}|{{ $program['program_name'] }}">
                                        {{ $program['major_name'] }}
                                    </option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                </div>

                <div class="field">
                    <label class="label">Đợt tuyển sinh dự kiến <span class="req">*</span></label>
                    <select name="quota_id" id="quota_id" class="field-select @error('quota_id') border-red-500 @enderror" required disabled>
                        <option value="">-- Vui lòng chọn chương trình học trước --</option>
                    </select>
                    <input type="hidden" name="intake_id" id="intake_id" value="{{ old('intake_id') }}">
                    @error('quota_id') <div class="text-red-500 text-sm mt-1">{{ $message }}</div> @enderror
                </div>

                <div id="fee-info-container" class="hidden"></div>

                <div class="field">
                    <label class="label">Ghi chú</label>
                    <textarea name="notes" class="field-textarea">{{ old('notes') }}</textarea>
                </div>

                <div class="field" style="margin-top: 10px; margin-bottom: 20px; display: flex; justify-content: center;">
                    <!-- Hardcoded site key for immediate load -->
                    <div class="g-recaptcha" data-sitekey="6Ldyz74sAAAAAMBNZc87V7Xcf5CD6zIrjTX37kjn" data-callback="recaptchaCallback"></div>
                    @error('g-recaptcha-response') <div class="text-red-500 text-sm mt-1">{{ $message }}</div> @enderror
                </div>

                <button type="submit" class="submit" id="submit-btn" disabled>
                    <span class="spinner"></span>
                    <span class="btn-text">Gửi đăng ký</span>
                </button>
            </form>
        @endif
        <p class="footer">&copy; {{ date('Y') }} Liên thông Đại học</p>
            </div>
        </div>
    </div>

    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    <script>
        const programSelector = document.getElementById('program_selector');
        const quotaSelect = document.getElementById('quota_id');
        const intakeHidden = document.getElementById('intake_id');
        const feeContainer = document.getElementById('fee-info-container');
        const form = document.getElementById('student-form');
        const submitBtn = document.getElementById('submit-btn');

        function showError(fieldId, message) {
            const field = document.getElementById(fieldId);
            if (field) {
                let errorDiv = field.parentNode.querySelector('.error-message');
                if (!errorDiv) {
                    errorDiv = document.createElement('div');
                    errorDiv.className = 'error-message text-red-500 text-sm mt-1';
                    field.parentNode.appendChild(errorDiv);
                }
                errorDiv.textContent = message;
            }
        }

        function hideError(fieldId) {
            const field = document.getElementById(fieldId);
            if (field) {
                const errorDiv = field.parentNode.querySelector('.error-message');
                if (errorDiv) errorDiv.remove();
            }
        }

        window.recaptchaCallback = function() {
            submitBtn.disabled = false;
        };

        const intakesData = @json($intakes ?? []);
        const oldQuotaId = @json(old('quota_id'));

        function getProgramLabel(programCode) {
            const code = String(programCode || '').trim().toUpperCase();
            if (code === 'REGULAR' || code === 'CHÍNH QUY' || code === 'HỆ CHÍNH QUY') return 'REGULAR';
            if (code === 'PART_TIME' || code === 'VỪA HỌC VỪA LÀM' || code === 'HỆ VỪA HỌC VỪA LÀM' || code === 'VHVL') return 'PART_TIME';
            if (code === 'DISTANCE' || code === 'ĐÀO TẠO TỪ XA' || code === 'DTTX') return 'DISTANCE';
            return programCode || 'Chưa xác định';
        }

        function switchTab(tabName) {
            document.querySelectorAll('.info-tab').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
            
            const activeTab = document.querySelector(`.info-tab[data-tab="${tabName}"]`);
            if (activeTab) activeTab.classList.add('active');
            
            const activeContent = document.getElementById(`tab-${tabName}`);
            if (activeContent) activeContent.classList.add('active');
        }

        function updateFeeInfo(quotaId) {
            if (!quotaId) {
                feeContainer.classList.add('hidden');
                return;
            }

            let selectedQuota = null;
            let selectedIntakeId = null;
            for (const intake of intakesData) {
                const q = (intake.quotas || []).find(it => it.id == quotaId);
                if (q) {
                    selectedQuota = q;
                    selectedIntakeId = intake.id;
                    break;
                }
            }

            if (!selectedQuota) {
                feeContainer.classList.add('hidden');
                return;
            }

            intakeHidden.value = selectedIntakeId;

            const programCode = getProgramLabel(selectedQuota.program_name);
            let feeHtml = `
                <div class="fee-info-box">
                    <div class="info-tabs">
                        <div class="info-tab active" data-tab="fees" onclick="switchTab('fees')">💰 Lệ phí & Thanh toán</div>
                        <div class="info-tab" data-tab="docs" onclick="switchTab('docs')">📄 Hồ sơ cần chuẩn bị</div>
                    </div>

                    <div id="tab-fees" class="tab-content active">
            `;

            if (programCode === 'REGULAR') {
                feeHtml += `
                    <div class="fee-section">
                        <div class="fee-section-title">✅ Chi phí tuyển sinh (Hệ Chính quy):</div>
                        <ul class="fee-list">
                            <li class="fee-item"><span>Lệ phí hồ sơ đăng ký thi tuyển</span> <span>60.000 VNĐ</span></li>
                            <li class="fee-item"><span>Lệ phí thi tuyển</span> <span>640.000 VNĐ</span></li>
                            <li class="fee-item"><span>Học phí ôn tập 3 môn</span> <span>1.500.000 VNĐ</span></li>
                            <li class="fee-item"><span>Tổng chi phí đăng ký & ôn tập</span> <span>1.750.000 VNĐ</span></li>
                        </ul>
                    </div>
                `;
            } else if (programCode === 'PART_TIME') {
                feeHtml += `
                    <div class="fee-section">
                        <div class="fee-section-title">✅ Chi phí tuyển sinh (Hệ VHVL):</div>
                        <ul class="fee-list">
                            <li class="fee-item"><span>Lệ phí hồ sơ đăng ký xét tuyển</span> <span>60.000 VNĐ</span></li>
                            <li class="fee-item"><span>Lệ phí xét tuyển, thi tuyển</span> <span>640.000 VNĐ</span></li>
                            <li class="fee-item"><span>Hồ sơ</span> <span>50.000 VNĐ</span></li>
                            <li class="fee-item"><span>Tổng chi phí đăng ký & ôn tập</span> <span>750.000 VNĐ</span></li>
                        </ul>
                    </div>
                `;
            } else if (programCode === 'DISTANCE') {
                feeHtml += `
                    <div class="fee-section">
                        <div class="fee-section-title">✅ Chi phí tuyển sinh (Hệ DTTX):</div>
                        <ul class="fee-list">
                            <li class="fee-item"><span>Lệ phí xét tuyển</span> <span>200.000 VNĐ</span></li>
                            <li class="fee-item"><span>Tổng chi phí hồ sơ</span> <span>200.000 VNĐ</span></li>
                        </ul>
                    </div>
                `;
            }

            feeHtml += `
                        <div class="payment-info">
                            <div class="fee-section-title">🏦 Thông tin thanh toán:</div>
                            <div class="payment-container">
                                <div class="payment-details">
                                    <div class="payment-row"><span class="payment-label">Ngân hàng:</span> <span class="payment-value">BIDV</span></div>
                                    <div class="payment-row"><span class="payment-label">Số tài khoản:</span> <span class="payment-value">8849994466</span></div>
                                    <div class="payment-row"><span class="payment-label">Người nhận:</span> <span class="payment-value">Cô Ly (Phụ trách tuyển sinh)</span></div>
                                    <div class="payment-row"><span class="payment-label">Nội dung CK:</span> <span class="payment-value">Họ tên + Ngày sinh + Nơi sinh</span></div>
                                </div>
                                <div class="qr-code-wrapper">
                                    <img src="${window.location.origin}/assets/qr-ly.png" alt="QR Thanh toán" onerror="this.parentElement.style.display='none'">
                                    <div class="qr-code-label">Quét mã để trả phí</div>
                                </div>
                            </div>
                        </div>
                        <p style="font-size: 13px; color: #1e293b; margin-top: 12px; line-height: 1.5; background: #fffbeb; padding: 10px; border-radius: 8px; border: 1px solid #fde68a;">
                            ⚠️ <strong>Lưu ý:</strong> Sau khi sinh viên đăng ký, vui lòng liên hệ với <strong>Cộng tác viên giới thiệu</strong> để gửi minh chứng thanh toán. Cộng tác viên sẽ giúp bạn hoàn tất thủ tục này trên hệ thống.
                        </p>
                    </div>

                    <div id="tab-docs" class="tab-content">
            `;

            if (programCode === 'DISTANCE') {
                feeHtml += `
                    <div class="fee-section">
                        <div class="fee-section-title">✅ Hồ sơ đăng ký gồm:</div>
                        <ul class="fee-list" style="list-style: disc; padding-left: 20px;">
                            <li style="margin-bottom: 8px;">
                                📄 <strong>Phiếu đăng ký hệ Từ xa</strong> 
                                <a href="https://docs.google.com/document/d/1qH_S7h3ehCjifGNjUAT8OOUPn6d82ZmX0zILlopwghE/edit?tab=t.0" target="_blank" style="color: #2563eb; text-decoration: underline;">Tải phiếu tại đây</a>
                                <br><small>(Hệ từ xa không cần xin dấu xã phường vào phiếu)</small>
                            </li>
                            <li style="margin-bottom: 4px;">📄 01 bản sao công chứng CCCD</li>
                            <li style="margin-bottom: 4px;">📄 01 bản sao công chứng Bằng TN CĐ, Bảng điểm</li>
                            <li style="margin-bottom: 4px;">📷 02 ảnh 4x6 (ghi rõ họ tên, ngày sinh, nơi sinh mặt sau)</li>
                        </ul>
                    </div>
                `;
            } else {
                feeHtml += `
                    <div class="fee-section">
                        <div class="fee-section-title">✅ Hồ sơ đăng ký gồm:</div>
                        <ul class="fee-list" style="list-style: disc; padding-left: 20px;">
                            <li style="margin-bottom: 8px;">
                                📄 <strong>Phiếu tuyển sinh hệ CQ hoặc VHVL</strong> 
                                <a href="https://docs.google.com/document/d/1bJi-xG6ogDXqMFDX8TGrFis5cXPm7EUVUpX4pjKEyJw/edit?tab=t.0" target="_blank" style="color: #2563eb; text-decoration: underline;">Tải phiếu tại đây</a>
                                <br><small>(Xã phường hoặc cơ quan đang làm việc đóng dấu)</small>
                            </li>
                            <li style="margin-bottom: 4px;">📄 01 Bản sao công chứng hợp lệ bằng tốt nghiệp Cao đẳng.</li>
                            <li style="margin-bottom: 4px;">📄 01 Bản sao công chứng bằng tốt nghiệp THPT</li>
                            <li style="margin-bottom: 4px;">📄 01 Bản công chứng giấy chứng nhận kết quả học tập (Bảng điểm).</li>
                            <li style="margin-bottom: 4px;">📄 01 Bản sao công chứng hợp lệ giấy khai sinh.</li>
                            <li style="margin-bottom: 4px;">📄 01 Bản sao công chứng căn cước công dân.</li>
                            <li style="margin-bottom: 4px;">📄 Giấy khám đủ sức khỏe (A3, bản gốc)</li>
                            <li style="margin-bottom: 4px;">📷 04 ảnh chân dung 4x6 cm (Chụp trong vòng 6 tháng trở lại).</li>
                        </ul>
                        <p style="font-size: 13px; color: #dc2626; margin-top: 8px; font-weight: 600;">⚠️ Lưu ý: Tất cả giấy tờ cần có công chứng.</p>
                    </div>
                `;
            }

            feeHtml += `
                        <div class="payment-info" style="background-color: #f0fdf4; border-left-color: #22c55e; margin-top: 15px;">
                            <div class="fee-section-title" style="color: #166534;">📍 Địa chỉ nộp hồ sơ:</div>
                            <div style="font-size: 13px; color: #166534; line-height: 1.6;">
                                Số 73 Nguyễn Chí Thanh - Phường Láng - Hà Nội<br>
                                Nhà A - Phòng 101<br>
                                <strong>Liên hệ:</strong> <a href="tel:0966666585" style="color: #166534; font-weight: 700;">Hotline 0966666585 cô Hà</a>
                            </div>
                        </div>
                    </div>
                </div>
            `;

            feeContainer.innerHTML = feeHtml;
            feeContainer.classList.remove('hidden');
        }

        programSelector.addEventListener('change', function(e, isInitialSelection = false) {
            const selectedValue = this.value;
            quotaSelect.innerHTML = '<option value="">-- Chọn đợt tuyển sinh --</option>';
            feeContainer.classList.add('hidden');
            intakeHidden.value = '';
            
            if (!selectedValue) {
                quotaSelect.disabled = true;
                return;
            }

            const [majorName, programName] = selectedValue.split('|');
            const availableOptions = [];
            intakesData.forEach(intake => {
                const q = (intake.quotas || []).find(it => it.major_name === majorName && it.program_name === programName && it.status === 'active');
                if (q) {
                    const availableSlots = Number(q.available_slots ?? 0);
                    if (availableSlots > 0 || isInitialSelection) {
                        availableOptions.push({
                            quota_id: q.id,
                            intake_name: intake.name,
                            available_slots: availableSlots
                        });
                    }
                }
            });

            if (availableOptions.length > 0) {
                availableOptions.forEach(opt => {
                    const option = document.createElement('option');
                    option.value = opt.quota_id;
                    option.textContent = opt.intake_name + ' (Chỉ tiêu còn lại: ' + opt.available_slots + ')';
                    quotaSelect.appendChild(option);
                });
                quotaSelect.disabled = false;
            } else {
                quotaSelect.innerHTML = '<option value="">-- Hiện chưa có đợt tuyển nào cho ngành này --</option>';
                quotaSelect.disabled = true;
            }
        });

        quotaSelect.addEventListener('change', function() {
            hideError('quota_id');
            updateFeeInfo(this.value);
        });

        if (form) {
            form.addEventListener('submit', function(e) {
                let isValid = true;
                if (!programSelector.value) {
                    alert('Vui lòng chọn chương trình đào tạo!');
                    isValid = false;
                }
                if (!quotaSelect.value) {
                    showError('quota_id', 'Vui lòng chọn đợt tuyển sinh');
                    isValid = false;
                }
                
                if (typeof grecaptcha !== 'undefined') {
                    const response = grecaptcha.getResponse();
                    if (response.length === 0) {
                        alert('Vui lòng xác minh Captcha!');
                        isValid = false;
                    }
                }

                if (!isValid) {
                    e.preventDefault();
                } else {
                    const btn = document.getElementById('submit-btn');
                    btn.disabled = true;
                    btn.classList.add('loading');
                    btn.querySelector('.btn-text').textContent = 'Đang xử lý...';
                }
            });
        }

        if (oldQuotaId) {
            let foundMajor = null, foundProgram = null;
            for (const intake of intakesData) {
                const q = (intake.quotas || []).find(it => it.id == oldQuotaId);
                if (q) {
                    foundMajor = q.major_name;
                    foundProgram = q.program_name;
                    break;
                }
            }
            if (foundMajor && foundProgram) {
                programSelector.value = foundMajor + '|' + foundProgram;
                programSelector.dispatchEvent(new Event('change', { bubbles: true }));
                quotaSelect.value = oldQuotaId;
                updateFeeInfo(oldQuotaId);
            }
        }
    </script>
</body>

</html>