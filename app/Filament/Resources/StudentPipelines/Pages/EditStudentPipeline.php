<?php

namespace App\Filament\Resources\StudentPipelines\Pages;

use App\Filament\Resources\StudentPipelines\StudentPipelineResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditStudentPipeline extends EditRecord
{
    protected static string $resource = StudentPipelineResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
