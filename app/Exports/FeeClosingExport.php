<?php

namespace App\Exports;

use App\Models\Payment;
use App\Models\CommissionItem;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;

class FeeClosingExport implements WithMultipleSheets {
    public function __construct(private array $data) {
    }

    public function sheets(): array {
        return [
            new FeeClosingSheet($this->data, 'payment_verified', 'Hàng tháng (Mùng 5)'),
            new FeeClosingSheet($this->data, 'student_enrolled', 'Sau khi nhập học'),
        ];
    }
}

class FeeClosingSheet implements FromCollection, WithHeadings, WithMapping, WithEvents, ShouldAutoSize, WithColumnFormatting, WithTitle {
    private int $rowNumber = 0;
    private float $totalAmount = 0;

    public function __construct(
        private array $data,
        private string $trigger,
        private string $sheetTitle
    ) {}

    public function title(): string {
        return $this->sheetTitle;
    }

    public function collection() {
        $startDate = Carbon::parse($this->data['start_date'])->startOfDay();
        $endDate = Carbon::parse($this->data['end_date'])->endOfDay();
        $collaboratorId = $this->data['collaborator_id'] ?? null;

        return CommissionItem::query()
            ->where('recipient_collaborator_id', $collaboratorId)
            ->where('trigger', $this->trigger)
            ->where('status', CommissionItem::STATUS_PAYABLE)
            ->whereBetween('payable_at', [$startDate, $endDate])
            ->with(['commission.student', 'commission.payment'])
            ->get();
    }

    public function headings(): array {
        $startDate = Carbon::parse($this->data['start_date'])->format('d/m');
        $endDate = Carbon::parse($this->data['end_date'])->format('d/m/Y');
        
        $mainTitle = $this->data['title'] ?? null;
        if (empty($mainTitle)) {
            $collectorId = $this->data['collector_user_id'] ?? null;
            $collectorName = $collectorId ? (\App\Models\User::find($collectorId)?->name ?? 'N/A') : 'HỆ THỐNG';
            $mainTitle = "DANH SÁCH LỆ PHÍ {$collectorName} THU TỪ {$startDate} - {$endDate}";
        }

        return [
            ["{$mainTitle} - " . strtoupper($this->sheetTitle)],
            [''], // Spacer row
            [
                'STT',
                'Khoá',
                'Họ và tên',
                'Ngày sinh',
                'Nơi sinh',
                'Số tiền',
                'Nội dung chi tiết',
            ]
        ];
    }

    public function map($item): array {
        $this->rowNumber++;
        $student = $item->commission?->student;
        $amount = (float)($item->amount ?? 0);
        $this->totalAmount += $amount;

        $programShorthand = $student ? match (strtoupper((string)$student->program_type)) {
            'REGULAR' => 'LTCQ',
            'PART_TIME' => 'VHVL',
            'DISTANCE' => 'TỪ XA',
            default => $student->program_type
        } : 'N/A';

        $description = $item->notes ?: ($item->meta['description'] ?? ($this->data['note'] ?? 'Lệ phí hồ sơ'));

        return [
            $this->rowNumber,
            $programShorthand,
            $student?->full_name ?? 'N/A',
            $student?->dob ? Carbon::parse($student->dob)->format('d/m/y') : '',
            $student?->birth_place ?? '',
            $amount,
            $description,
        ];
    }

    public function registerEvents(): array {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $lastRow = $this->rowNumber + 3;

                $sheet->mergeCells('A1:G1');
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
                $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $headerRange = 'A3:G3';
                $sheet->getStyle($headerRange)->getFont()->setBold(true);
                $sheet->getStyle($headerRange)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle($headerRange)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

                $bodyRange = 'A4:G' . $lastRow;
                $sheet->getStyle($bodyRange)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                
                $sheet->getStyle('A4:A' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('B4:B' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('D4:D' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('F4:F' . ($lastRow + 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

                $footerRow = $lastRow + 1;
                $startDate = Carbon::parse($this->data['start_date'])->format('d/m');
                $endDate = Carbon::parse($this->data['end_date'])->format('d/m');
                
                $sheet->mergeCells("A{$footerRow}:E{$footerRow}");
                $sheet->setCellValue("A{$footerRow}", "TỔNG CỘNG " . strtoupper($this->sheetTitle));
                $sheet->setCellValue("F{$footerRow}", $this->totalAmount);
                
                $sheet->getStyle("A{$footerRow}:G{$footerRow}")->getFont()->setBold(true);
                $sheet->getStyle("A{$footerRow}:G{$footerRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                $sheet->getStyle("A{$footerRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("F{$footerRow}")->getNumberFormat()->setFormatCode('#,##0');
            },
        ];
    }

    public function columnFormats(): array {
        return ['F' => '#,##0'];
    }
}
