@php($success = session('success'))
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng ký xét tuyển - Liên thông Đại học</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css">
</head>

<body class="bg-gray-100 min-h-screen flex items-center justify-center">
    <div class="bg-white p-8 rounded shadow-md w-full max-w-lg">
        <h1 class="text-2xl font-bold mb-4 text-center">Đăng ký <span class="font-semibold text-green-600">{{ e($collaborator->organization->name ?? 'N/A') }}</span></h1>
        @if($success)
        <div class="bg-green-100 text-green-800 p-3 rounded mb-4 text-center">{{ $success }}</div>
        @endif
        @if($errors->any())
        <div class="bg-red-100 text-red-800 p-3 rounded mb-4">
            <ul class="list-disc pl-5">
                @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif
        <form id="student-form" method="POST" action="">
            @csrf
            <div class="mb-3">
                <label class="block font-medium mb-1">Họ và tên *</label>
                <input type="text" name="full_name" value="{{ old('full_name') }}" required class="w-full border rounded px-3 py-2 focus:outline-none focus:ring" />
            </div>
            <div class="mb-3">
                <label class="block font-medium mb-1">Số điện thoại *</label>
                <input type="text" name="phone" value="{{ old('phone') }}" required class="w-full border rounded px-3 py-2 focus:outline-none focus:ring" />
            </div>
            <div class="mb-3">
                <label class="block font-medium mb-1">Email</label>
                <input type="email" name="email" value="{{ old('email') }}" class="w-full border rounded px-3 py-2 focus:outline-none focus:ring" />
            </div>

            <div class="mb-3">
                <label class="block font-medium mb-1">Ngày tháng năm sinh *</label>
                <input type="date" name="dob" value="{{ old('dob') }}" required class="w-full border rounded px-3 py-2 focus:outline-none focus:ring" />
            </div>
            <div class="mb-3">
                <label class="block font-medium mb-1">Địa chỉ *</label>
                <input type="text" name="address" value="{{ old('address') }}" required class="w-full border rounded px-3 py-2 focus:outline-none focus:ring" />
            </div>
            <input type="hidden" name="organization_id" value="{{ $collaborator->organization_id }}" />
            <div class="mb-3">
                <label class="block font-medium mb-1">Đợt tuyển sinh <span class="text-red-500">*</span></label>
                <select name="intake_id" id="intake_id" class="w-full border rounded px-3 py-2 focus:outline-none focus:ring" required>
                    <option value="">-- Chọn đợt tuyển --</option>
                    @foreach(($intakes ?? []) as $intake)
                    <option value="{{ e($intake->id) }}" {{ old('intake_id') == $intake->id ? 'selected' : '' }}>
                        {{ e($intake->name) }} (từ {{ $intake->start_date?->format('d/m/Y') }} đến {{ $intake->end_date?->format('d/m/Y') }})
                    </option>
                    @endforeach
                </select>
                <div id="intake_id_error" class="text-red-500 text-sm mt-1 hidden">Vui lòng chọn đợt tuyển</div>
            </div>

            <div class="mb-3">
                <label class="block font-medium mb-1">Chương trình đào tạo <span class="text-red-500">*</span></label>
                <select name="quota_id" id="quota_id" class="w-full border rounded px-3 py-2 focus:outline-none focus:ring" required disabled>
                    <option value="">-- Vui lòng chọn đợt tuyển trước --</option>
                </select>
                <div id="quota_id_error" class="text-red-500 text-sm mt-1 hidden">Vui lòng chọn chương trình đào tạo</div>
            </div>
            <div class="mb-3">
                <label class="block font-medium mb-1">Ghi chú</label>
                <textarea name="notes" class="w-full border rounded px-3 py-2 focus:outline-none focus:ring">{{ old('notes') }}</textarea>
            </div>
            <button type="submit" class="w-full bg-blue-600 text-white py-2 rounded font-semibold hover:bg-blue-700 transition">Gửi đăng ký</button>
        </form>
        <p class="mt-4 text-center text-gray-500 text-xs">&copy; {{ date('Y') }} Liên thông Đại học</p>
    </div>

    <script>
        const intakeSelect = document.getElementById('intake_id');
        const quotaSelect = document.getElementById('quota_id');
        const form = document.getElementById('student-form');

        const intakesData = @json($intakes ?? []);
        const oldQuotaId = @json(old('quota_id'));

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

                    let statusText = '';
                    if (!isActive) {
                        statusText = ' - Tạm dừng';
                    } else if (availableSlots <= 0) {
                        statusText = ' - Đã đủ chỉ tiêu';
                    }

                    const option = document.createElement('option');
                    option.value = quota.id;
                    option.textContent = quota.name + ' (Chỉ tiêu còn lại: ' + availableSlots + ')' + statusText;
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