<?php

namespace App\Exports;

use App\Models\Payment;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use Illuminate\Database\Eloquent\Builder;

class StudentsExcelExport implements FromQuery, WithHeadings, WithMapping, WithEvents, ShouldAutoSize {
    private int $rowNumber = 0;

    public function __construct(private readonly Builder $query) {
    }

    public function query(): Builder {
        return $this->query;
    }

    public function headings(): array {
        // Row 1 (group headers) + Row 2 (sub headers)
        return [
            [
                'STT',
                'Ngày tháng',
                'GVHD',
                'Họ và tên',
                'Ngày sinh',
                'Nơi sinh',
                'Hộ khẩu thường trú',
                'Số điện thoại',
                'Dân tộc',
                'Giới tính',
                'CCCD',
                'Ngày cấp',
                'Nơi cấp',
                'Bằng TN CĐ',
                '',
                'Bảng điểm CĐ',
                '',
                'Bằng TN THPT',
                '',
                'Trung cấp',
                '',
                'Điểm TB TC',
                'Giấy khai sinh',
                '',
                'CCCD (mặt trước)',
                'CCCD (mặt sau)',
                'Ảnh thẻ',
                'Giấy khám sức khỏe',
                '',
                'Trường TN CĐ',
                'Ngành TN CĐ',
                'Xếp loại TN CĐ',
                'Hệ TN CĐ',
                'Năm TN CĐ',
                'Số hiệu bằng TN CĐ',
                'Số vào sổ cấp bằng TN CĐ',
                'Ngày ký bằng TN CĐ',
                'Người ký bằng TN CĐ',
                'Điểm TB CĐ',
                'Ngành ĐKLT',
                'Trường ĐKLT',
                'Hệ ĐKLT',
                'Đợt ĐKLT',
                'Tên trường THPT',
                'Mã trường',
                'Tên tỉnh/TP',
                'Mã tỉnh',
                'Tên Quận/huyện',
                'Mã Quận/huyện',
                'KV ưu tiên',
                'Năm TN THPT',
                'Học lực cả năm',
                'Hạnh kiểm',
                'Trạng thái hồ sơ',
                'Tình trạng',
                'Phiếu tuyển sinh',
                'Hình thức tuyển sinh',
                'Lệ phí',
                'Chuyển hệ',
                'Hoàn tiền',
                'Ghi chú',
            ],
            [
                '', '', '', '', '', '', '', '', '', '', '', '', '',
                'BS', 'BG',
                'BS', 'BG',
                'BS', 'BG',
                'Bằng', 'Bảng điểm', '',
                'BS', 'BG',
                '', '', '',
                'BS', 'BG',
                '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '',
            ],
        ];
    }

