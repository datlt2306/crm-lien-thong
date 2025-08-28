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
                <label class="block font-medium mb-1">Ngành muốn học <span class="text-red-500">*</span></label>
                <select name="major_id" id="major_id" class="w-full border rounded px-3 py-2 focus:outline-none focus:ring" required>
                    <option value="">-- Chọn ngành --</option>
                    @foreach(($majors ?? []) as $m)
                    <option value="{{ e($m['id']) }}" {{ old('major_id') == $m['id'] ? 'selected' : '' }}
                        @if(isset($m['quota']) && $m['quota'] <=0) disabled @endif>
                        {{ e($m['name']) }}
                        @if(isset($m['quota']))
                        @if($m['quota'] > 0)
                        (Chỉ tiêu còn lại: {{ e($m['quota']) }})
                        @else
                        (Hết chỉ tiêu)
                        @endif
                        @endif
                    </option>
                    @endforeach
                </select>
                <div id="major_id_error" class="text-red-500 text-sm mt-1 hidden">Vui lòng chọn ngành muốn học</div>
            </div>
            <div class="mb-3">
                <label class="block font-medium mb-1">Hệ đào tạo <span class="text-red-500">*</span></label>
                <select name="program_id" id="program_id" class="w-full border rounded px-3 py-2 focus:outline-none focus:ring" required>
                    <option value="">-- Chọn hệ đào tạo --</option>
                    @foreach(($programs ?? []) as $p)
                    <option value="{{ e($p['id']) }}" {{ old('program_id') == $p['id'] ? 'selected' : '' }}>{{ e($p['name']) }}</option>
                    @endforeach
                </select>
                <div id="program_id_error" class="text-red-500 text-sm mt-1 hidden">Vui lòng chọn hệ đào tạo</div>
            </div>
            <div class="mb-3">
                <label class="block font-medium mb-1">Đợt tuyển <span class="text-red-500">*</span></label>
                <select name="intake_month" id="intake_month" class="w-full border rounded px-3 py-2 focus:outline-none focus:ring" required>
                    <option value="">-- Chọn đợt tuyển --</option>
                    @foreach(($intakeMonths ?? []) as $month)
                    <option value="{{ e($month) }}" {{ old('intake_month') == $month ? 'selected' : '' }}>Tháng {{ e($month) }}</option>
                    @endforeach
                </select>
                <div id="intake_month_error" class="text-red-500 text-sm mt-1 hidden">Vui lòng chọn đợt tuyển</div>
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
        // Hiển thị thông tin chi tiết khi chọn ngành
        const majorSelect = document.getElementById('major_id');
        const programSelect = document.getElementById('program_id');
        const intakeSelect = document.getElementById('intake_month');
        const form = document.getElementById('student-form');

        // Lấy dữ liệu majors từ server
        const majorsData = @json($majors ?? []);
        const programsData = @json($programs ?? []);

        // Hàm hiển thị lỗi
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

        // Hàm ẩn lỗi
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

        // Hàm validate form
        function validateForm() {
            let isValid = true;

            // Validate ngành
            if (!majorSelect.value) {
                showError('major_id', 'Vui lòng chọn ngành muốn học');
                isValid = false;
            } else {
                hideError('major_id');
            }

            // Validate hệ đào tạo
            if (!programSelect.value) {
                showError('program_id', 'Vui lòng chọn hệ đào tạo');
                isValid = false;
            } else {
                hideError('program_id');
            }

            // Validate đợt tuyển
            if (!intakeSelect.value) {
                showError('intake_month', 'Vui lòng chọn đợt tuyển');
                isValid = false;
            } else {
                hideError('intake_month');
            }

            return isValid;
        }

        // Event listener cho form submit
        form.addEventListener('submit', function(e) {
            if (!validateForm()) {
                e.preventDefault();
                // Scroll đến field lỗi đầu tiên
                const firstError = document.querySelector('.border-red-500');
                if (firstError) {
                    firstError.scrollIntoView({
                        behavior: 'smooth',
                        block: 'center'
                    });
                }
            }
        });

        // Event listeners cho các field để ẩn lỗi khi user bắt đầu nhập
        majorSelect.addEventListener('change', function() {
            hideError('major_id');

            const selectedMajorId = this.value;
            const selectedMajor = majorsData.find(function(m) {
                return m.id == selectedMajorId;
            });

            if (selectedMajor) {
                // Reset và cập nhật đợt tuyển theo ngành đã chọn
                intakeSelect.innerHTML = '<option value="">-- Chọn đợt tuyển --</option>';
                if (selectedMajor.intake_months && selectedMajor.intake_months.length > 0) {
                    // Sắp xếp tháng theo thứ tự tăng dần
                    const sortedMonths = [...selectedMajor.intake_months].sort((a, b) => parseInt(a) - parseInt(b));
                    sortedMonths.forEach(function(month) {
                        const option = document.createElement('option');
                        option.value = month;
                        option.textContent = 'Tháng ' + month;
                        intakeSelect.appendChild(option);
                    });
                }

                // Reset và cập nhật hệ đào tạo theo ngành đã chọn
                if (selectedMajor.programs && selectedMajor.programs.length > 0) {
                    programSelect.innerHTML = '<option value="">-- Chọn hệ đào tạo --</option>';
                    selectedMajor.programs.forEach(function(program) {
                        const option = document.createElement('option');
                        option.value = program.id;
                        option.textContent = program.name;
                        programSelect.appendChild(option);
                    });
                } else {
                    // Fallback: hiển thị tất cả hệ đào tạo
                    programSelect.innerHTML = '<option value="">-- Chọn hệ đào tạo --</option>';
                    programsData.forEach(function(program) {
                        const option = document.createElement('option');
                        option.value = program.id;
                        option.textContent = program.name;
                        programSelect.appendChild(option);
                    });
                }

                // Hiển thị thông báo về hệ đào tạo
                const programInfo = document.getElementById('program-info');
                if (programInfo) {
                    programInfo.remove();
                }

                const infoDiv = document.createElement('div');
                infoDiv.id = 'program-info';
                infoDiv.className = 'mb-3 p-2 bg-blue-100 text-blue-800 rounded text-sm';

                let programNames = 'Tất cả hệ đào tạo';
                if (selectedMajor.programs && selectedMajor.programs.length > 0) {
                    programNames = selectedMajor.programs.map(p => p.name).join(', ');
                } else {
                    programNames = programsData.map(p => p.name).join(', ');
                }

                const monthText = (selectedMajor.intake_months && selectedMajor.intake_months.length) ?
                    ('Tháng ' + selectedMajor.intake_months.join(', ')) :
                    'Chưa cấu hình';
                infoDiv.innerHTML = '<strong>Thông tin ngành ' + selectedMajor.name + ':</strong><br>' +
                    'Chỉ tiêu: ' + selectedMajor.quota + ' sinh viên<br>' +
                    'Đợt tuyển: ' + monthText + '<br>' +
                    'Hệ đào tạo: ' + programNames;

                majorSelect.parentNode.insertBefore(infoDiv, majorSelect.nextSibling);
            }
        });

        // Event listeners cho các field khác
        programSelect.addEventListener('change', function() {
            hideError('program_id');
        });

        intakeSelect.addEventListener('change', function() {
            hideError('intake_month');
        });
    </script>
</body>

</html>