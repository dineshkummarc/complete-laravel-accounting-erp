<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Modules\Core\Models\AuditEvent;
use App\Modules\Core\Services\AuditTrail;
use App\Modules\Core\Services\BusinessContext;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;

final class AuditLog extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';

    protected static string $view = 'filament.pages.audit-log';

    public static function canAccess(): bool
    {
        return Team::canAccess();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return self::canAccess();
    }

    public static function getNavigationLabel(): string
    {
        return __('business.audit_log');
    }

    public function getTitle(): string
    {
        return __('business.audit_log');
    }

    public function table(Table $table): Table
    {
        return $table->query(AuditEvent::query())->columns([
            TextColumn::make('created_at')->label(__('business.date'))->dateTime()->sortable(),
            TextColumn::make('action')->label(__('business.action'))->searchable(),
            TextColumn::make('subject_type')->label(__('business.record_type')),
            TextColumn::make('subject_id')->label(__('business.record_id')),
        ])->defaultSort('id', 'desc')->paginated([25, 50, 100]);
    }

    protected function getHeaderActions(): array
    {
        return [Action::make('verify')->label(__('business.verify_audit'))->action(function (): void {
            abort_unless(static::canAccess(), 403);
            $valid = app(AuditTrail::class)->verify(app(BusinessContext::class)->companyId);
            Notification::make()->title($valid ? __('business.audit_valid') : __('business.audit_invalid'))->status($valid ? 'success' : 'danger')->send();
        })];
    }
}
