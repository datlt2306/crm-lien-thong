@php($success = session('success'))
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
        @media (max-width: 720px) {
            .grid { grid-template-columns: 1fr; }
            .hero h1 { font-size: 24px; }
            .content { padding: 16px; }
        }
    </style>
</head>

<body>
    <div class="wrap">
        <div class="card">
            <div class="hero">
                <h1>Đăng ký xét tuyển liên thông</h1>
                <p>Điền thông tin để gửi hồ sơ đăng ký nhanh cho bộ phận tuyển sinh.</p>
            </div>
            <div class="content">
        @if($success)
        <div class="alert alert-success">{{ $success }}</div>
        @endif
        @if($errors->any())
        <div class="alert alert-error">
            <ul>
                @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif
        <form id="student-form" method="POST" action="">
            @csrf
            <div class="grid">
                <div class="field">
                    <label class="label">Họ và tên <span class="req">*</span></label>
                    <input type="text" name="full_name" value="{{ old('full_name') }}" required class="field-input" />
                </div>
                <div class="field">
                    <label class="label">Số điện thoại <span class="req">*</span></label>
                    <input type="text" name="phone" value="{{ old('phone') }}" required class="field-input" />
                </div>
                <div class="field">
                    <label class="label">Email</label>
                    <input type="email" name="email" value="{{ old('email') }}" class="field-input" />
                </div>
                <div class="field">
                    <label class="label">Ngày tháng năm sinh <span class="req">*</span></label>
                    <input type="date" name="dob" value="{{ old('dob') }}" required class="field-input" />
                </div>
                <div class="field full">
                    <label class="label">Địa chỉ <span class="req">*</span></label>
                    <input type="text" name="address" value="{{ old('address') }}" required class="field-input" />
                </div>
            </div>

            <input type="hidden" name="organization_id" value="{{ $collaborator->organization_id }}" />
            <div class="field" style="margin-top: 10px;">
                <label class="label">Đợt tuyển sinh <span class="req">*</span></label>
                <select name="intake_id" id="intake_id" class="field-select" required>
                    <option value="">-- Chọn đợt tuyển --</option>
                    @foreach(($intakes ?? []) as $intake)
                    <option value="{{ e($intake->id) }}" {{ old('intake_id') == $intake->id ? 'selected' : '' }}>
                        {{ e($intake->name) }} (từ {{ $intake->start_date?->format('d/m/Y') }} đến {{ $intake->end_date?->format('d/m/Y') }})
                    </option>
                    @endforeach
                </select>
                <div id="intake_id_error" class="text-red-500 text-sm mt-1 hidden">Vui lòng chọn đợt tuyển</div>
            </div>

            <div class="field">
                <label class="label">Chương trình đào tạo <span class="req">*</span></label>
                <select name="quota_id" id="quota_id" class="field-select" required disabled>
                    <option value="">-- Vui lòng chọn đợt tuyển trước --</option>
                </select>
                <div id="quota_id_error" class="text-red-500 text-sm mt-1 hidden">Vui lòng chọn chương trình đào tạo</div>
            </div>
            <div class="field">
                <label class="label">Ghi chú</label>
                <textarea name="notes" class="field-textarea">{{ old('notes') }}</textarea>
            </div>
            <button type="submit" class="submit">Gửi đăng ký</button>
        </form>
        <p class="footer">&copy; {{ date('Y') }} Liên thông Đại học</p>
            </div>
        </div>
    </div>

    <script>
        const intakeSelect = document.getElementById('intake_id');
        const quotaSelect = document.getElementById('quota_id');
        const form = document.getElementById('student-form');

        const intakesData = @json($intakes ?? []);
        const oldQuotaId = @json(old('quota_id'));

        function getProgramLabel(programCode) {
            const code = String(programCode || '').trim().toUpperCase();

            if (code === 'REGULAR') {
                return 'Chính quy';
            }

            if (code === 'PART_TIME') {
                return 'Vừa học vừa làm';
            }

            if (code === 'DISTANCE') {
                return 'Đào tạo từ xa';
            }

            return programCode || 'Chưa xác định';
        }

        function showError(fieldId, message) {
            const errorDiv = document.getElementById(fieldId + '_error');
            if (errorDiv) {
                errorDiv.textContent = message;
                errorDiv.classList.remove('hidden');
            }
            const field = document.getElementById(fieldId);
            if (field) {
                field.classList.add('border-red-500');
            }
        }

        function hideError(fieldId) {
            const errorDiv = document.getElementById(fieldId + '_error');
            if (errorDiv) {
                errorDiv.classList.add('hidden');
            }
            const field = document.getElementById(fieldId);
            if (field) {
                field.classList.remove('border-red-500');
            }
        }

        function validateForm() {
            let isValid = true;

            if (!intakeSelect.value) {
                showError('intake_id', 'Vui lòng chọn đợt tuyển');
                isValid = false;
            } else {
                hideError('intake_id');
            }

            if (!quotaSelect.value) {
                showError('quota_id', 'Vui lòng chọn chương trình đào tạo');
                isValid = false;
            } else {
                hideError('quota_id');
            }

            return isValid;
        }

        form.addEventListener('submit', function(e) {
            if (!validateForm()) {
                e.preventDefault();
                const firstError = document.querySelector('.border-red-500');
                if (firstError) {
                    firstError.scrollIntoView({
                        behavior: 'smooth',
                        block: 'center'
                    });
                }
            }
        });

        intakeSelect.addEventListener('change', function() {
            hideError('intake_id');
            
            const selectedIntakeId = this.value;
            quotaSelect.innerHTML = '<option value="">-- Chọn chương trình đào tạo --</option>';
            
            if (!selectedIntakeId) {
                quotaSelect.disabled = true;
                return;
            }

            const selectedIntake = intakesData.find(i => i.id == selectedIntakeId);
            
            if (selectedIntake && selectedIntake.quotas && selectedIntake.quotas.length > 0) {
                quotaSelect.disabled = false;
                selectedIntake.quotas.forEach(function(quota) {
                    const availableSlots = Number(quota.available_slots ?? 0);
                    const isActive = quota.status === 'active';
                    const isOpen = isActive && availableSlots > 0;
                    const majorName = quota.major_name || quota.name || 'Chưa xác định';
                    const programLabel = getProgramLabel(quota.program_name);

                    let statusText = '';
                    if (!isActive) {
                        statusText = ' - Tạm dừng';
                    } else if (availableSlots <= 0) {
                        statusText = ' - Đã đủ chỉ tiêu';
                    }

                    const option = document.createElement('option');
                    option.value = quota.id;
                    option.textContent = majorName + ' - ' + programLabel + ' (Chỉ tiêu còn lại: ' + availableSlots + ')' + statusText;
                    option.disabled = !isOpen;
                    quotaSelect.appendChild(option);
                });
                
                if (oldQuotaId) {
                    quotaSelect.value = oldQuotaId;
                }
            } else {
                quotaSelect.disabled = true;
                quotaSelect.innerHTML = '<option value="">-- Không có chương trình nào đang mở --</option>';
            }
        });

        quotaSelect.addEventListener('change', function() {
            hideError('quota_id');
        });

        if (intakeSelect.value) {
            intakeSelect.dispatchEvent(new Event('change'));
        }
    </script>
</body>

</html>