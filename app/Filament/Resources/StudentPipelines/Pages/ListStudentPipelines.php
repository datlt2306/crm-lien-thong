<?php

namespace App\Filament\Resources\StudentPipelines\Pages;

use App\Filament\Resources\StudentPipelines\StudentPipelineResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListStudentPipelines extends ListRecords
{
    protected static string $resource = StudentPipelineResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
