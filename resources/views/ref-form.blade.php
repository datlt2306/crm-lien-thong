@php
    $success = session('success');
@endphp
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng ký xét tuyển liên thông GTVT</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Outfit:wght@600;700;800&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        :root {
            --primary: #4f46e5;
            --primary-hover: #4338ca;
            --bg: #f8fafc;
            --card-bg: #ffffff;
            --text-main: #1e293b;
            --text-muted: #64748b;
            --border: #e2e8f0;
            --shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
            --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg);
            background: linear-gradient(to bottom, #f8fafc, #eef2ff);
            margin: 0;
            padding: 24px 14px;
            color: var(--text-main);
            line-height: 1.5;
            min-height: 100vh;
        }

        .wrap {
            max-width: 800px;
            margin: 0 auto;
        }

        .card {
            background: var(--card-bg);
            border-radius: 24px;
            box-shadow: var(--shadow-lg);
            overflow: hidden;
            border: 1px solid var(--border);
        }

        .hero {
            background: #0f172a;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            padding: 40px 30px;
            color: white;
            text-align: center;
        }

        .hero h1 {
            font-family: 'Outfit', sans-serif;
            font-size: 32px;
            margin: 0 0 10px 0;
            font-weight: 800;
            letter-spacing: -0.02em;
        }

        .hero p {
            margin: 0;
            font-size: 15px;
            color: #94a3b8;
        }

        .content {
            padding: 30px;
        }

        .grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        @media (max-width: 600px) {
            .grid { grid-template-columns: 1fr; }
        }

        .field { margin-bottom: 20px; }
        .field.full { grid-column: 1 / -1; }

        .label {
            display: block;
            margin-bottom: 8px;
            font-size: 14px;
            font-weight: 600;
            color: var(--text-main);
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
            color: var(--text-main);
        }
        optgroup {
            font-weight: bold;
            color: var(--text-main);
            font-style: normal;
            background-color: #f1f5f9;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .fee-title {
            font-weight: 700;
            color: var(--primary);
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
            padding: 5px 0;
            font-size: 12px;
        }
        .fee-item:last-child {
            border-bottom: none;
            font-weight: 700;
            color: #0f172a;
            font-size: 15px;
            padding-top: 8px;
        }
        .payment-info {
            background-color: #fff;
            padding: 12px;
            border-radius: 10px;
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

        /* Enhanced Tab Styles */
        .info-tabs {
            display: flex;
            border-bottom: 2px solid #e2e8f0;
            margin-bottom: 16px;
            gap: 4px;
            overflow-x: auto;
            scrollbar-width: none;
            -ms-overflow-style: none;
        }
        .info-tabs::-webkit-scrollbar { display: none; }

        .info-tab {
            padding: 10px 14px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 700;
            color: #64748b;
            border-bottom: 2px solid transparent;
            margin-bottom: -2px;
            transition: all 0.2s;
            white-space: nowrap;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .info-tab:hover { color: #2563eb; }
        .info-tab.active { color: #2563eb; border-bottom-color: #2563eb; }

        /* Info Card Styles */
        .info-card {
            background: #ffffff;
            border: 1px solid #f1f5f9;
            border-radius: 10px;
            padding: 12px;
            margin-bottom: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.02);
        }
        .info-card-title {
            font-size: 13px;
            font-weight: 700;
            color: #334155;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .info-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 700;
            background: #eff6ff;
            color: #2563eb;
            margin-left: auto;
        }
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }
        .info-item-label { font-size: 12px; color: var(--text-muted); margin-bottom: 4px; font-weight: 500; }
        .info-item-value { font-size: 14px; color: var(--text-main); font-weight: 600; line-height: 1.4; }

        /* QR & Payment Styles */
        .payment-container {
            display: flex;
            gap: 20px;
            align-items: center;
        }
        .qr-code-wrapper {
            background: white;
            padding: 5px;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            text-align: center;
            flex-shrink: 0;
        }
        .qr-code-wrapper img {
            width: 100%;
            display: block;
        }
        .qr-code-label {
            font-size: 9px;
            color: #94a3b8;
            font-weight: 700;
            text-transform: uppercase;
        }

        .tab-content {
            display: none;
            animation: fadeIn 0.4s ease;
        }
        .tab-content.active {
            display: block;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(5px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @media (max-width: 720px) {
            .grid { grid-template-columns: 1fr; }
            .hero h1 { font-size: 24px; }
            .content { padding: 16px; }
            .info-tab { padding: 10px 12px; font-size: 12px; }
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
                        <label class="label">Email <span class="req">*</span></label>
                        <input type="email" name="email" value="{{ old('email') }}" class="field-input @error('email') border-red-500 @enderror" placeholder="VD: kien.tran@gmail.com" required>
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
    <script src="https://unpkg.com/lucide@latest"></script>
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
                        <div class="info-tab active" data-tab="fees" onclick="switchTab('fees')">
                            <i data-lucide="credit-card"></i> Chi phí & Học phí
                        </div>
                        <div class="info-tab" data-tab="training" onclick="switchTab('training')">
                            <i data-lucide="graduation-cap"></i> Tuyển sinh & Đào tạo
                        </div>
                        <div class="info-tab" data-tab="docs" onclick="switchTab('docs')">
                            <i data-lucide="file-text"></i> Hồ sơ chuẩn bị
                        </div>
                    </div>

                    <!-- TAB 1: CHI PHÍ -->
                    <div id="tab-fees" class="tab-content active">
                        <div class="info-card">
                            <div class="info-card-title">
                                <i data-lucide="banknote"></i> Lệ phí tuyển sinh 
                                <span class="info-badge">Đóng 1 lần</span>
                            </div>
                            <ul class="fee-list">
            `;

            if (programCode === 'REGULAR') {
                feeHtml += `
                    <li class="fee-item"><span>Lệ phí hồ sơ thi tuyển</span> <span>60.000đ</span></li>
                    <li class="fee-item"><span>Lệ phí thi tuyển</span> <span>640.000đ</span></li>
                    <li class="fee-item"><span>Học phí ôn tập (3 môn)</span> <span>1.500.000đ</span></li>
                    <li class="fee-item" style="border-top: 1px solid var(--border); font-weight: 800; color: var(--primary); padding-top: 12px; margin-top: 8px;"><span>Tổng chi phí đăng ký</span> <span>1.750.000đ</span></li>
                `;
            } else if (programCode === 'PART_TIME') {
                feeHtml += `
                    <li class="fee-item"><span>Lệ phí hồ sơ xét tuyển</span> <span>60.000đ</span></li>
                    <li class="fee-item"><span>Lệ phí xét tuyển/thi tuyển</span> <span>640.000đ</span></li>
                    <li class="fee-item"><span>Lệ phí hồ sơ</span> <span>50.000đ</span></li>
                    <li class="fee-item" style="border-top: 1px solid var(--border); font-weight: 800; color: var(--primary); padding-top: 12px; margin-top: 8px;"><span>Tổng chi phí đăng ký</span> <span>750.000đ</span></li>
                `;
            } else {
                feeHtml += `
                    <li class="fee-item"><span>Lệ phí xét tuyển</span> <span>200.000đ</span></li>
                `;
            }

            feeHtml += `
                            </ul>
                        </div>

                        <div class="info-card">
                            <div class="info-card-title"><i data-lucide="book-open"></i> Học phí đào tạo (2025 - 2026)</div>
                            <div class="info-grid">
                                <div><div class="info-item-label">Tổng học phí (4 kỳ)</div><div class="info-item-value">~ 57 - 60 triệu VNĐ</div></div>
                                <div><div class="info-item-label">Đóng theo kỳ</div><div class="info-item-value">~ 14.5 triệu VNĐ/kỳ</div></div>
                            </div>
                        </div>

                        <div class="payment-info">
                            <div class="fee-section-title">
                                <i data-lucide="landmark"></i> Thông tin thanh toán:
                            </div>
                            <div class="payment-container">
                                <div class="payment-details">
                                    <div class="payment-row" style="margin-bottom: 8px;"><span class="payment-label" style="width: 85px;">Ngân hàng:</span> <span class="payment-value">BIDV</span></div>
                                    <div class="payment-row" style="margin-bottom: 8px;"><span class="payment-label" style="width: 85px;">Số tài khoản:</span> <span class="payment-value">8849994466</span></div>
                                    <div class="payment-row" style="margin-bottom: 8px;"><span class="payment-label" style="width: 85px;">Người nhận:</span> <span class="payment-value">Cô Ly</span></div>
                                    <div class="payment-row" style="margin-bottom: 0;"><span class="payment-label" style="width: 85px;">Nội dung:</span> <span class="payment-value" style="font-size: 13px; color: #4338ca;">Họ tên + Ngày sinh</span></div>
                                </div>
                                <div class="qr-code-wrapper" style="width: 180px; border-color: #dee2ff;">
                                    <img src="${window.location.origin}/assets/qr-ly.png" alt="QR" onerror="this.parentElement.style.display='none'">
                                </div>
                            </div>
                        </div>
                        
                        <div style="font-size: 12px; color: #4338ca; margin-top: 16px; line-height: 1.6; background: #eef2ff; padding: 12px; border-radius: 12px; border: 1px solid #c7d2fe; display: flex; gap: 10px; align-items: flex-start;">
                            <i data-lucide="info" style="width: 18px; height: 18px; flex-shrink: 0;"></i>
                            <div>
                                <strong>Chính sách hoàn phí:</strong> Không hoàn trả lệ phí (trừ khi trường không mở lớp). Có thể bảo lưu hồ sơ trong vòng 2 đợt tuyển sinh liên tiếp.
                            </div>
                        </div>
                    </div>

                    <!-- TAB 2: TUYỂN SINH & ĐÀO TẠO -->
                    <div id="tab-training" class="tab-content">
                        <div class="info-card">
                            <div class="info-card-title"><i data-lucide="clipboard-list"></i> Hình thức & Môn thi</div>
                            <div style="font-size: 14px; color: var(--text-muted); margin-bottom: 12px;">
                                ${programCode === 'REGULAR' ? 'Kỳ thi tuyển sinh gồm 3 môn chuyên ngành:' : 'Xét tuyển hồ sơ học tập, không yêu cầu thi tuyển.'}
                            </div>
                            ${programCode === 'REGULAR' ? `
                            <div class="info-grid">
                                <div style="background: #f8fafc; padding: 10px; border-radius: 8px; border: 1px solid var(--border); text-align: center; font-weight: 700; font-size: 13px; color: var(--primary);">Toán</div>
                                <div style="background: #f8fafc; padding: 10px; border-radius: 8px; border: 1px solid var(--border); text-align: center; font-weight: 700; font-size: 13px; color: var(--primary);">Toán rời rạc</div>
                                <div style="grid-column: span 2; background: #f8fafc; padding: 10px; border-radius: 8px; border: 1px solid var(--border); text-align: center; font-weight: 700; font-size: 13px; color: var(--primary);">Cấu trúc dữ liệu & Giải thuật</div>
                            </div>
                            ` : ''}
                        </div>

                        <div class="info-card">
                            <div class="info-card-title"><i data-lucide="calendar"></i> Thời gian & Lịch học</div>
                            <div class="info-grid" style="margin-bottom: 12px;">
                                <div><div class="info-item-label">Thời gian đào tạo</div><div class="info-item-value">2 năm (4 học kỳ)</div></div>
                                <div><div class="info-item-label">Lịch học dự kiến</div><div class="info-item-value">Tối (18h30 - 20h30)</div></div>
                            </div>
                            <div style="font-size: 13px; color: var(--text-muted); padding: 10px; background: #f8fafc; border-radius: 8px; border-left: 4px solid var(--primary);">
                                📍 <strong>Địa điểm:</strong> ${
                                    programCode === 'REGULAR' ? 'Học trực tiếp tại CS1: Số 3 Cầu Giấy, Hà Nội.' : 
                                    (programCode === 'PART_TIME' ? 'Học kết hợp (Hybrid): Offline và Online.' : 'Học Online 100%, thi tập trung cuối kỳ.')
                                }
                            </div>
                        </div>

                        <div class="info-card">
                            <div class="info-card-title"><i data-lucide="award"></i> Bằng cấp & Chứng chỉ</div>
                            <div class="info-item-value" style="color: #059669; margin-bottom: 12px; display: flex; align-items: center; gap: 8px;">
                                <i data-lucide="check-circle" style="width: 18px; height: 18px;"></i> Nhận bằng Cử nhân Liên thông Chính quy
                            </div>
                            
                            <div class="info-item-label">Chứng chỉ ngoại ngữ miễn học (nếu có):</div>
                            <div style="display: flex; flex-wrap: wrap; gap: 8px; margin-top: 6px;">
                                <span class="info-badge">IELTS ≥ 4.5</span>
                                <span class="info-badge">TOEIC ≥ 450</span>
                                <span class="info-badge">VSTEP ≥ 5</span>
                            </div>
                            <div style="font-size: 11px; color: var(--text-muted); margin-top: 10px; line-height: 1.5;">
                                * Chấp nhận: ĐHQG Hà Nội, ĐH Sư phạm HN, ĐH Hà Nội.
                                <br> * Yêu cầu chứng chỉ Giáo dục Quốc phòng của Bộ GD.
                            </div>
                        </div>
                    </div>

                    <!-- TAB 3: HỒ SƠ -->
                    <div id="tab-docs" class="tab-content">
                        <div class="info-card">
                            <ul class="fee-list" style="gap: 12px; display: flex; flex-direction: column;">
            `;

            if (programCode === 'DISTANCE') {
                feeHtml += `
                    <li class="fee-item" style="flex-direction: column; align-items: flex-start; border: none; gap: 4px;">
                        <div style="font-size: 13px; display: flex; gap: 8px; font-weight: 600; color: var(--text-main);">
                            <i data-lucide="file-check" style="width: 16px; color: var(--primary);"></i> Phiếu đăng ký hệ Từ xa
                        </div>
                        <div style="margin-left: 24px;">
                            <a href="https://docs.google.com/document/d/1qH_S7h3ehCjifGNjUAT8OOUPn6d82ZmX0zILlopwghE/edit?tab=t.0" target="_blank" style="color: var(--primary); text-decoration: none; font-size: 13px; font-weight: 500;">
                                <i data-lucide="download" style="width: 12px; height: 12px; display: inline-block; vertical-align: middle;"></i> Tải phiếu mẫu tại đây
                            </a>
                        </div>
                    </li>
                    <li class="fee-item" style="border: none; padding: 0 0 0 24px;"><span style="color: var(--text-muted); font-size: 13px;">• 01 bản sao công chứng CCCD</span></li>
                    <li class="fee-item" style="border: none; padding: 0 0 0 24px;"><span style="color: var(--text-muted); font-size: 13px;">• 01 bản sao công chứng Bằng TN CĐ, Bảng điểm</span></li>
                    <li class="fee-item" style="border: none; padding: 0 0 0 24px;"><span style="color: var(--text-muted); font-size: 13px;">• 02 ảnh 4x6 (ghi rõ thông tin mặt sau)</span></li>
                `;
            } else {
                feeHtml += `
                    <li class="fee-item" style="flex-direction: column; align-items: flex-start; border: none; gap: 4px;">
                        <div style="font-size: 13px; display: flex; gap: 8px; font-weight: 600; color: var(--text-main);">
                            <i data-lucide="file-check" style="font-size: 13px;width: 16px; color: var(--primary);"></i> Phiếu tuyển sinh CQ hoặc VHVL
                        </div>
                        <div style="margin-left: 24px;">
                            <a href="https://docs.google.com/document/d/1bJi-xG6ogDXqMFDX8TGrFis5cXPm7EUVUpX4pjKEyJw/edit?tab=t.0" target="_blank" style="color: var(--primary); text-decoration: none; font-size: 13px; font-weight: 500;">
                                <i data-lucide="download" style="width: 12px; height: 12px; display: inline-block; vertical-align: middle;"></i> Tải phiếu mẫu tại đây
                            </a>
                            <div style="font-size: 11px; color: var(--text-muted); margin-top: 2px;">(Cần xin dấu xác nhận của Xã/Phường hoặc Cơ quan)</div>
                        </div>
                    </li>
                    <li class="fee-item" style="border: none; padding: 0 0 0 24px;"><span style="color: var(--text-muted); font-size: 13px;">• 01 Bản sao công chứng Bằng TN Cao đẳng</span></li>
                    <li class="fee-item" style="border: none; padding: 0 0 0 24px;"><span style="color: var(--text-muted); font-size: 13px;">• 01 Bản sao công chứng Bằng tốt nghiệp THPT</span></li>
                    <li class="fee-item" style="border: none; padding: 0 0 0 24px;"><span style="color: var(--text-muted); font-size: 13px;">• 01 Bản công chứng Bảng điểm Cao đẳng</span></li>
                    <li class="fee-item" style="border: none; padding: 0 0 0 24px;"><span style="color: var(--text-muted); font-size: 13px;">• 01 Bản sao công chứng Giấy khai sinh & CCCD</span></li>
                    <li class="fee-item" style="border: none; padding: 0 0 0 24px;"><span style="color: var(--text-muted); font-size: 13px;">• Giấy khám sức khỏe (A3, bản gốc)</span></li>
                    <li class="fee-item" style="border: none; padding: 0 0 0 24px;"><span style="color: var(--text-muted); font-size: 13px;">• 04 ảnh chân dung 4x6 cm (mới chụp)</span></li>
                `;
            }

            feeHtml += `
                            </ul>
                            <div style="margin-top: 15px; padding: 10px; background: #fff1f2; border-radius: 8px; color: #be123c; font-size: 12px; font-weight: 600; display: flex; gap: 8px; align-items: center;">
                                <i data-lucide="alert-circle" style="width: 16px; height: 16px;"></i> Tất cả giấy tờ bản sao đều phải được công chứng.
                            </div>
                        </div>

                        <div class="info-card" style="background: #f0fdf4; border-color: #dcfce7;">
                            <div class="info-card-title" style="color: #166534;"><i data-lucide="map-pin"></i> Địa chỉ nộp hồ sơ</div>
                            <div style="font-size: 13px; color: #166534; line-height: 1.6;">
                                <div style="font-weight: 700; font-size: 14px;">Số 73 Nguyễn Chí Thanh, Láng Thượng, Đống Đa, Hà Nội</div>
                                <div style="margin-top: 4px;">Nhà A - Phòng 101 (Văn phòng tuyển sinh)</div>
                                <div style="margin-top: 10px; display: flex; align-items: center; gap: 8px;">
                                    <div style="background: #22c55e; color: white; padding: 4px 12px; border-radius: 20px; font-weight: 700;">
                                        <i data-lucide="phone" style="width: 12px; height: 12px; display: inline-block; vertical-align: middle; margin-right: 4px;"></i>
                                        0966.666.585 (Cô Hà)
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;

            feeContainer.innerHTML = feeHtml;
            feeContainer.classList.remove('hidden');
            
            // Re-initialize Lucide icons for dynamic content
            if (window.lucide) {
                window.lucide.createIcons();
            }
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