    public function map($row): array {
        /** @var Student $student */
        $student = $row;

        $this->rowNumber++;

        $paymentStatus = '';
        $paymentAmount = '';
        if ($student->payment) {
            $paymentStatus = match ($student->payment->status) {
                Payment::STATUS_NOT_PAID => 'Chưa nộp tiền',
                Payment::STATUS_SUBMITTED => 'Đã nộp, chờ xác minh',
                Payment::STATUS_VERIFIED => 'Đã nộp tiền',
                default => (string) $student->payment->status,
            };
            $paymentAmount = ($student->payment->amount ?? 0) > 0
                ? number_format((float) $student->payment->amount, 0, ',', '.') . ' đ'
                : 'Chưa cập nhật';
        }

        $programType = match ($student->program_type) {
            'REGULAR' => 'Chính quy',
            'PART_TIME' => 'Vừa học vừa làm',
            default => $student->program_type ?? '—',
        };

        $intakeDisplay = $student->intake?->name ?: ($student->intake_month ? "Tháng {$student->intake_month}" : '—');

        $checklist = $student->document_checklist ?? [];
        $requiredDocs = ['phieu_tuyen_sinh', 'bang_cao_dang', 'bang_thpt', 'bang_diem', 'giay_khai_sinh', 'cccd', 'giay_kham_suc_khoe', 'anh_4x6'];
        $missingDocs = array_diff($requiredDocs, $checklist);
        $hasAllDocs = empty($missingDocs);

        $applicationStatus = 'Đang nhập';
        if ($student->status === Student::STATUS_REJECTED) {
            $applicationStatus = 'Không đủ điều kiện';
        } elseif (in_array($student->status, [Student::STATUS_NEW, Student::STATUS_CONTACTED], true)) {
            $applicationStatus = 'Đang nhập';
        } elseif (in_array($student->status, [Student::STATUS_SUBMITTED, Student::STATUS_APPROVED, Student::STATUS_ENROLLED], true) && !$hasAllDocs) {
            $applicationStatus = 'Thiếu giấy tờ';
        } elseif (in_array($student->status, [Student::STATUS_SUBMITTED, Student::STATUS_APPROVED], true) && $hasAllDocs) {
            $applicationStatus = 'Đã nộp';
        } elseif ($student->status === Student::STATUS_ENROLLED && $hasAllDocs) {
            $applicationStatus = 'Đủ điều kiện';
        }

        $refundStatus = '';
        if ($student->payment && $student->payment->excess_amount > 0) {
            $refundStatus = match ($student->payment->refund_status) {
                'none' => 'Không có',
                'pending' => '⏳ Chờ hoàn trả (' . number_format($student->payment->excess_amount, 0, ',', '.') . 'đ)',
                'completed' => '✅ Đã hoàn trả',
                default => (string) $student->payment->refund_status,
            };
        }

        $createdAtFormatted = $student->created_at
            ? Carbon::parse($student->created_at)->format('d/m/Y')
            : '';

        $dobFormatted = $student->dob
            ? Carbon::parse($student->dob)->format('d/m/Y')
            : '';

        $issueDateFormatted = $student->identity_card_issue_date
            ? Carbon::parse($student->identity_card_issue_date)->format('d/m/Y')
            : '';

        $collegeIssueDateFormatted = $student->college_diploma_issue_date
            ? Carbon::parse($student->college_diploma_issue_date)->format('d/m/Y')
            : '';

        $genderLabel = match ($student->gender) {
            'male' => 'Nam',
            'female' => 'Nữ',
            'other' => 'Khác',
            default => $student->gender ?? '',
        };

        $collegeDiplomaBS = (($student->college_diploma_copy_type ?? '') === 'BS') ? 'x' : '';
        $collegeDiplomaBG = (($student->college_diploma_copy_type ?? '') === 'BG') ? 'x' : '';
        $collegeTranscriptBS = (($student->college_transcript_copy_type ?? '') === 'BS') ? 'x' : '';
        $collegeTranscriptBG = (($student->college_transcript_copy_type ?? '') === 'BG') ? 'x' : '';
        $highSchoolDiplomaBS = (($student->high_school_diploma_copy_type ?? '') === 'BS') ? 'x' : '';
        $highSchoolDiplomaBG = (($student->high_school_diploma_copy_type ?? '') === 'BG') ? 'x' : '';
        $birthCertBS = (($student->birth_certificate_copy_type ?? '') === 'BS') ? 'x' : '';
        $birthCertBG = (($student->birth_certificate_copy_type ?? '') === 'BG') ? 'x' : '';
        $healthCertBS = (($student->health_certificate_copy_type ?? '') === 'BS') ? 'x' : '';
        $healthCertBG = (($student->health_certificate_copy_type ?? '') === 'BG') ? 'x' : '';

        $admissionForm = in_array('phieu_tuyen_sinh', $checklist, true) ? 'x' : '';

        return [
            $this->rowNumber,
            $createdAtFormatted,
            $student->instructor ?? '',
            $student->full_name ?? '',
            $dobFormatted,
            $student->birth_place ?? '',
            $student->permanent_residence ?? '',
            $student->phone ?? '',
            $student->ethnicity ?? '',
            $genderLabel,
            $student->identity_card ?? '',
            $issueDateFormatted,
            $student->identity_card_issue_place ?? '',
            $collegeDiplomaBS,
            $collegeDiplomaBG,
            $collegeTranscriptBS,
            $collegeTranscriptBG,
            $highSchoolDiplomaBS,
            $highSchoolDiplomaBG,
            $student->document_intermediate_diploma ?? '',
            $student->document_intermediate_transcript ?? '',
            $student->intermediate_gpa ?? '',
            $birthCertBS,
            $birthCertBG,
            $student->document_identity_card_front ?? '',
            $student->document_identity_card_back ?? '',
            $student->document_photo ?? '',
            $healthCertBS,
            $healthCertBG,
            $student->college_graduation_school ?? '',
            $student->college_graduation_major ?? '',
            $student->college_graduation_grade ?? '',
            $student->college_training_type ?? '',
            $student->college_graduation_year ?? '',
            $student->college_diploma_number ?? '',
            $student->college_diploma_book_number ?? '',
            $collegeIssueDateFormatted,
            $student->college_diploma_signer ?? '',
            $student->college_gpa ?? '',
            $student->major ?? '',
            $student->target_university ?? '',
            $programType,
            $intakeDisplay,
            $student->high_school_name ?? '',
            $student->high_school_code ?? '',
            $student->high_school_province ?? '',
            $student->high_school_province_code ?? '',
            $student->high_school_district ?? '',
            $student->high_school_district_code ?? '',
            $student->priority_area ?? '',
            $student->high_school_graduation_year ?? '',
            $student->high_school_academic_performance ?? '',
            $student->high_school_conduct ?? '',
            $applicationStatus,
            $paymentStatus,
            $admissionForm,
            $student->source ?? '',
            $paymentAmount,
            $student->has_transferred ? 'Đã chuyển' : 'Không',
            $refundStatus,
            $student->notes ?? '',
        ];
    }

