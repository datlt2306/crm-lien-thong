<?php

namespace App\Filament\Resources\Students\Schemas;

use App\Models\Intake;
use App\Models\Organization;
use App\Models\Student;
use App\Models\Payment;
use App\Models\StudentUpdateLog;
use App\Models\Collaborator;
use App\Services\StudentFeeService;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Facades\Schema as SchemaFacade;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\FileUpload;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Tabs;
use Illuminate\Support\Facades\Auth;

class StudentForm {
    private static function getProgramLabel(?string $programCode): string {
        return match (strtoupper((string) $programCode)) {
            'REGULAR' => 'Chính quy',
            'PART_TIME' => 'Vừa học vừa làm',
            'DISTANCE' => 'Đào tạo từ xa',
            default => $programCode ?: 'Chưa xác định',
        };
    }

    public static function configure(Schema $schema): Schema {
        return $schema
            ->columns(12)
            ->schema([
                // Left section: 8 columns - các tab thông tin & upload giấy tờ
                Tabs::make('StudentInformation')
                    ->columnSpan(fn() => Auth::user()?->role === 'ctv' ? 12 : 9)
                    ->tabs([
                        // Tab 1: Thông tin cơ bản
                        Tabs\Tab::make('Thông tin cơ bản')
                            ->icon('heroicon-o-identification')
                            ->schema([
                                TextInput::make('full_name')
                                    ->label('Họ và tên')
                                    ->required(),
                                TextInput::make('instructor')
                                    ->label('GVHD')
                                    ->default(function (?Student $record) {
                                        $user = Auth::user();

                                        // Nếu đang edit và đã có instructor, giữ nguyên
                                        if ($record && $record->instructor) {
                                            return $record->instructor;
                                        }

                                        // Nếu user là CTV hoặc organization_owner, lấy từ collaborator
                                        if ($user && in_array($user->role, ['ctv', 'organization_owner'])) {
                                            $collaborator = Collaborator::where('email', $user->email)->first();
                                            if ($collaborator && !empty($collaborator->full_name)) {
                                                return $collaborator->full_name;
                                            }
                                        }

                                        // Fallback về user name
                                        return $user?->name ?? '';
                                    })
                                    ->afterStateHydrated(function (TextInput $component, $state, ?Student $record) {
                                        // Nếu state rỗng và đang tạo mới, tự động điền từ collaborator
                                        if (empty($state) && !$record) {
                                            $user = Auth::user();
                                            if ($user && in_array($user->role, ['ctv', 'organization_owner'])) {
                                                $collaborator = Collaborator::where('email', $user->email)->first();
                                                if ($collaborator && !empty($collaborator->full_name)) {
                                                    $component->state($collaborator->full_name);
                                                } elseif ($user->name) {
                                                    $component->state($user->name);
                                                }
                                            } elseif ($user && $user->name) {
                                                $component->state($user->name);
                                            }
                                        }
                                    })
                                    ->maxLength(255),
                                TextInput::make('phone')
                                    ->label('Số điện thoại')
                                    ->tel()
                                    ->required()
                                    ->unique(ignoreRecord: true)
                                    ->validationMessages([
                                        'unique' => 'Số điện thoại đã được sử dụng bởi học viên khác.',
                                    ]),
                                TextInput::make('email')
                                    ->label('Email')
                                    ->email()
                                    ->unique(ignoreRecord: true)
                                    ->validationMessages([
                                        'unique' => 'Email đã được sử dụng bởi học viên khác.',
                                    ]),
                                Select::make('collaborator_id')
                                    ->label('Người giới thiệu')
                                    ->relationship('collaborator', 'full_name')
                                    ->searchable()
                                    ->preload()
                                    ->helperText('CTV/Đối tác giới thiệu học viên này')
                                    ->visible(fn($get) => Auth::user()?->role !== 'ctv' && ($get('source') ?? null) === 'ref'),
                                Select::make('organization_id')
                                    ->label('Tổ chức')
                                    ->options(fn() => Organization::orderBy('name')->pluck('name', 'id'))
                                    ->searchable()
                                    ->preload()
                                    ->default(fn() => Auth::user()?->organization_id)
                                    ->visible(false)
                                    ->required(false)
                                    ->helperText('Tổ chức quản lý học viên')
                                    ->columnSpanFull(),
                                Select::make('target_university')
                                    ->label('Trường đăng ký liên thông')
                                    ->options(fn() => Organization::orderBy('name')->pluck('name', 'name'))
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->helperText('Chọn trường/tổ chức đào tạo liên thông'),
                                \Filament\Forms\Components\Select::make('intake_id')
                                    ->label('Đợt đăng ký liên thông')
                                    ->options(function ($get) {
                                        $orgId = $get('organization_id') ?? Auth::user()?->organization_id;
                                        if (!$orgId) {
                                            return [];
                                        }
                                        return Intake::where('organization_id', $orgId)
                                            ->whereIn('status', [Intake::STATUS_ACTIVE, Intake::STATUS_UPCOMING, Intake::STATUS_CLOSED])
                                            ->with(['quotas' => function ($query) {
                                                $query->where('status', \App\Models\Quota::STATUS_ACTIVE);
                                            }])
                                            ->orderBy('start_date')
                                            ->get()
                                            ->mapWithKeys(function ($intake) {
                                                $programs = $intake->quotas
                                                    ->pluck('program_name')
                                                    ->filter()
                                                    ->unique()
                                                    ->map(fn($p) => self::getProgramLabel($p))
                                                    ->values()
                                                    ->toArray();

                                                $programText = empty($programs)
                                                    ? 'Chưa cấu hình hệ'
                                                    : implode(', ', $programs);

                                                $label = "{$intake->name} ({$programText})";
                                                return [$intake->id => $label];
                                            });
                                    })
                                    ->searchable()
                                    ->required()
                                    ->live()
                                    ->helperText('Chọn đợt tuyển sinh theo đúng hệ đào tạo cần đăng ký'),

                                \Filament\Forms\Components\Select::make('quota_id')
                                    ->label('Chương trình tuyển sinh (Khóa học)')
                                    ->options(function ($get) {
                                        $intakeId = $get('intake_id');
                                        if (!$intakeId) {
                                            return [];
                                        }
                                        return \App\Models\Quota::where('intake_id', $intakeId)
                                            ->where('status', \App\Models\Quota::STATUS_ACTIVE)
                                            ->orderByDesc('id')
                                            ->get()
                                            ->unique(function ($quota) {
                                                return ($quota->major_name ?: $quota->name) . '|' . strtoupper((string) $quota->program_name);
                                            })
                                            ->mapWithKeys(function ($quota) {
                                                $programLabel = self::getProgramLabel($quota->program_name);
                                                $label = ($quota->major_name ?: $quota->name) . ' - ' . $programLabel;
                                                return [$quota->id => $label];
                                            });
                                    })
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->helperText('Chọn ngành và hệ đào tạo tương ứng (chỉ hiển thị các chỉ tiêu đang mở)'),

                                \Filament\Forms\Components\Select::make('source')
                                    ->label('Hình thức tuyển sinh')
                                    ->options([
                                        'form' => 'Form website',
                                        'ref' => 'Giới thiệu (CTV/Đối tác)',
                                        'facebook' => 'Facebook',
                                        'zalo' => 'Zalo',
                                        'tiktok' => 'TikTok',
                                        'hotline' => 'Hotline',
                                        'event' => 'Sự kiện',
                                        'school' => 'Trường THPT/Trung tâm',
                                        'walkin' => 'Đến trực tiếp',
                                        'other' => 'Khác',
                                    ])
                                    ->required()
                                    ->default('form')
                                    ->live()
                                    ->afterStateUpdated(function ($state, callable $set) {
                                        // Nếu học viên "đến trực tiếp" thì không được gán CTV giới thiệu
                                        if ($state === 'walkin') {
                                            $set('collaborator_id', null);
                                        }
                                    }),
                                \Filament\Forms\Components\Select::make('application_status')
                                    ->label('Tình trạng hồ sơ')
                                    ->options([
                                        'draft' => 'Đang nhập',
                                        'pending_documents' => 'Thiếu giấy tờ',
                                        'submitted' => 'Đã nộp hồ sơ',
                                        'verified' => 'Đã xác minh',
                                        'eligible' => 'Đủ điều kiện',
                                        'ineligible' => 'Không đủ điều kiện',
                                    ])
                                    ->searchable()
                                    ->nullable()
                                    ->default(function (?Student $record) {
                                        // Nếu đã có giá trị trong record thì dùng giá trị đó
                                        if ($record?->application_status) {
                                            return $record->application_status;
                                        }

                                        // Nếu chưa có application_status nhưng có Payment, tự động map từ Payment status
                                        if ($record?->payment) {
                                            return match ($record->payment->status) {
                                                Payment::STATUS_SUBMITTED => 'submitted',
                                                Payment::STATUS_VERIFIED => 'verified',
                                                default => null,
                                            };
                                        }

                                        return null;
                                    })
                                    ->helperText('Chọn tình trạng chi tiết của hồ sơ')
                                    ->visible(fn() => Auth::user()?->role !== 'ctv'),
                                \Filament\Forms\Components\Textarea::make('address')
                                    ->label('Địa chỉ')
                                    ->rows(3)
                                    ->helperText('Nhập địa chỉ của sinh viên'),
                                \Filament\Forms\Components\TextInput::make('fee')
                                    ->label('Lệ phí (VNĐ)')
                                    ->default(function (?Student $record) {
                                        // Ưu tiên lấy từ payment->amount
                                        if ($record?->payment && $record->payment->amount) {
                                            $amount = (float) $record->payment->amount;
                                            if ($amount > 0) {
                                                return number_format((int) round($amount), 0, '', '.');
                                            }
                                        }
                                        // Nếu có fee trong student record
                                        if ($record?->fee && $record->fee >= 100) {
                                            return number_format((int) $record->fee, 0, '', '.');
                                        }
                                        return '';
                                    })
                                    ->formatStateUsing(function ($state, ?Student $record) {
                                        // Luôn ưu tiên lấy từ payment->amount để đảm bảo hiển thị đúng
                                        if ($record?->payment && $record->payment->amount) {
                                            $amount = (float) $record->payment->amount;
                                            if ($amount > 0) {
                                                return number_format((int) round($amount), 0, '', '.');
                                            }
                                        }

                                        // Nếu không có payment, format từ state
                                        if (!empty($state) && $state != 0 && $state != '0') {
                                            // Loại bỏ dấu chấm và dấu phẩy để lấy số
                                            $numericValue = is_string($state)
                                                ? (int) str_replace(['.', ',', ' '], '', $state)
                                                : (int) round((float) $state);

                                            if ($numericValue > 0) {
                                                return number_format($numericValue, 0, '', '.');
                                            }
                                        }
                                        return '';
                                    })
                                    ->dehydrateStateUsing(function ($state) {
                                        if (empty($state)) {
                                            return null;
                                        }
                                        // Loại bỏ dấu chấm và dấu phẩy, chuyển thành số
                                        if (is_string($state)) {
                                            return (int) str_replace(['.', ',', ' '], '', $state);
                                        }
                                        return (int) round((float) $state);
                                    })
                                    ->live()
                                    ->afterStateUpdated(function ($state, callable $set) {
                                        if (!empty($state)) {
                                            // Format lại khi người dùng nhập
                                            $numericValue = (int) str_replace(['.', ',', ' '], '', $state);
                                            if ($numericValue > 0) {
                                                $formatted = number_format($numericValue, 0, '', '.');
                                                $set('fee', $formatted);
                                            }
                                        }
                                    })
                                    ->placeholder(function (?Student $record) {
                                        if (!$record?->payment) {
                                            return 'Chưa có thông tin lệ phí';
                                        }

                                        $amount = (float) ($record->payment->amount ?? 0);
                                        if ($amount <= 0) {
                                            return 'Chưa cập nhật số tiền';
                                        }

                                        return null;
                                    })
                                    ->suffix(function (?Student $record) {
                                        if (!$record?->payment) {
                                            return 'Chưa nộp';
                                        }

                                        return match ($record->payment->status) {
                                            Payment::STATUS_NOT_PAID => 'Chưa nộp',
                                            Payment::STATUS_SUBMITTED => 'Chờ xác minh',
                                            Payment::STATUS_VERIFIED => 'Đã nộp',
                                            Payment::STATUS_REVERTED => 'Đã hoàn trả',
                                            default => $record->payment->status,
                                        };
                                    })
                                    ->helperText('Nếu đã nộp, số tiền hiển thị trong ô và trạng thái hiển thị bên phải')
                                    ->visible(fn() => Auth::user()?->role !== 'ctv'),
                                Textarea::make('notes')
                                    ->label('Ghi chú')
                                    ->columnSpanFull(),
                            ])
                            ->columns(2),

                        // Tab 2: Thông tin cá nhân
                        Tabs\Tab::make('Thông tin cá nhân')
                            ->icon('heroicon-o-user')
                            ->visible(fn() => Auth::user()?->role !== 'ctv')
                            ->schema([
                                \Filament\Forms\Components\DatePicker::make('dob')
                                    ->label('Ngày sinh')
                                    ->displayFormat('d/m/Y')
                                    ->required()
                                    ->helperText('Chọn ngày tháng năm sinh của sinh viên'),
                                TextInput::make('birth_place')
                                    ->label('Nơi sinh')
                                    ->required()
                                    ->maxLength(255),
                                Textarea::make('permanent_residence')
                                    ->label('Hộ khẩu thường trú')
                                    ->rows(2)
                                    ->columnSpanFull(),
                                TextInput::make('ethnicity')
                                    ->label('Dân tộc')
                                    ->maxLength(100),
                                Select::make('gender')
                                    ->label('Giới tính')
                                    ->options([
                                        'male' => 'Nam',
                                        'female' => 'Nữ',
                                        'other' => 'Khác',
                                    ]),
                                TextInput::make('identity_card')
                                    ->label('Số CCCD')
                                    ->required()
                                    ->helperText('Nhập số căn cước công dân của học viên')
                                    ->unique(ignoreRecord: true)
                                    ->validationMessages([
                                        'unique' => 'Số căn cước công dân đã được sử dụng bởi học viên khác.',
                                    ]),
                                \Filament\Forms\Components\DatePicker::make('identity_card_issue_date')
                                    ->label('Ngày cấp CCCD')
                                    ->displayFormat('d/m/Y'),
                                TextInput::make('identity_card_issue_place')
                                    ->label('Nơi cấp CCCD')
                                    ->maxLength(255)
                                    ->columnSpanFull(),
                            ])
                            ->columns(2),

                        // Tab 3: Thông tin THPT
                        Tabs\Tab::make('Thông tin THPT')
                            ->icon('heroicon-o-academic-cap')
                            ->visible(fn() => Auth::user()?->role !== 'ctv')
                            ->schema([
                                TextInput::make('high_school_name')
                                    ->label('Tên trường THPT')
                                    ->maxLength(255)
                                    ->columnSpanFull(),
                                TextInput::make('high_school_code')
                                    ->label('Mã trường')
                                    ->maxLength(50),
                                TextInput::make('high_school_province')
                                    ->label('Tên tỉnh/TP')
                                    ->maxLength(255),
                                TextInput::make('high_school_province_code')
                                    ->label('Mã tỉnh')
                                    ->maxLength(50),
                                TextInput::make('high_school_district')
                                    ->label('Tên quận/huyện')
                                    ->maxLength(255),
                                TextInput::make('high_school_district_code')
                                    ->label('Mã quận/huyện')
                                    ->maxLength(50),
                                TextInput::make('priority_area')
                                    ->label('Khu vực ưu tiên')
                                    ->maxLength(50),
                                \Filament\Forms\Components\TextInput::make('high_school_graduation_year')
                                    ->label('Năm tốt nghiệp THPT')
                                    ->numeric()
                                    ->minValue(1900)
                                    ->maxValue(2100),
                                Select::make('high_school_academic_performance')
                                    ->label('Học lực cả năm')
                                    ->options([
                                        'Giỏi' => 'Giỏi',
                                        'Khá' => 'Khá',
                                        'Trung bình' => 'Trung bình',
                                        'Yếu' => 'Yếu',
                                    ]),
                                Select::make('high_school_conduct')
                                    ->label('Hạnh kiểm')
                                    ->options([
                                        'Tốt' => 'Tốt',
                                        'Khá' => 'Khá',
                                        'Trung bình' => 'Trung bình',
                                        'Yếu' => 'Yếu',
                                    ]),
                            ])
                            ->columns(3),

                        // Tab 4: Thông tin văn bằng Cao đẳng
                        Tabs\Tab::make('Thông tin văn bằng Cao đẳng')
                            ->icon('heroicon-o-document-text')
                            ->visible(fn() => Auth::user()?->role !== 'ctv')
                            ->schema([
                                TextInput::make('college_graduation_school')
                                    ->label('Trường tốt nghiệp CĐ')
                                    ->maxLength(255)
                                    ->columnSpanFull(),
                                TextInput::make('college_graduation_major')
                                    ->label('Ngành tốt nghiệp CĐ')
                                    ->maxLength(255)
                                    ->columnSpanFull(),
                                Select::make('college_graduation_grade')
                                    ->label('Xếp loại')
                                    ->options([
                                        'Xuất sắc' => 'Xuất sắc',
                                        'Giỏi' => 'Giỏi',
                                        'Khá' => 'Khá',
                                        'Trung bình' => 'Trung bình',
                                    ]),
                                Select::make('college_training_type')
                                    ->label('Hệ đào tạo tốt nghiệp')
                                    ->options([
                                        'Chính quy' => 'Chính quy',
                                        'Vừa học vừa làm' => 'Vừa học vừa làm',
                                        'Từ xa' => 'Từ xa',
                                        'Khác' => 'Khác',
                                    ]),
                                \Filament\Forms\Components\TextInput::make('college_graduation_year')
                                    ->label('Năm tốt nghiệp')
                                    ->numeric()
                                    ->minValue(1900)
                                    ->maxValue(2100),
                                TextInput::make('college_diploma_number')
                                    ->label('Số hiệu bằng TN CĐ')
                                    ->maxLength(255),
                                TextInput::make('college_diploma_book_number')
                                    ->label('Số vào sổ cấp bằng TN CĐ')
                                    ->maxLength(255),
                                \Filament\Forms\Components\DatePicker::make('college_diploma_issue_date')
                                    ->label('Ngày ký bằng TN CĐ')
                                    ->displayFormat('d/m/Y'),
                                TextInput::make('college_diploma_signer')
                                    ->label('Người ký bằng TN CĐ')
                                    ->maxLength(255)
                                    ->columnSpanFull(),
                            ])
                            ->columns(3),

                        // Tab 5: Thông tin văn bằng Trung cấp
                        Tabs\Tab::make('Thông tin văn bằng Trung cấp')
                            ->icon('heroicon-o-document')
                            ->visible(fn() => Auth::user()?->role !== 'ctv')
                            ->schema([
                                TextInput::make('intermediate_graduation_school')
                                    ->label('Trường tốt nghiệp TC')
                                    ->maxLength(255)
                                    ->columnSpanFull(),
                                TextInput::make('intermediate_graduation_major')
                                    ->label('Ngành tốt nghiệp TC')
                                    ->maxLength(255)
                                    ->columnSpanFull(),
                                Select::make('intermediate_graduation_grade')
                                    ->label('Xếp loại')
                                    ->options([
                                        'Xuất sắc' => 'Xuất sắc',
                                        'Giỏi' => 'Giỏi',
                                        'Khá' => 'Khá',
                                        'Trung bình' => 'Trung bình',
                                    ]),
                                Select::make('intermediate_training_type')
                                    ->label('Hệ đào tạo tốt nghiệp')
                                    ->options([
                                        'Chính quy' => 'Chính quy',
                                        'Vừa học vừa làm' => 'Vừa học vừa làm',
                                        'Từ xa' => 'Từ xa',
                                        'Khác' => 'Khác',
                                    ]),
                                \Filament\Forms\Components\TextInput::make('intermediate_graduation_year')
                                    ->label('Năm tốt nghiệp')
                                    ->numeric()
                                    ->minValue(1900)
                                    ->maxValue(2100),
                                TextInput::make('intermediate_diploma_number')
                                    ->label('Số hiệu bằng TN TC')
                                    ->maxLength(255),
                                TextInput::make('intermediate_diploma_book_number')
                                    ->label('Số vào sổ cấp bằng TN TC')
                                    ->maxLength(255),
                                \Filament\Forms\Components\DatePicker::make('intermediate_diploma_issue_date')
                                    ->label('Ngày ký bằng TN TC')
                                    ->displayFormat('d/m/Y'),
                                TextInput::make('intermediate_diploma_signer')
                                    ->label('Người ký bằng TN TC')
                                    ->maxLength(255)
                                    ->columnSpanFull(),
                            ])
                            ->columns(3),

                        // Tab 6: Giấy tờ (link file + loại BS/BG)
                        Tabs\Tab::make('Giấy tờ')
                            ->icon('heroicon-o-paper-clip')
                            ->visible(fn() => Auth::user()?->role !== 'ctv')
                            ->schema([
                                TextInput::make('document_identity_card_front')
                                    ->label('File CCCD (mặt trước)')
                                    ->helperText('Nhập link/đường dẫn file CCCD mặt trước')
                                    ->maxLength(255),
                                TextInput::make('document_identity_card_back')
                                    ->label('File CCCD (mặt sau)')
                                    ->helperText('Nhập link/đường dẫn file CCCD mặt sau')
                                    ->maxLength(255),
                                TextInput::make('document_college_diploma')
                                    ->label('Bằng tốt nghiệp CĐ (file/link)')
                                    ->maxLength(255),
                                Select::make('college_diploma_copy_type')
                                    ->label('Bằng tốt nghiệp CĐ (BS/BG)')
                                    ->options([
                                        'BS' => 'Bản sao',
                                        'BG' => 'Bản gốc',
                                    ])
                                    ->nullable(),
                                TextInput::make('document_college_transcript')
                                    ->label('Bảng điểm CĐ (file/link)')
                                    ->maxLength(255),
                                Select::make('college_transcript_copy_type')
                                    ->label('Bảng điểm CĐ (BS/BG)')
                                    ->options([
                                        'BS' => 'Bản sao',
                                        'BG' => 'Bản gốc',
                                    ])
                                    ->nullable(),
                                TextInput::make('document_high_school_diploma')
                                    ->label('Bằng tốt nghiệp THPT (file/link)')
                                    ->maxLength(255),
                                Select::make('high_school_diploma_copy_type')
                                    ->label('Bằng tốt nghiệp THPT (BS/BG)')
                                    ->options([
                                        'BS' => 'Bản sao',
                                        'BG' => 'Bản gốc',
                                    ])
                                    ->nullable(),
                                TextInput::make('document_intermediate_diploma')
                                    ->label('Bằng Trung cấp (file/link)')
                                    ->maxLength(255),
                                TextInput::make('document_intermediate_transcript')
                                    ->label('Bảng điểm Trung cấp (file/link)')
                                    ->maxLength(255),
                                TextInput::make('document_birth_certificate')
                                    ->label('Giấy khai sinh (file/link)')
                                    ->maxLength(255),
                                Select::make('birth_certificate_copy_type')
                                    ->label('Giấy khai sinh (BS/BG)')
                                    ->options([
                                        'BS' => 'Bản sao',
                                        'BG' => 'Bản gốc',
                                    ])
                                    ->nullable(),
                                TextInput::make('document_photo')
                                    ->label('Ảnh thẻ (file/link)')
                                    ->maxLength(255),
                                TextInput::make('document_health_certificate')
                                    ->label('Giấy khám sức khỏe (file/link)')
                                    ->maxLength(255),
                                Select::make('health_certificate_copy_type')
                                    ->label('Giấy khám sức khỏe (BS/BG)')
                                    ->options([
                                        'BS' => 'Bản sao',
                                        'BG' => 'Bản gốc',
                                    ])
                                    ->nullable(),
                            ])
                            ->columns(2),

                    ]),

                // Right section: 4 columns - checklist hồ sơ nhập học
                Tabs::make('StudentChecklist')
                    ->columnSpan(3)
                    ->visible(fn() => Auth::user()?->role !== 'ctv')
                    ->tabs([
                        Tabs\Tab::make('Checklist hồ sơ nhập học')
                            ->icon('heroicon-o-check-circle')
                            ->visible(fn() => Auth::user()?->role !== 'ctv')
                            ->schema([
                                \Filament\Forms\Components\CheckboxList::make('document_checklist')
                                    ->label('Danh sách giấy tờ')
                                    ->options([
                                        'phieu_tuyen_sinh' => '📄 Phiếu tuyển sinh hệ CQ hoặc VHVL',
                                        'phieu_xet_tuyen' => '📄 Phiếu xét tuyển hệ đào tạo từ xa (Xã phường hoặc cơ quan đang làm việc đóng dấu)',
                                        'bang_cao_dang' => '📄 01 Bản sao công chứng hợp lệ bằng tốt nghiệp Cao đẳng',
                                        'bang_thpt' => '📄 01 Bản sao công chứng bằng tốt nghiệp THPT',
                                        'bang_diem' => '📄 01 Bản công chứng giấy chứng nhận kết quả học tập (Bảng điểm)',
                                        'giay_khai_sinh' => '📄 01 Bản sao công chứng hợp lệ giấy khai sinh',
                                        'cccd' => '📄 01 Bản sao công chứng căn cước công dân',
                                        'giay_kham_suc_khoe' => '📷 Giấy khám đủ sức khỏe (cấp bởi Bệnh viện hoặc TTYT công lập cấp quận/huyện trở lên) - Giấy A3, bản gốc',
                                        'anh_4x6' => '📷 04 ảnh chân dung 4x6 cm (Chụp trong vòng 6 tháng trở lại)',
                                    ])
                                    ->columns(1)
                                    ->gridDirection('row')
                                    ->bulkToggleable()
                                    ->helperText('Đánh dấu các giấy tờ mà học viên đã nộp đầy đủ')
                                    ->columnSpanFull(),
                            ]),
                        Tabs\Tab::make('Lịch sử chỉnh sửa')
                            ->icon('heroicon-o-clock')
                            ->visible(fn() => Auth::user()?->role !== 'ctv')
                            ->columns(12)
                            ->schema([
                                \Filament\Forms\Components\DatePicker::make('history_date_from')
                                    ->label('Từ ngày')
                                    ->displayFormat('d/m/Y')
                                    ->native(false)
                                    ->placeholder('Chọn ngày bắt đầu')
                                    ->live()
                                    ->afterStateUpdated(fn() => null)
                                    ->columnSpan(6),
                                \Filament\Forms\Components\DatePicker::make('history_date_to')
                                    ->label('Đến ngày')
                                    ->displayFormat('d/m/Y')
                                    ->native(false)
                                    ->placeholder('Chọn ngày kết thúc')
                                    ->live()
                                    ->afterStateUpdated(fn() => null)
                                    ->columnSpan(6),
                                \Filament\Forms\Components\Placeholder::make('update_history')
                                    ->label('Lịch sử chỉnh sửa')
                                    ->content(function (?Student $record, $get) {
                                        if (!SchemaFacade::hasTable('student_update_logs')) {
                                            return 'Chưa có bảng log (chạy migrate để kích hoạt).';
                                        }

                                        if (!$record) {
                                            return 'Chưa có dữ liệu.';
                                        }

                                        $query = StudentUpdateLog::where('student_id', $record->id);

                                        // Filter theo date range
                                        $dateFrom = $get('history_date_from');
                                        $dateTo = $get('history_date_to');

                                        if ($dateFrom) {
                                            $query->whereDate('created_at', '>=', $dateFrom);
                                        }

                                        if ($dateTo) {
                                            $query->whereDate('created_at', '<=', $dateTo);
                                        }

                                        // Pagination
                                        $currentPage = (int) (request()->query('history_page', 1));
                                        $perPage = 10;
                                        $total = $query->count();
                                        $totalPages = max(1, (int) ceil($total / $perPage));
                                        $currentPage = max(1, min($currentPage, $totalPages)); // Ensure valid page

                                        $logs = $query->latest()
                                            ->skip(($currentPage - 1) * $perPage)
                                            ->take($perPage)
                                            ->get();

                                        if ($logs->isEmpty()) {
                                            return 'Chưa có lịch sử chỉnh sửa.';
                                        }

                                        $fieldLabels = [
                                            'address' => 'Địa chỉ',
                                            'target_university' => 'Trường đăng ký liên thông',
                                            'document_checklist' => 'Checklist giấy tờ',
                                            'fee' => 'Lệ phí',
                                            'application_status' => 'Tình trạng hồ sơ',
                                            'intake_id' => 'Đợt đăng ký',
                                            'intake_month' => 'Đợt đăng ký (tháng)',
                                            'full_name' => 'Họ và tên',
                                            'phone' => 'Số điện thoại',
                                            'email' => 'Email',
                                            'major' => 'Ngành đăng ký',
                                            'program_type' => 'Hệ đào tạo',
                                            'status' => 'Trạng thái',
                                            'source' => 'Hình thức tuyển sinh',
                                            'notes' => 'Ghi chú',
                                            'payment_status' => 'Trạng thái thanh toán',
                                        ];

                                        $formatValue = function ($value, $field = '') {
                                            // Xử lý document_checklist - có thể là JSON string hoặc array
                                            if ($field === 'document_checklist') {
                                                $labels = [
                                                    'phieu_tuyen_sinh' => 'Phiếu tuyển sinh',
                                                    'phieu_xet_tuyen' => 'Phiếu xét tuyển',
                                                    'bang_cao_dang' => 'Bằng Cao đẳng',
                                                    'bang_thpt' => 'Bằng THPT',
                                                    'bang_diem' => 'Bảng điểm',
                                                    'giay_khai_sinh' => 'Giấy khai sinh',
                                                    'cccd' => 'CCCD',
                                                    'giay_kham_suc_khoe' => 'Giấy khám sức khỏe',
                                                    'anh_4x6' => 'Ảnh 4x6',
                                                ];

                                                // Nếu là JSON string, decode trước
                                                if (is_string($value)) {
                                                    $decoded = json_decode($value, true);
                                                    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                                                        $value = $decoded;
                                                    }
                                                }

                                                if (is_array($value)) {
                                                    return implode(', ', array_map(
                                                        fn($item) => $labels[$item] ?? $item,
                                                        $value
                                                    ));
                                                }

                                                // Nếu không phải array, trả về string đã clean
                                                $str = (string) $value;
                                                // Loại bỏ các ký tự JSON nếu có
                                                $str = preg_replace('/[\[\]"]/', '', $str);
                                                $items = array_filter(array_map('trim', explode(',', $str)));
                                                if (!empty($items)) {
                                                    return implode(', ', array_map(
                                                        fn($item) => $labels[$item] ?? $item,
                                                        $items
                                                    ));
                                                }
                                                return $str;
                                            }

                                            if (is_array($value)) {
                                                return implode(', ', array_map(
                                                    fn($item) => is_array($item)
                                                        ? json_encode($item, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                                                        : (string) $item,
                                                    $value
                                                ));
                                            }

                                            if (is_bool($value)) {
                                                return $value ? 'Có' : 'Không';
                                            }

                                            $str = (string) ($value ?? '');

                                            // Format application_status
                                            if ($field === 'application_status') {
                                                $statusLabels = [
                                                    'draft' => 'Đang nhập',
                                                    'pending_documents' => 'Thiếu giấy tờ',
                                                    'submitted' => 'Đã nộp hồ sơ',
                                                    'verified' => 'Đã xác minh',
                                                    'eligible' => 'Đủ điều kiện',
                                                    'ineligible' => 'Không đủ điều kiện',
                                                ];
                                                return $statusLabels[$str] ?? $str;
                                            }

                                            // Format payment_status
                                            if ($field === 'payment_status') {
                                                $paymentStatusLabels = [
                                                    Payment::STATUS_NOT_PAID => 'Chưa nộp tiền',
                                                    Payment::STATUS_SUBMITTED => 'Chờ xác minh',
                                                    Payment::STATUS_VERIFIED => 'Đã xác nhận',
                                                    Payment::STATUS_REVERTED => 'Đã hoàn trả',
                                                ];
                                                return $paymentStatusLabels[$str] ?? $str;
                                            }

                                            // Format program_type
                                            if ($field === 'program_type') {
                                                return $str === 'REGULAR' ? 'Chính quy' : ($str === 'PART_TIME' ? 'Vừa học vừa làm' : $str);
                                            }

                                            // Format intake_id: hiển thị tên đợt tuyển
                                            if ($field === 'intake_id' && !empty($value)) {
                                                $intake = Intake::find($value);
                                                return $intake ? $intake->name : (string) $value;
                                            }

                                            return $str;
                                        };

                                        $entries = $logs->map(function ($log) use ($fieldLabels, $formatValue) {
                                            return [
                                                'time' => $log->created_at?->format('d/m/Y H:i') ?? '',
                                                'user' => $log->user?->name ?? 'Hệ thống',
                                                'changes' => collect($log->changes ?? [])->map(function ($change) use ($fieldLabels, $formatValue) {
                                                    $field = $change['field'] ?? '';
                                                    return [
                                                        'label' => $fieldLabels[$field] ?? ucfirst(str_replace('_', ' ', $field)),
                                                        'from' => $formatValue($change['from'] ?? '', $field),
                                                        'to' => $formatValue($change['to'] ?? '', $field),
                                                    ];
                                                })->values()->all(),
                                            ];
                                        })->values()->all();

                                        return new HtmlString(
                                            view('components.student-update-history', [
                                                'entries' => $entries,
                                                'currentPage' => $currentPage,
                                                'totalPages' => $totalPages,
                                                'total' => $total,
                                                'recordId' => $record->id,
                                            ])->render()
                                        );
                                    })
                                    ->columnSpanFull(),
                            ]),
                    ]),
            ]);
    }
}
