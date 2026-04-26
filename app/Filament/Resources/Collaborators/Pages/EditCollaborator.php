<?php

namespace App\Filament\Resources\Collaborators\Pages;

use App\Filament\Resources\Collaborators\CollaboratorResource;
use App\Models\User;
use App\Models\Collaborator;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Gate;

class EditCollaborator extends EditRecord {
    protected static string $resource = CollaboratorResource::class;

    public function mount(int | string $record): void {
        parent::mount($record);

        // Kiểm tra quyền update
        if (!Gate::allows('update', $this->record)) {
            abort(403, 'Bạn không có quyền chỉnh sửa cộng tác viên này.');
        }
    }

    protected function mutateFormDataBeforeFill(array $data): array {
        // Debug: Log data khi load form
        \Illuminate\Support\Facades\Log::info('EditCollaborator - Data before fill:', $data);

        // Đảm bảo ref_id được format đúng cho hiển thị
        if (!empty($data['ref_id'])) {
            $data['ref_id'] = request()->getSchemeAndHttpHost() . '/ref/' . $data['ref_id'];
        }

        // Xử lý status field - Đã bỏ ép kiểu boolean vì Select dùng string keys

        // Load thông tin User nếu có email
        if (!empty($data['email'])) {
            $user = User::where('email', $data['email'])->first();
            if ($user) {
                // Hiển thị dummy password để người dùng thấy là đã có mật khẩu
                $data['password'] = '********';
                $data['password_confirmation'] = '********';
            }
        }

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array {
        // Debug: Log data trước khi xử lý
        \Illuminate\Support\Facades\Log::info('EditCollaborator - Data before save:', $data);

        // Đồng bộ thông tin sang tài khoản User
        $originalEmail = $this->record->getOriginal('email') ?: $this->record->email;
        $currentEmail = $data['email'] ?? $this->record->email;

        if (!empty($currentEmail)) {
            // Tìm User dựa trên email cũ hoặc email mới
            $user = User::where('email', $originalEmail)->first();
            
            if ($user) {
                $userUpdate = [];
                
                // Cập nhật các thông tin nếu có thay đổi
                if (!empty($data['email']) && $data['email'] !== $user->email) {
                    $userUpdate['email'] = $data['email'];
                }
                
                // CHỈ CẬP NHẬT mật khẩu nếu có nhập mới và khác với chuỗi dummy '********'
                if (!empty($data['password']) && $data['password'] !== '********') {
                    $userUpdate['password'] = Hash::make($data['password']);
                    \Illuminate\Support\Facades\Log::info("EditCollaborator: Updated password for user {$user->email}");
                }
                
                if (!empty($data['full_name']) && $data['full_name'] !== $user->name) {
                    $userUpdate['name'] = $data['full_name'];
                }

                if (!empty($userUpdate)) {
                    $user->update($userUpdate);
                }
            } else {
                // Nếu chưa có User nhưng CTV có email -> Tự động tạo User
                // Nếu mật khẩu nhập vào là dummy hoặc rỗng thì dùng mặc định 123456
                $password = (!empty($data['password']) && $data['password'] !== '********') 
                    ? $data['password'] 
                    : '123456';
                    
                User::create([
                    'name' => $data['full_name'] ?? $this->record->full_name,
                    'email' => $currentEmail,
                    'password' => Hash::make($password),
                    'role' => 'collaborator',
                ])->assignRole('collaborator');
                
                \Illuminate\Support\Facades\Log::info("EditCollaborator: Created new user account for {$currentEmail}");
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
