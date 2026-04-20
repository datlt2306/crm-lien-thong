<?php

namespace App\Filament\Resources\Collaborators\Pages;

use App\Filament\Resources\Collaborators\CollaboratorResource;
use App\Models\User;
use App\Models\Collaborator;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Gate;

class CreateCollaborator extends CreateRecord {
    protected static string $resource = CollaboratorResource::class;

    /** Mật khẩu tạm để tạo User trong afterCreate (tránh UserObserver tạo Collaborator trùng trước khi Filament tạo) */
    protected ?string $pendingUserPassword = null;

    public function mount(): void {
        parent::mount();

        // Kiểm tra quyền create
        if (!Gate::allows('create', Collaborator::class)) {
            abort(403, 'Bạn không có quyền tạo cộng tác viên mới.');
        }
    }

    protected function mutateFormDataBeforeCreate(array $data): array {
        // Kiểm tra xem collaborator với email này đã tồn tại chưa
        $existingCollaborator = Collaborator::where('email', $data['email'] ?? '')->first();
        if ($existingCollaborator) {
            \Filament\Notifications\Notification::make()
                ->title('Email đã tồn tại')
                ->body('Email này đã được sử dụng bởi CTV khác. Vui lòng sử dụng email khác.')
                ->danger()
                ->send();

            throw new \Illuminate\Validation\ValidationException(
                \Illuminate\Support\Facades\Validator::make([], ['email' => 'unique:collaborators,email']),
            );
        }


        // Lưu mật khẩu để tạo User trong afterCreate. KHÔNG tạo User ở đây:
        // UserObserver khi tạo User (role=ctv) sẽ tạo Collaborator → trùng email với
        // Collaborator do Filament sắp tạo → lỗi UNIQUE. Tạo User sau khi Collaborator
        // đã tồn tại, UserObserver sẽ thấy Collaborator có sẵn và không tạo thêm.
        if (!empty($data['email'])) {
            $this->pendingUserPassword = !empty($data['password']) ? $data['password'] : '123456';
        }

        unset($data['password'], $data['password_confirmation']);

        return $data;
    }

    protected function afterCreate(): void {
        if (empty($this->record->email)) {
            $this->pendingUserPassword = null;
            return;
        }

        $existingUser = User::where('email', $this->record->email)->first();

        if (!$existingUser) {
            $userAccount = User::create([
                'name' => $this->record->full_name,
                'email' => $this->record->email,
                'password' => Hash::make($this->pendingUserPassword ?? '123456'),
                'role' => 'ctv',
            ]);
            $userAccount->assignRole('ctv');
        } else {
            $userAccount = $existingUser;
        }


        $this->pendingUserPassword = null;
    }

    public function getTitle(): string {
        return 'Thêm CTV con mới';
    }

    public function getBreadcrumb(): string {
        return 'Thêm CTV con mới';
    }

    protected function getFormActions(): array {
        return [
            $this->getCreateFormAction()
                ->label('Tạo cộng tác viên'),
            $this->getCancelFormAction()
                ->label('Hủy'),
        ];
    }
}
