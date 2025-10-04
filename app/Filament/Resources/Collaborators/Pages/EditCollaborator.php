<?php

namespace App\Filament\Resources\Collaborators\Pages;

use App\Filament\Resources\Collaborators\CollaboratorResource;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Hash;

class EditCollaborator extends EditRecord {
    protected static string $resource = CollaboratorResource::class;

    protected function mutateFormDataBeforeFill(array $data): array {
        // Debug: Log data khi load form
        \Illuminate\Support\Facades\Log::info('EditCollaborator - Data before fill:', $data);

        // Đảm bảo ref_id được format đúng cho hiển thị
        if (!empty($data['ref_id'])) {
            $data['ref_id'] = request()->getSchemeAndHttpHost() . '/ref/' . $data['ref_id'];
        }

        // Xử lý status field - chuyển từ string sang boolean cho UI
        if (isset($data['status'])) {
            if (is_string($data['status'])) {
                $data['status'] = $data['status'] === 'active';
            }
        }

        // Load thông tin User nếu có email
        if (!empty($data['email'])) {
            $user = User::where('email', $data['email'])->first();
            if ($user) {
                // Không load password từ User, chỉ để trống
                $data['password'] = '';
                $data['password_confirmation'] = '';
            }
        }

        // Đảm bảo các relationship fields được load đúng
        if (isset($data['organization_id']) && !empty($data['organization_id'])) {
            // Organization ID đã có, không cần xử lý thêm
        }

        if (isset($data['upline_id']) && !empty($data['upline_id'])) {
            // Upline ID đã có, không cần xử lý thêm
        }

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array {
        // Debug: Log data trước khi xử lý
        \Illuminate\Support\Facades\Log::info('EditCollaborator - Data before save:', $data);

        // Cập nhật mật khẩu nếu có
        if (!empty($data['email'])) {
            $user = User::where('email', $data['email'])->first();
            if ($user) {
                $password = !empty($data['password']) ? $data['password'] : '123456';
                $user->update([
                    'password' => Hash::make($password)
                ]);
            }
        }

        // Loại bỏ password khỏi data trước khi lưu Collaborator
        unset($data['password'], $data['password_confirmation']);

        // Debug: Log data sau khi xử lý
        \Illuminate\Support\Facades\Log::info('EditCollaborator - Data after processing:', $data);

        return $data;
    }

    protected function getHeaderActions(): array {
        return [
            ViewAction::make()
                ->label('Xem chi tiết'),
        ];
    }

    protected function getFormActions(): array {
        return [
            $this->getSaveFormAction()
                ->label('Lưu thay đổi'),
            $this->getCancelFormAction()
                ->label('Hủy'),
        ];
    }

    public function getTitle(): string {
        return 'Chỉnh sửa CTV con';
    }

    public function getBreadcrumb(): string {
        return 'Chỉnh sửa CTV con';
    }
}
