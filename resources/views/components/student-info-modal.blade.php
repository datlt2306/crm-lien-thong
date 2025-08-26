<div class="p-6 space-y-6">
    <div class="flex items-center justify-between">
        <h2 class="text-xl font-semibold text-gray-900">Thông tin sinh viên</h2>
        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
            {{ $student->status ?? 'Chưa cập nhật' }}
        </span>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Họ và tên</label>
                <p class="text-sm text-gray-900">{{ $student->full_name }}</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Số điện thoại</label>
                <p class="text-sm text-gray-900">{{ $student->phone }}</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                <p class="text-sm text-gray-900">{{ $student->email ?? 'Chưa cập nhật' }}</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Ngày sinh</label>
                <p class="text-sm text-gray-900">{{ $student->birth_date ? \Carbon\Carbon::parse($student->birth_date)->format('d/m/Y') : 'Chưa cập nhật' }}</p>
            </div>
        </div>

        <div class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Ngành học</label>
                <p class="text-sm text-gray-900">{{ $student->major ?? 'Chưa cập nhật' }}</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Hệ đào tạo</label>
                <p class="text-sm text-gray-900">{{ $student->program_type ?? 'Chưa cập nhật' }}</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Tháng nhập học</label>
                <p class="text-sm text-gray-900">{{ $student->intake_month ?? 'Chưa cập nhật' }}</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Trường THPT</label>
                <p class="text-sm text-gray-900">{{ $student->high_school ?? 'Chưa cập nhật' }}</p>
            </div>
        </div>
    </div>

    @if($student->note)
    <div class="border-t pt-4">
        <label class="block text-sm font-medium text-gray-700 mb-1">Ghi chú</label>
        <p class="text-sm text-gray-900">{{ $student->note }}</p>
    </div>
    @endif

    <div class="border-t pt-4">
        <div class="grid grid-cols-2 gap-4 text-sm">
            <div>
                <span class="text-gray-500">Ngày tạo:</span>
                <span class="text-gray-900">{{ $student->created_at ? $student->created_at->format('d/m/Y H:i') : 'N/A' }}</span>
            </div>
            <div>
                <span class="text-gray-500">Cập nhật lần cuối:</span>
                <span class="text-gray-900">{{ $student->updated_at ? $student->updated_at->format('d/m/Y H:i') : 'N/A' }}</span>
            </div>
        </div>
    </div>
</div>