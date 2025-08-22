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

        if ($user && $user->role === 'chủ đơn vị') {
            // Tìm collaborator của user hiện tại
            $collaborator = Collaborator::where('email', $user->email)->first();
            if ($collaborator) {
                $data['collaborator_id'] = $collaborator->id;
                $data['organization_id'] = $collaborator->organization_id;
            }
        }

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
}
