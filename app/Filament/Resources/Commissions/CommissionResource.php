<?php

namespace App\Filament\Resources\Commissions;

use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Gate;
use Filament\Tables\Table;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Models\Commission;
use App\Models\CommissionItem;
use App\Models\Organization;
use App\Models\Collaborator;
use App\Models\Student;
use App\Filament\Resources\Commissions\Pages\ListCommissions;
use Illuminate\Support\Facades\Auth;

class CommissionResource extends Resource {
    protected static ?string $model = Commission::class;
    protected static string|\UnitEnum|null $navigationGroup = 'Tài chính';
    protected static ?string $navigationLabel = 'Hoa hồng & Đối soát';
    protected static ?int $navigationSort = 2;
    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedCurrencyDollar;

    public static function shouldRegisterNavigation(): bool {
        $user = Auth::user();
        // Ẩn menu "Hoa hồng & Đối soát" cho cán bộ hồ sơ
        if ($user && $user->role === 'document') {
            return false;
        }
        return true; // Cho phép các role khác thấy menu, quyền truy cập sẽ được kiểm tra ở page level
    }

    public static function form(Schema $schema): Schema {
        return $schema;
    }

    public static function table(Table $table): Table {
        $user = Auth::user();
        $isCtv = $user->role === 'ctv';

        // Kiểm tra xem CTV có phải là người trực tiếp giới thiệu sinh viên không
        $isDirectRef = false;
        // Kiểm tra xem có phải là organization_owner không
        $isOwner = $user->role === 'organization_owner';
        if ($isCtv) {
            $collaborator = Collaborator::where('email', $user->email)->first();
            // CTV trực tiếp giới thiệu sinh viên sẽ có commission với role = 'direct'
            // và recipient_collaborator_id = collaborator.id
            $isDirectRef = $collaborator && CommissionItem::where('recipient_collaborator_id', $collaborator->id)
                ->where('role', 'direct')
                ->exists();
        }

        return $table
            ->query(CommissionItem::query())
            // Hiển thị filter như segmented control phía trên bảng
            ->filtersLayout(\Filament\Tables\Enums\FiltersLayout::AboveContent)
            ->columns([
                \Filament\Tables\Columns\TextColumn::make('commission.student.full_name')
                    ->label('Họ và tên')
                    ->searchable()
                    ->sortable()
                    ->description(function (CommissionItem $record) {
                        $student = $record->commission->student;
                        if (!$student) return '';

                        $lines = [];
                        if ($student->phone) {
                            $lines[] = '📞 ' . e($student->phone);
                        }
                        if ($student->email) {
                            $lines[] = '✉️ ' . e($student->email);
                        }
                        return implode(' | ', $lines);
                    }),

                \Filament\Tables\Columns\TextColumn::make('commission.student.dob')
                    ->label('Ngày sinh')
                    ->date('d/m/Y')
                    ->sortable(),

                \Filament\Tables\Columns\TextColumn::make('commission.student.major')
                    ->label('Ngành học')
                    ->searchable(),

                \Filament\Tables\Columns\TextColumn::make('commission.student.address')
                    ->label('Địa chỉ')
                    ->limit(50)
                    ->searchable(),

                \Filament\Tables\Columns\TextColumn::make('commission.student.intake.name')
                    ->label('Đợt tuyển')
                    ->formatStateUsing(fn($state, $record) => $state ?: ($record->commission?->student?->intake_month ? "Tháng {$record->commission->student->intake_month}" : '—'))
                    ->sortable(),

                \Filament\Tables\Columns\TextColumn::make('commission.student.program_type')
                    ->label('Hệ tuyển sinh')
                    ->formatStateUsing(fn($state) => match ($state) {
                        'REGULAR' => '🎓 Chính quy',
                        'PART_TIME' => '⏰ Vừa học vừa làm',
                        default => '—'
                    })
                    ->badge()
                    ->color(fn($state) => match ($state) {
                        'REGULAR' => 'success',
                        'PART_TIME' => 'info',
                        default => 'gray'
                    }),

                // Trạng thái sinh viên: hiển thị "Đã nhập học" nếu đã ENROLLED
                \Filament\Tables\Columns\BadgeColumn::make('student_status')
                    ->label('Trạng thái SV')
                    ->state(function ($record) {
                        /** @var CommissionItem|null $record */
                        if (!$record) return null;
                        $student = $record->commission?->student;
                        if (!$student) return null;
                        return $student->status === \App\Models\Student::STATUS_ENROLLED ? 'Đã nhập học' : null;
                    })
                    ->color(function ($state) {
                        return $state ? 'success' : 'gray';
                    }),

                \Filament\Tables\Columns\TextColumn::make('recipient.full_name')
                    ->label('CTV nhận hoa hồng')
                    ->searchable()
                    ->sortable()
                    ->visible(fn(): bool => !$isCtv),

                \Filament\Tables\Columns\TextColumn::make('amount')
                    ->label('Số tiền hoa hồng')
                    ->money('VND')
                    ->sortable(),

                // Cột mã số phiếu thu
                \Filament\Tables\Columns\TextColumn::make('commission.payment.receipt_number')
                    ->label('Số phiếu thu')
                    ->sortable()
                    ->formatStateUsing(function (?CommissionItem $record) {
                        if (!$record) return '—';
                        $payment = $record->commission->payment;
                        if (!$payment || !$payment->receipt_number) {
                            return '—';
                        }
                        return $payment->receipt_number;
                    })
                    ->visible(fn(): bool => in_array(Auth::user()->role, ['accountant', 'organization_owner', 'super_admin']) || (Auth::user()->roles && Auth::user()->roles->contains('name', 'accountant'))),

                \Filament\Tables\Columns\BadgeColumn::make('status')
                    ->label('Trạng thái')
                    ->color(function (string $state): string {
                        return match ($state) {
                            CommissionItem::STATUS_PENDING => 'gray',
                            CommissionItem::STATUS_PAYABLE => 'warning',
                            CommissionItem::STATUS_PAID => 'success',
                            CommissionItem::STATUS_CANCELLED => 'danger',
                            CommissionItem::STATUS_PAYMENT_CONFIRMED => 'info',
                            CommissionItem::STATUS_RECEIVED_CONFIRMED => 'success',
                            default => 'gray',
                        };
                    })
                    ->formatStateUsing(function (string $state, CommissionItem $record) use ($user) {
                        if ($user->role === 'ctv' && $state === CommissionItem::STATUS_PAYABLE) {
                            return 'Chưa nhận được hoa hồng';
                        }
                        if ($user->role === 'ctv' && $state === CommissionItem::STATUS_PAYMENT_CONFIRMED) {
                            return 'Chờ xác nhận nhận tiền';
                        }
                        if ($user->role === 'ctv' && $state === CommissionItem::STATUS_RECEIVED_CONFIRMED) {
                            return 'Đã nhận tiền thành công';
                        }
                        if ($user->role === 'organization_owner' && $state === CommissionItem::STATUS_RECEIVED_CONFIRMED) {
                            return 'Đã chuyển thành công';
                        }
                        return CommissionItem::getStatusOptions()[$state] ?? $state;
                    }),

                \Filament\Tables\Columns\TextColumn::make('payment_confirmed_at')
                    ->label('Đã thanh toán lúc')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->visible(fn(): bool => !$isCtv), // Chỉ hiển thị cho chủ đơn vị và super admin

            ])
            ->filters([
                \Filament\Tables\Filters\SelectFilter::make('status')
                    ->label('Trạng thái hoa hồng')
                    ->options(CommissionItem::getStatusOptions()),

                // Đã loại bỏ filter Cấp 1/Cấp 2 - hệ thống chỉ còn 1 cấp

                // Filter Trạng thái SV: Tất cả / Đã nhập học / Chưa nhập học
                \Filament\Tables\Filters\SelectFilter::make('student_status')
                    ->label('Trạng thái SV')
                    ->options([
                        'ALL' => 'Tất cả',
                        'ENROLLED' => 'Đã nhập học',
                        'NOT_ENROLLED' => 'Chưa nhập học',
                    ])
                    ->default('ALL')
                    ->native(false)
                    ->query(function ($query, array $data) {
                        $value = strtoupper((string) ($data['value'] ?? 'ALL'));
                        if ($value === 'ENROLLED') {
                            $query->whereHas('commission.student', function ($q) {
                                $q->where('status', \App\Models\Student::STATUS_ENROLLED);
                            });
                        } elseif ($value === 'NOT_ENROLLED') {
                            $query->whereHas('commission.student', function ($q) {
                                $q->where('status', '!=', \App\Models\Student::STATUS_ENROLLED);
                            });
                        }
                    }),

                // Bỏ filter "Điều kiện kích hoạt"
            ])
            ->actions([
                Action::make('mark_payable')
                    ->label('Đánh dấu có thể thanh toán')
                    ->icon('heroicon-o-currency-dollar')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Đánh dấu có thể thanh toán')
                    ->modalDescription('Đánh dấu commission này đã đến hạn chi, CTV có thể nhận.')
                    ->modalSubmitActionLabel('Xác nhận')
                    ->modalCancelActionLabel('Hủy')
                    ->visible(function (CommissionItem $record) use ($user): bool {
                        return $record->status === CommissionItem::STATUS_PENDING && $user->role === 'organization_owner';
                    })
                    ->action(function (CommissionItem $record) {
                        $record->markAsPayable();

                        \Filament\Notifications\Notification::make()
                            ->title('Đã đánh dấu có thể thanh toán')
                            ->body('CTV có thể nhận hoa hồng này.')
                            ->success()
                            ->send();
                    }),

                Action::make('confirm_payment')
                    ->label('Xác nhận')
                    ->icon('heroicon-o-currency-dollar')
                    ->color('success')
                    ->form([
                        \Filament\Forms\Components\FileUpload::make('bill')
                            ->label('Bill thanh toán')
                            ->required()
                            ->disk('local')
                            ->directory('commission-bills')
                            ->acceptedFileTypes(['image/*', 'application/pdf'])
                            ->maxSize(5120), // 5MB
                    ])
                    ->modalHeading('Xác nhận đã thanh toán')
                    ->modalDescription('Xác nhận đã thanh toán hoa hồng cho CTV và upload bill.')
                    ->modalSubmitActionLabel('Xác nhận thanh toán')
                    ->modalCancelActionLabel('Hủy')
                    ->visible(function (CommissionItem $record) use ($user): bool {
                        // Chủ đơn vị xác nhận khi item là DIRECT và đang PAYABLE/PENDING
                        return $user->role === 'organization_owner'
                            && $record->role === 'direct'
                            && in_array($record->status, [CommissionItem::STATUS_PAYABLE, CommissionItem::STATUS_PENDING]);
                    })
                    ->action(function (CommissionItem $record, array $data) {
                        $record->markAsPaymentConfirmed($data['bill'], \Illuminate\Support\Facades\Auth::user()->id);

                        \Filament\Notifications\Notification::make()
                            ->title('Đã xác nhận thanh toán')
                            ->body('Bill đã được upload và CTV sẽ được thông báo.')
                            ->success()
                            ->send();
                    }),

                Action::make('confirm_received')
                    ->label('Xác nhận đã nhận tiền')
                    ->icon('heroicon-o-hand-thumb-up')
                    ->color('info')
                    ->requiresConfirmation()
                    ->modalHeading('Xác nhận đã nhận tiền')
                    ->modalDescription('Xác nhận đã nhận được tiền hoa hồng từ chủ đơn vị.')
                    ->modalSubmitActionLabel('Xác nhận đã nhận')
                    ->modalCancelActionLabel('Hủy')
                    ->visible(function (CommissionItem $record) use ($user): bool {
                        if ($user->role !== 'ctv') return false;
                        // Chỉ hiện cho item DIRECT thuộc CTV hiện tại sau khi Chủ đơn vị xác nhận (PAYMENT_CONFIRMED)
                        $collab = \App\Models\Collaborator::where('email', $user->email)->first();
                        if (!$collab) return false;
                        return $record->status === CommissionItem::STATUS_PAYMENT_CONFIRMED
                            && $record->role === 'direct'
                            && $record->recipient_collaborator_id === $collab->id;
                    })
                    ->action(function (CommissionItem $record) {
                        $service = new \App\Services\CommissionService();
                        $service->confirmDirectReceived($record, \Illuminate\Support\Facades\Auth::user()->id);

                        \Filament\Notifications\Notification::make()
                            ->title('Đã xác nhận nhận tiền')
                            ->body('Hoa hồng đã được chuyển vào ví của bạn.')
                            ->success()
                            ->send();
                    }),

                // Đã loại bỏ action "Chuyển cho CTV cấp 2" - hệ thống chỉ còn 1 cấp

                // Action xem Bill từ CTV (SV upload) - Payment.bill_path
                Action::make('view_bill_student')
                    ->label('Xem Bill từ SV')
                    ->icon('heroicon-o-document-text')
                    ->color('info')
                    ->modalHeading('Bill thanh toán từ sinh viên (CTV upload)')
                    ->modalContent(function (CommissionItem $record) {
                        $payment = $record->commission->payment;
                        if (!$payment || !$payment->bill_path) {
                            return view('components.no-content', [
                                'message' => 'Chưa có bill thanh toán từ sinh viên.'
                            ]);
                        }

                        $fileUrl = route('files.bill.view', $payment->id);
                        return view('components.bill-viewer', [
                            'fileUrl' => $fileUrl,
                            'fileName' => basename($payment->bill_path),
                            'payment' => $payment
                        ]);
                    })
                    ->modalWidth('4xl')
                    ->visible(function (CommissionItem $record) use ($user): bool {
                        // Tất cả role đều được xem bill từ SV
                        $payment = $record->commission->payment;
                        return $payment && !empty($payment->bill_path);
                    }),

                // ActionGroup: Bill & Receipt
                ActionGroup::make([
                    Action::make('view_bill_transfer')
                        ->label('Minh chứng chuyển khoản')
                        ->icon('heroicon-o-document-arrow-up')
                        ->modalHeading('Minh chứng chuyển khoản từ CTV')
                        ->modalContent(function (CommissionItem $record) {
                            if (!$record->payment_bill_path) {
                                return view('components.no-content', [
                                    'message' => 'Chưa có bill chuyển tiền.'
                                ]);
                            }
                            return view('components.commission-bill-viewer', [
                                'commissionItem' => $record,
                            ]);
                        })
                        ->modalWidth('4xl')
                        ->visible(fn (CommissionItem $record): bool => !empty($record->payment_bill_path)),

                    Action::make('view_bill_receipt')
                        ->label('Xem phiếu thu')
                        ->icon('heroicon-o-receipt-percent')
                        ->modalHeading('Phiếu thu từ Helen')
                        ->modalContent(function (CommissionItem $record) {
                            $payment = $record->commission->payment;
                            if (!$payment || !$payment->receipt_path) {
                                return view('components.no-content', [
                                    'message' => 'Chưa có phiếu thu từ Helen.'
                                ]);
                            }

                            $fileUrl = route('files.receipt.view', $payment->id);
                            return view('components.bill-viewer', [
                                'fileUrl' => $fileUrl,
                                'fileName' => basename($payment->receipt_path),
                                'payment' => $payment
                            ]);
                        })
                        ->modalWidth('4xl')
                        ->visible(function (CommissionItem $record) use ($user): bool {
                            $payment = $record->commission->payment;
                            if (!$payment || !$payment->receipt_path) return false;
                            // Chỉ hiển thị cho organization_owner, super_admin, ctv (không hiển thị cho accountant)
                            return in_array($user->role, ['organization_owner', 'super_admin', 'ctv']);
                        }),

                    Action::make('manage_receipt')
                        ->label(function (CommissionItem $record): string {
                            $payment = $record->commission->payment;
                            return $payment && $payment->receipt_path ? 'Chỉnh sửa phiếu thu' : 'Upload phiếu thu';
                        })
                        ->icon(function (CommissionItem $record): string {
                            $payment = $record->commission->payment;
                            return $payment && $payment->receipt_path ? 'heroicon-o-pencil-square' : 'heroicon-o-document-plus';
                        })
                        ->color('primary')
                        ->form(function (CommissionItem $record): array {
                            $payment = $record->commission->payment;
                            $hasReceipt = $payment && $payment->receipt_path;

                            $form = [
                                \Filament\Forms\Components\TextInput::make('receipt_number')
                                    ->label('Mã số phiếu thu')
                                    ->maxLength(255)
                                    ->default($payment ? $payment->receipt_number : '')
                                    ->helperText('Nhập mã số phiếu thu từ Helen'),
                            ];

                            if ($hasReceipt) {
                                // Hiển thị phiếu thu hiện tại
                                $form[] = \Filament\Forms\Components\ViewField::make('current_receipt')
                                    ->label('Phiếu thu hiện tại')
                                    ->view('components.bill-viewer')
                                    ->viewData([
                                        'payment' => $payment,
                                        'fileUrl' => route('files.receipt.view', $payment->id),
                                        'fileName' => basename($payment->receipt_path),
                                    ]);
                            }

                            $form[] = \Filament\Forms\Components\FileUpload::make('receipt')
                                ->label($hasReceipt ? 'File phiếu thu mới (để trống nếu không thay đổi)' : 'File phiếu thu')
                                ->acceptedFileTypes(['image/*', 'application/pdf'])
                                ->maxSize(5120) // 5MB
                                ->disk('local')
                                ->directory('receipts')
                                ->required(!$hasReceipt)
                                ->helperText($hasReceipt ? 'Chọn file mới để thay thế phiếu thu hiện tại' : 'Upload phiếu thu từ Helen (JPG, PNG, PDF, tối đa 5MB)');

                            return $form;
                        })
                        ->modalHeading(function (CommissionItem $record): string {
                            $payment = $record->commission->payment;
                            return $payment && $payment->receipt_path ? 'Chỉnh sửa phiếu thu' : 'Upload phiếu thu';
                        })
                        ->modalDescription(function (CommissionItem $record): string {
                            $payment = $record->commission->payment;
                            return $payment && $payment->receipt_path
                                ? 'Chỉnh sửa thông tin phiếu thu đã upload.'
                                : 'Upload phiếu thu sau khi xác nhận đã thanh toán hoa hồng cho CTV.';
                        })
                        ->modalSubmitActionLabel(function (CommissionItem $record): string {
                            $payment = $record->commission->payment;
                            return $payment && $payment->receipt_path ? 'Cập nhật' : 'Upload';
                        })
                        ->modalCancelActionLabel('Hủy')
                        ->visible(function (CommissionItem $record) use ($user): bool {
                            // Chỉ hiển thị cho accountant
                            if ($user->role !== 'accountant' && !($user->roles && $user->roles->contains('name', 'accountant'))) {
                                return false;
                            }

                            // Chỉ hiển thị cho commission ở trạng thái PAYABLE hoặc PAYMENT_CONFIRMED hoặc RECEIVED_CONFIRMED
                            return in_array($record->status, [
                                CommissionItem::STATUS_PAYABLE,
                                CommissionItem::STATUS_PAYMENT_CONFIRMED,
                                CommissionItem::STATUS_RECEIVED_CONFIRMED
                            ]) && $record->role === 'direct';
                        })
                        ->action(function (array $data, CommissionItem $record) {
                            // Cập nhật receipt vào payment
                            $payment = $record->commission->payment;
                            if ($payment) {
                                $updateData = [
                                    'receipt_uploaded_by' => Auth::id(),
                                    'receipt_uploaded_at' => now(),
                                ];

                                // Cập nhật receipt_number
                                if (isset($data['receipt_number'])) {
                                    $updateData['receipt_number'] = $data['receipt_number'];
                                }

                                // Cập nhật file nếu có file mới
                                if (!empty($data['receipt'])) {
                                    $updateData['receipt_path'] = $data['receipt'];
                                }

                                $payment->update($updateData);

                                $action = $payment->receipt_path ? 'cập nhật' : 'upload';
                                \Filament\Notifications\Notification::make()
                                    ->title("Đã {$action} phiếu thu thành công")
                                    ->body('Thông tin phiếu thu đã được lưu vào hệ thống.')
                                    ->success()
                                    ->send();
                            }
                        }),
                ])
                    ->label('Files')
                    ->icon('heroicon-o-document-text')
                    ->color('gray')
                    ->button()
                    ->size('sm'),

                Action::make('mark_cancelled')
                    ->label('Đánh dấu đã huỷ')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Đánh dấu đã huỷ')
                    ->modalDescription('Đánh dấu huỷ hoa hồng này (VD: SV không nhập học).')
                    ->modalSubmitActionLabel('Xác nhận')
                    ->modalCancelActionLabel('Hủy')
                    ->visible(function (CommissionItem $record) use ($user): bool {
                        return in_array($record->status, [CommissionItem::STATUS_PENDING, CommissionItem::STATUS_PAYABLE]) && $user->role === 'organization_owner';
                    })
                    ->action(function (CommissionItem $record) {
                        $record->markAsCancelled();

                        \Filament\Notifications\Notification::make()
                            ->title('Đã đánh dấu huỷ')
                            ->body('Hoa hồng đã được huỷ.')
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    \Filament\Actions\BulkAction::make('bulk_confirm_payment')
                        ->label('Xác nhận thanh toán hàng loạt')
                        ->icon('heroicon-o-currency-dollar')
                        ->color('success')
                        ->form([
                            \Filament\Forms\Components\FileUpload::make('bill')
                                ->label('Bill thanh toán chung')
                                ->required()
                                ->disk('local')
                                ->directory('commission-bills')
                                ->acceptedFileTypes(['image/*', 'application/pdf'])
                                ->maxSize(5120) // 5MB
                                ->helperText('Upload một bill thanh toán chung cho tất cả các hoa hồng đã chọn'),
                            \Filament\Forms\Components\Textarea::make('note')
                                ->label('Ghi chú')
                                ->rows(3)
                                ->placeholder('Ghi chú về việc thanh toán hàng loạt này...')
                                ->helperText('Ghi chú tùy chọn cho việc thanh toán hàng loạt'),
                        ])
                        ->modalHeading('Xác nhận thanh toán hàng loạt')
                        ->modalDescription('Xác nhận đã thanh toán hoa hồng cho tất cả các CTV đã chọn. Một bill chung sẽ được áp dụng cho tất cả.')
                        ->modalSubmitActionLabel('Xác nhận thanh toán tất cả')
                        ->modalCancelActionLabel('Hủy')
                        ->visible(fn() => Auth::user()->role === 'organization_owner')
                        ->action(function (array $data, $records) {
                            $userId = Auth::user()->id;
                            $billPath = $data['bill'];
                            $note = $data['note'] ?? null;

                            $successCount = 0;
                            $errorCount = 0;

                            foreach ($records as $record) {
                                try {
                                    // Chỉ xử lý các commission có thể thanh toán
                                    if (in_array($record->status, [CommissionItem::STATUS_PAYABLE, CommissionItem::STATUS_PENDING])) {
                                        $record->markAsPaymentConfirmed($billPath, $userId);
                                        $successCount++;
                                    }
                                } catch (\Exception $e) {
                                    $errorCount++;
                                }
                            }

                            // Hiển thị thông báo kết quả
                            if ($successCount > 0) {
                                \Filament\Notifications\Notification::make()
                                    ->title("Đã xác nhận thanh toán thành công {$successCount} hoa hồng")
                                    ->body($errorCount > 0 ? "Có {$errorCount} hoa hồng không thể xử lý." : "Tất cả hoa hồng đã được xác nhận.")
                                    ->success()
                                    ->send();
                            } else {
                                \Filament\Notifications\Notification::make()
                                    ->title('Không có hoa hồng nào được xác nhận')
                                    ->body('Vui lòng kiểm tra lại trạng thái của các hoa hồng đã chọn.')
                                    ->warning()
                                    ->send();
                            }
                        })
                        ->deselectRecordsAfterCompletion(),

                    DeleteBulkAction::make()
                        ->label('Xóa đã chọn')
                        ->modalHeading('Xóa hoa hồng đã chọn')
                        ->modalDescription('Bạn có chắc chắn muốn xóa các hoa hồng đã chọn? Hành động này không thể hoàn tác.')
                        ->modalSubmitActionLabel('Xóa')
                        ->modalCancelActionLabel('Hủy'),
                ]),
            ])
            ->headerActions([
                // Nút chuyển cho CTV cấp 2: hiển thị khi chọn một item downline ở trạng thái phù hợp
            ])
            ->modifyQueryUsing(function ($query) {
                $user = Auth::user();

                if ($user->role === 'super_admin') {
                    return;
                }

                if ($user->role === 'ctv') {
                    // CTV chỉ thấy commission của chính mình (role nào xem role đó)
                    $collaborator = Collaborator::where('email', $user->email)->first();
                    if ($collaborator) {
                        $query->where('recipient_collaborator_id', $collaborator->id);
                    } else {
                        $query->whereNull('id');
                    }
                }

                if ($user->role === 'organization_owner') {
                    // Chủ đơn vị chỉ thấy commission của tổ chức mình
                    $org = Organization::where('organization_owner_id', $user->id)->first();
                    if ($org) {
                        $query->whereHas('recipient', function ($q) use ($org) {
                            $q->where('organization_id', $org->id);
                        });
                        // Và chỉ hiển thị khoản hoa hồng CTV cấp 1 (direct)
                        $query->where('role', 'direct');
                    }
                }

                if ($user->role === 'accountant') {
                    // Kế toán có thể xem tất cả commissions để đối soát
                    return;
                }
            });
    }

    public static function getPages(): array {
        return [
            'index' => ListCommissions::route('/'),
        ];
    }

    public static function getNavigationBadge(): ?string {
        try {
            // Đếm theo Commission để tránh nhân đôi (DIRECT + DOWNLINE)
            return (string) Commission::count();
        } catch (\Throwable) {
            return null;
        }
    }

    public static function getNavigationBadgeTooltip(): ?string {
        return 'Tổng số bộ hoa hồng';
    }

}
