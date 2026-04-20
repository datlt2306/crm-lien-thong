<?php

namespace App\Exports;

use App\Models\AuditLog;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class AuditLogExport implements FromCollection, WithHeadings, WithMapping
{
    protected $query;

    public function __construct($query)
    {
        $this->query = $query;
    }

    public function collection()
    {
        return $this->query->get();
    }

    public function headings(): array
    {
        return [
            'Thời gian',
            'Nhóm sự kiện',
            'Hành động',
            'Đối tượng',
            'ID Đối tượng',
            'Người thực hiện',
            'Vai trò',
            'Hồ sơ liên quan',
            'Chênh lệch tiền',
            'Lý do',
            'IP Address',
        ];
    }

    public function map($record): array
    {
        return [
            $record->created_at->format('d/m/Y H:i:s'),
            $record->event_group,
            $record->event_type,
            class_basename($record->auditable_type),
            $record->auditable_id,
            $record->user?->name,
            $record->user_role,
            $record->student?->full_name,
            $record->amount_diff,
            $record->reason,
            $record->ip_address,
        ];
    }
}
