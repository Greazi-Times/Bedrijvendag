<?php

namespace App\Filament\Resources\AuditLogs\Pages;

use App\Filament\Resources\AuditLogs\AuditLogResource;
use App\Models\AuditLog;
use App\Support\Audit;
use App\Support\AuditRetention;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;
use Maatwebsite\Excel\Excel;
use pxlrbt\FilamentExcel\Actions\ExportAction;
use pxlrbt\FilamentExcel\Columns\Column;
use pxlrbt\FilamentExcel\Exports\ExcelExport;

class ListAuditLogs extends ListRecords
{
    protected static string $resource = AuditLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->exportAction(),
            $this->retentionAction(),
        ];
    }

    /** Exports the filtered list. Each export is itself logged, with the filters used. */
    private function exportAction(): ExportAction
    {
        $columns = [
            'id' => 'Event ID', 'created_at' => 'Time (UTC)', 'log_name' => 'Category', 'event' => 'Event',
            'severity' => 'Severity', 'result' => 'Result', 'actor_type' => 'Actor type', 'causer_id' => 'User ID',
            'actor_label' => 'Actor', 'ip_address' => 'IP address', 'http_method' => 'Method', 'route_name' => 'Route',
            'path' => 'Path', 'request_id' => 'Request ID', 'subject_type' => 'Resource type', 'subject_id' => 'Resource ID',
            'subject_label' => 'Resource', 'description' => 'Description',
        ];

        return ExportAction::make('exportAuditLog')
            ->authorize('export')
            ->color('gray')
            ->label('Download CSV')
            ->icon(Heroicon::OutlinedArrowDownTray)
            ->before(fn () => Audit::log('audit.exported', 'Audit log exported', properties: [
                'filters' => array_filter($this->tableFilters ?? [], fn ($filter): bool => filled(array_filter((array) $filter))),
                'search' => $this->tableSearch ?: null,
                'rows' => $this->getFilteredTableQuery()->count(),
            ]))
            ->exports([
                ExcelExport::make('audit-log')
                    ->fromTable()
                    ->withColumns(collect($columns)->map(fn (string $heading, string $name): Column => Column::make($name)
                        ->heading($heading)
                        ->getStateUsing(fn (AuditLog $record): string => self::csvSafe($name === 'created_at'
                            ? $record->created_at->utc()->format('Y-m-d H:i:s')
                            : (string) $record->getAttribute($name))))
                        ->values()
                        ->all())
                    ->only(array_keys($columns))
                    ->withWriterType(Excel::CSV)
                    ->withFilename('audit-log-'.now()->format('Y-m-d-His')),
            ]);
    }

    private function retentionAction(): Action
    {
        return Action::make('pruneAuditLog')
            ->label('Retention cleanup')
            ->icon(Heroicon::OutlinedTrash)
            ->color('gray')
            ->visible(fn (): bool => auth()->user()?->can('audit.manage-retention') ?? false)
            ->authorize(fn (): bool => auth()->user()?->can('audit.manage-retention') ?? false)
            ->requiresConfirmation()
            ->modalHeading('Remove expired audit entries')
            ->modalDescription(function (): string {
                $counts = AuditRetention::prune(dryRun: true);

                return $counts
                    ? 'This permanently removes entries past their retention period (config/audit.php): '.collect($counts)->map(fn (int $count, string $category): string => "{$category}: {$count}")->join(', ').'.'
                    : 'No entries have passed their retention period.';
            })
            ->modalSubmitActionLabel('Remove')
            ->action(function (): void {
                $removed = array_sum(AuditRetention::prune(dryRun: false));

                Notification::make()->title("{$removed} expired entries removed")->success()->send();
            });
    }

    /** Spreadsheet apps run cells starting with these characters as formulas; logged text is untrusted. */
    private static function csvSafe(string $value): string
    {
        return preg_match('/^[=+\-@\t\r]/', $value) ? "'{$value}" : $value;
    }
}
