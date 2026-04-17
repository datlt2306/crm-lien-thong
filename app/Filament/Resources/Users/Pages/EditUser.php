<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Gate;
use App\Services\CollaboratorValidationService;

class EditUser extends EditRecord {
    protected static string $resource = UserResource::class;

    public function mount(int | string $record): void {
        parent::mount($record);

        // Kiểm tra quyền update
        if (!Gate::allows('update', $this->record)) {
            abort(403, 'Bạn không có quyền chỉnh sửa người dùng này.');
        }
    }

    protected function getHeaderActions(): array {
        return [
            ViewAction::make()
                ->label('Xem chi tiết'),
            DeleteAction::make()
                ->label('Xóa người dùng')
                ->modalHeading('Xóa người dùng')
                ->modalDescription('Bạn có chắc chắn muốn xóa người dùng này? Hành động này không thể hoàn tác.')
                ->modalSubmitActionLabel('Xóa')
                ->modalCancelActionLabel('Hủy'),
        ];
    }

    public function getTitle(): string {
        return 'Chỉnh sửa người dùng';
    }

    public function getBreadcrumb(): string {
        return 'Chỉnh sửa người dùng';
    }

    protected function getFormActions(): array {
        return [
            $this->getSaveFormAction()
                ->label('Lưu thay đổi'),
            $this->getCancelFormAction()
                ->label('Hủy'),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array {
        // Nếu password trống, không cập nhật password
        if (empty($data['password'])) {
            unset($data['password']);
        } else {
            // Hash password nếu có nhập
            $data['password'] = \Illuminate\Support\Facades\Hash::make($data['password']);
        }


        // Loại bỏ password_confirmation khỏi data
        unset($data['password_confirmation']);


        // Xử lý role - cập nhật role trong database
        if (isset($data['role'])) {
            $user = $this->record;
            $user->role = $data['role'];
            $user->save();
        }

        return $data;
    }

    protected function afterSave(): void {
        $user = $this->record;

        $collaborator = \App\Models\Collaborator::where('email', $user->email)->first();

        if ($collaborator) {
            $updatePayload = [
                'full_name' => $user->name,
            ];

            CollaboratorValidationService::validateForUpdate($user->email, $user->phone, $collaborator->id);

            if (!empty($user->phone)) {
                $updatePayload['phone'] = $user->phone;
            }
            if (!empty($user->email) && $user->email !== $collaborator->email) {
                $updatePayload['email'] = $user->email;
            }
            $collaborator->update($updatePayload);
        } elseif ($user->role === 'ctv') {
            CollaboratorValidationService::validateForCreation($user->email, $user->phone);

            \App\Models\Collaborator::create([
                'full_name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'status' => 'active',
            ]);
        }
    }
}
