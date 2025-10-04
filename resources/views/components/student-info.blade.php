<div class="p-4">
    @if($error)
    <div class="text-red-600">{{ $error }}</div>
    @elseif($student)
    <div class="space-y-2">
        <div><span class="font-medium">Tên:</span> {{ $student->full_name }}</div>
        <div><span class="font-medium">Email:</span> {{ $student->email }}</div>
        <div><span class="font-medium">Số điện thoại:</span> {{ $student->phone }}</div>
        <div><span class="font-medium">Ngành học:</span> {{ $student->major->name ?? 'N/A' }}</div>
    </div>
    @endif
</div>