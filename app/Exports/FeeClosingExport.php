<?php

namespace App\Exports;

use App\Models\Payment;
use App\Models\Student;
use App\Models\CommissionItem;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class FeeClosingExport implements FromCollection, WithHeadings, WithMapping, WithEvents, ShouldAutoSize {
    private int $rowNumber = 0;
    private float $totalAmount = 0;

    public function __construct(private array $data) {
    }

    public function collection() {
        $startDate = Carbon::parse($this->data['start_date'])->startOfDay();
        $endDate = Carbon::parse($this->data['end_date'])->endOfDay();

        $collaboratorId = $this->data['collaborator_id'] ?? null;
        
        return Student::query()
            ->where('collaborator_id', $collaboratorId)
            ->whereHas('payment', function ($query) use ($startDate, $endDate) {
                $query->where('status', Payment::STATUS_VERIFIED)
                    ->whereBetween('verified_at', [$startDate, $endDate]);
            })
            ->whereHas('commission.items', function ($query) use ($collaboratorId) {
                $query->where('recipient_collaborator_id', $collaboratorId)
                    ->where('status', CommissionItem::STATUS_PAYABLE);
            })
            ->with(['payment'])
            ->get();
    }

    public function headings(): array {
        $startDate = Carbon::parse($this->data['start_date'])->format('d/m');
        $endDate = Carbon::parse($this->data['end_date'])->format('d/m/Y');
        
        $title = $this->data['title'];
        
        if (empty($title)) {
            $collectorName = \App\Models\User::find($this->data['collector_user_id'])?->name ?? 'N/A';
            $title = "DANH SÁCH LỆ PHÍ {$collectorName} THU TỪ {$startDate} - {$endDate}";
        }

        return [
            [$title],
            [''], // Spacer row
            [
                'STT',
                'Khoá',
                'Họ và tên',
                'Ngày sinh',
                'Nơi sinh',
                'Lệ phí hồ sơ',
                'Ghi chú',
            ]
        ];
    }

    public function map($student): array {
        $this->rowNumber++;
        $amount = $student->payment?->amount ?? 0;
        $this->totalAmount += $amount;

        // Map program type to shorthand like in the image (LTCQ, TỪ XA)
        $programShorthand = match (strtoupper((string)$student->program_type)) {
            'REGULAR' => 'LTCQ',
            'PART_TIME' => 'VHVL',
            'DISTANCE' => 'TỪ XA',
            default => $student->program_type
        };

        return [
            $this->rowNumber,
            $programShorthand,
            $student->full_name,
            $student->dob ? Carbon::parse($student->dob)->format('d/m/y') : '',
            $student->birth_place,
            number_format($amount, 0, ',', '.'),
            $this->data['note'] ?: 'Lệ phí hồ sơ',
        ];
    }

    public function registerEvents(): array {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $lastRow = $this->rowNumber + 3; // +3 because of 3 header rows

                // Merge Title Row
                $sheet->mergeCells('A1:G1');
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
                $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                // Style Headers
                $headerRange = 'A3:G3';
                $sheet->getStyle($headerRange)->getFont()->setBold(true);
                $sheet->getStyle($headerRange)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle($headerRange)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

                // Style Body Borders
                $bodyRange = 'A4:G' . $lastRow;
                $sheet->getStyle($bodyRange)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                
                // Alignments
                $sheet->getStyle('A4:A' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('B4:B' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('D4:D' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('F4:F' . ($lastRow + 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

                // Footer Row for Total
                $footerRow = $lastRow + 1;
                $startDate = Carbon::parse($this->data['start_date'])->format('d/m');
                $endDate = Carbon::parse($this->data['end_date'])->format('d/m');
                
                $sheet->mergeCells("A{$footerRow}:E{$footerRow}");
                $sheet->setCellValue("A{$footerRow}", "CỘNG LỆ PHÍ HỒ SƠ TỪ NGÀY {$startDate}-{$endDate}");
                $sheet->setCellValue("F{$footerRow}", number_format($this->totalAmount, 0, ',', '.'));
                
                $sheet->getStyle("A{$footerRow}:G{$footerRow}")->getFont()->setBold(true);
                $sheet->getStyle("A{$footerRow}:G{$footerRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                $sheet->getStyle("A{$footerRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            },
        ];
    }
}
