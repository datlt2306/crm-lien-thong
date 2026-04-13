<?php

namespace App\Filament\Resources\Students\Pages;

use App\Filament\Resources\Students\StudentResource;
use App\Models\Collaborator;
use App\Models\Organization;
use App\Models\Student;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateStudent extends CreateRecord {
    protected static string $resource = StudentResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array {
        $user = Auth::user();

        if ($user) {
            if (in_array($user->role, ['organization_owner', 'ctv'])) {
                // Tìm collaborator của user hiện tại
                $collaborator = Collaborator::where('email', $user->email)->first();
                if ($collaborator) {
                    // Nếu học viên "đến trực tiếp" thì không auto gán CTV
                    if (($data['source'] ?? null) !== 'walkin') {
                        $data['collaborator_id'] = $collaborator->id;
                    } else {
                        $data['collaborator_id'] = null;
                    }
                    $data['organization_id'] = $collaborator->organization_id;
                    
                    // Tự động điền GVHD từ tên CTV nếu chưa có
                    if (empty($data['instructor']) && !empty($collaborator->full_name)) {
                        $data['instructor'] = $collaborator->full_name;
                    }
                }
            }

            if (empty($data['organization_id'])) {
                $organization = !empty($user->organization_id)
                    ? Organization::find($user->organization_id)
                    : null;

                $organization = $organization
                    ?? Organization::where('organization_owner_id', $user->id)->first();
                if ($organization) {
                    $data['organization_id'] = $organization->id;
                }
            }
            
            // Nếu chưa có instructor và có user name, điền từ user name
            if (empty($data['instructor']) && !empty($user->name)) {
                $data['instructor'] = $user->name;
            }
        }

        // Fallback cuối cùng để tránh lỗi NOT NULL
        if (empty($data['organization_id'])) {
            $data['organization_id'] = Organization::query()->value('id');
        }

        // Tự động set status mặc định
        $data['status'] = Student::STATUS_NEW;

        return $data;
    }

    protected function getValidationRules(): array {
        return Student::getValidationRules();
    }

    public function getTitle(): string {
        return 'Thêm học viên mới';
    }

    public function getBreadcrumb(): string {
        return 'Thêm học viên mới';
    }

    protected function getFormActions(): array {
        return [
            $this->getCreateFormAction()
                ->label('Tạo học viên'),
            $this->getCancelFormAction()
                ->label('Hủy'),
        ];
    }

    public static function canAccess(array $parameters = []): bool {
        $user = Auth::user();
        return $user && in_array($user->role, ['super_admin', 'organization_owner', 'ctv', 'accountant', 'admissions']);
    }
}