    public function registerEvents(): array {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // Lấy Cột chữ để merge chính xác
                $columnsToMergeVertical = [
                    'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M',
                    'X', 'Y', 'Z',
                    'AC', 'AD', 'AE', 'AF', 'AG', 'AH', 'AI', 'AJ', 'AK', 'AL', 'AM', 'AN', 'AO', 'AP', 'AQ', 'AR', 'AS', 'AT', 'AU', 'AV', 'AW', 'AX', 'AY', 'AZ',
                    'BA', 'BB', 'BC', 'BD', 'BE', 'BF', 'BG', 'BH', 'BI'
                ];
                foreach ($columnsToMergeVertical as $col) {
                    $sheet->mergeCells("{$col}1:{$col}2");
                }

                $sheet->mergeCells('N1:O1');  // Bằng TN CĐ
                $sheet->mergeCells('P1:Q1');  // Bảng điểm CĐ
                $sheet->mergeCells('R1:S1');  // Bằng TN THPT
                $sheet->mergeCells('T1:U1');  // Trung cấp
                $sheet->mergeCells('V1:W1');  // Giấy khai sinh
                $sheet->mergeCells('AA1:AB1'); // Giấy khám sức khỏe

                // Style header rows - BI là cột 61
                $headerRange = 'A1:BI2';
                $sheet->getStyle($headerRange)->getFont()->setBold(true);
                $sheet->getStyle($headerRange)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle($headerRange)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
                $sheet->getStyle($headerRange)->getAlignment()->setWrapText(true);

                $sheet->getRowDimension(1)->setRowHeight(28);
                $sheet->getRowDimension(2)->setRowHeight(22);

                // Borders for header
                $sheet->getStyle($headerRange)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

                // Freeze header
                $sheet->freezePane('A3');
            },
        ];
    }
}
