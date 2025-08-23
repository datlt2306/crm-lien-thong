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
        <h1 class="text-2xl font-bold mb-4 text-center">Đăng ký xét tuyển</h1>
        <!-- <p class="mb-2 text-center text-gray-600">Mã giới thiệu: <span class="font-semibold text-blue-600">{{ $ref_id }}</span></p> -->
        <p class="mb-4 text-center text-gray-600">Mã giới thiệu thuộc: <span class="font-semibold text-green-600">{{ $collaborator->organization->name ?? 'N/A' }}</span></p>
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
        <form method="POST" action="">
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
                <label class="block font-medium mb-1">Ngành muốn học</label>
                <select name="major_id" id="major_id" class="w-full border rounded px-3 py-2 focus:outline-none focus:ring">
                    <option value="">-- Chọn ngành --</option>
                    @foreach(($majors ?? []) as $m)
                    <option value="{{ $m['id'] }}" {{ old('major_id') == $m['id'] ? 'selected' : '' }}>
                        {{ $m['name'] }}
                        @if(isset($m['quota']) && $m['quota'])
                        (Chỉ tiêu: {{ $m['quota'] }})
                        @endif
                    </option>
                    @endforeach
                </select>
            </div>
            <div class="mb-3">
                <label class="block font-medium mb-1">Hệ đào tạo</label>
                <select name="program_id" id="program_id" class="w-full border rounded px-3 py-2 focus:outline-none focus:ring">
                    <option value="">-- Chọn hệ đào tạo --</option>
                    @foreach(($programs ?? []) as $p)
                    <option value="{{ $p['id'] }}" {{ old('program_id') == $p['id'] ? 'selected' : '' }}>{{ $p['name'] }}</option>
                    @endforeach
                </select>
            </div>
            <div class="mb-3">
                <label class="block font-medium mb-1">Đợt tuyển</label>
                <select name="intake_month" id="intake_month" class="w-full border rounded px-3 py-2 focus:outline-none focus:ring">
                    <option value="">-- Chọn đợt tuyển --</option>
                    @foreach(($intakeMonths ?? []) as $month)
                    <option value="{{ $month }}" {{ old('intake_month') == $month ? 'selected' : '' }}>Tháng {{ $month }}</option>
                    @endforeach
                </select>
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

        // Lấy dữ liệu majors từ server
        const majorsData = JSON.parse('{!! json_encode($majors ?? []) !!}');
        const programsData = JSON.parse('{!! json_encode($programs ?? []) !!}');

        majorSelect.addEventListener('change', function() {
            const selectedMajorId = this.value;
            const selectedMajor = majorsData.find(function(m) {
                return m.id == selectedMajorId;
            });

            if (selectedMajor) {
                // Cập nhật đợt tuyển theo ngành đã chọn
                intakeSelect.innerHTML = '<option value="">-- Chọn đợt tuyển --</option>';
                if (selectedMajor.intake_months && selectedMajor.intake_months.length > 0) {
                    selectedMajor.intake_months.forEach(function(month) {
                        const option = document.createElement('option');
                        option.value = month;
                        option.textContent = 'Tháng ' + month;
                        intakeSelect.appendChild(option);
                    });
                }

                // Cập nhật hệ đào tạo theo ngành đã chọn
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
                majorSelect.parentNode.insertBefore(infoDiv, majorSelect.nextSibling);
            }
        });
    </script>
</body>

</html>