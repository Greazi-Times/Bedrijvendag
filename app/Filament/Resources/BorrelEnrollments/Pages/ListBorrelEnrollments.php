<?php

namespace App\Filament\Resources\BorrelEnrollments\Pages;

use App\Filament\Resources\BorrelEnrollments\BorrelEnrollmentResource;
use App\Filament\Resources\BorrelEnrollments\Tables\BorrelEnrollmentsTable;
use App\Models\BorrelEnrollment;
use App\Models\Event;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Resources\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ListBorrelEnrollments extends Page implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    protected static string $resource = BorrelEnrollmentResource::class;

    protected string $view = 'filament.resources.borrel-enrollments.pages.list-borrel-enrollments';

    public ?int $selectedEventId = null;

    public function mount(): void
    {
        $this->selectedEventId = Event::query()
            ->nextOrLatest()
            ->value('id');
    }

    public function updatedSelectedEventId($state): void
    {
        $this->selectedEventId = filled($state) ? (int) $state : null;
        $this->resetTable();
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Select::make('selectedEventId')
                    ->label('Event')
                    ->options(Event::query()->orderByDesc('date')->pluck('name', 'id'))
                    ->live()
                    ->afterStateHydrated(function ($state): void {
                        $this->selectedEventId = filled($state) ? (int) $state : $this->selectedEventId;
                    })
                    ->afterStateUpdated(function ($state): void {
                        $this->updatedSelectedEventId($state);
                    }),
            ]);
    }

    public function table(Table $table): Table
    {
        return BorrelEnrollmentsTable::configure($table)
            ->query(fn (): Builder => $this->getEnrollmentQuery());
    }

    protected function getEnrollmentQuery(): Builder
    {
        if (! $this->selectedEventId) {
            return BorrelEnrollment::query()->whereRaw('1 = 0');
        }

        return BorrelEnrollment::query()
            ->where('event_id', $this->selectedEventId);
    }
}
