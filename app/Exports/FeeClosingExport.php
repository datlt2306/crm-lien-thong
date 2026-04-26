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
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Illuminate\Support\Facades\Auth;

class FeeClosingExport implements FromCollection, WithHeadings, WithMapping, WithEvents, ShouldAutoSize, WithColumnFormatting, WithTitle {
    private int $rowNumber = 0;
    private float $totalAmount = 0;

    public function __construct(private array $data) {
    }

    public function title(): string {
        return 'Bảng kê';
    }

    public function collection() {
        $startDate = Carbon::parse($this->data['start_date'])->startOfDay();
        $endDate = Carbon::parse($this->data['end_date'])->endOfDay();
        $collaboratorId = $this->data['collaborator_id'] ?? null;
        $status = $this->data['status'] ?? CommissionItem::STATUS_PAYABLE;
        
        $dateField = $status === CommissionItem::STATUS_PAYMENT_CONFIRMED ? 'payment_confirmed_at' : 'payable_at';

        return CommissionItem::query()
            ->where('recipient_collaborator_id', $collaboratorId)
            ->where('status', $status)
            ->whereBetween($dateField, [$startDate, $endDate])
            ->with(['commission.student', 'commission.payment'])
            ->get();
    }

    public function headings(): array {
        $startDate = Carbon::parse($this->data['start_date'])->format('d/m');
        $endDate = Carbon::parse($this->data['end_date'])->format('d/m/Y');
        
        $status = $this->data['status'] ?? CommissionItem::STATUS_PAYABLE;
        $titlePrefix = $status === CommissionItem::STATUS_PAYMENT_CONFIRMED ? 'BÁO CÁO HOA HỒNG ĐÃ CHI' : 'DANH SÁCH LỆ PHÍ';

        $mainTitle = $this->data['title'] ?? null;
        if (empty($mainTitle)) {
            $collaboratorId = $this->data['collaborator_id'] ?? null;
            $collaboratorName = $collaboratorId ? (\App\Models\Collaborator::find($collaboratorId)?->full_name ?? 'N/A') : 'HỆ THỐNG';
            $mainTitle = "{$titlePrefix} - {$collaboratorName} TỪ {$startDate} - {$endDate}";
        }

        return [
            [$mainTitle],
            [
                'STT',
                'Hệ',
                'Mã hồ sơ',
                'Họ và tên',
                'Ngày sinh',
                'Nơi sinh',
                'Số tiền',
                'Nội dung chi tiết',
                'Mã thanh toán (ID - KHÔNG SỬA)'
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

        $description = $item->notes ?: ($this->data['note'] ?? '');

        return [
            $this->rowNumber,
            $programShorthand,
            $student?->profile_code ?? 'N/A',
            $student?->full_name ?? 'N/A',
            $student?->dob ? Carbon::parse($student->dob)->format('d/m/y') : '',
            $student?->birth_place ?? '',
            $amount,
            $description,
            $item->id // Cột I: ID
        ];
    }

    public function registerEvents(): array {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $lastRow = $this->rowNumber + 2;

                $sheet->mergeCells('A1:I1');
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
                $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $headerRange = 'A2:I2';
                $sheet->getStyle($headerRange)->getFont()->setBold(true);
                $sheet->getStyle($headerRange)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle($headerRange)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

                $bodyRange = 'A3:I' . $lastRow;
                $sheet->getStyle($bodyRange)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                
                $sheet->getStyle('A3:A' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('B3:B' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('C3:C' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('E3:E' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('G3:G' . ($lastRow + 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                $sheet->getStyle('I3:I' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                // Ẩn cột ID
                $sheet->getColumnDimension('I')->setVisible(false);

                $footerRow = $lastRow + 1;
                $sheet->mergeCells("A{$footerRow}:F{$footerRow}");
                $sheet->setCellValue("A{$footerRow}", "TỔNG CỘNG");
                $sheet->setCellValue("G{$footerRow}", $this->totalAmount);
                
                $sheet->getStyle("A{$footerRow}:I{$footerRow}")->getFont()->setBold(true);
                $sheet->getStyle("A{$footerRow}:I{$footerRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                $sheet->getStyle("A{$footerRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("G{$footerRow}")->getNumberFormat()->setFormatCode('#,##0');
            },
        ];
    }

    public function columnFormats(): array {
        return ['G' => '#,##0'];
    }
}
