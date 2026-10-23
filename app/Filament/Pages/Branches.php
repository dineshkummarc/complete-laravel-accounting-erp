<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Modules\Core\Actions\AddAttachment;
use App\Modules\Core\Actions\CreateBranch;
use App\Modules\Core\Actions\QueueImport;
use App\Modules\Core\Actions\SwitchBranch;
use App\Modules\Core\Models\Attachment;
use App\Modules\Core\Models\Branch;
use App\Modules\Core\Services\BranchChoices;
use App\Modules\Core\Services\BusinessContext;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\URL;

final class Branches extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';

    protected static string $view = 'filament.pages.branches';

    public static function getNavigationLabel(): string
    {
        return __('business.branches');
    }

    public function getTitle(): string
    {
        return __('business.branches');
    }

    public function switchAction(): Action
    {
        return Action::make('switch')->label(__('business.switch_branch'))->form([
            Select::make('branch')->label(__('business.branch'))->options(fn (): array => app(BranchChoices::class)->all())->required(),
        ])->action(function (array $data): void {
            app(SwitchBranch::class)->handle(auth()->user(), (int) $data['branch']);
            $this->redirect(static::getUrl());
        });
    }

    public function importAction(): Action
    {
        return Action::make('import')->label(__('business.import_csv'))->form([
            FileUpload::make('file')->label(__('business.file'))->storeFiles(false)->required()->maxSize(10240)->acceptedFileTypes(['text/csv', 'text/plain']),
        ])->action(fn (array $data) => app(QueueImport::class)->handle(auth()->user(), 'branches', $data['file']));
    }

    public function attachmentAction(): Action
    {
        return Action::make('attachment')->label(__('business.attach_file'))->form([
            FileUpload::make('file')->label(__('business.file'))->storeFiles(false)->required()->maxSize(10240),
        ])->action(function (array $data): void {
            $branch = Branch::query()->findOrFail(app(BusinessContext::class)->branchId);
            app(AddAttachment::class)->handle(auth()->user(), $branch, $data['file']);
        });
    }

    /** @return array<string, string> */
    public function attachmentLinks(): array
    {
        return Attachment::query()->where('subject_type', Branch::class)->where('subject_id', app(BusinessContext::class)->branchId)->get()
            ->mapWithKeys(fn ($file): array => [URL::temporarySignedRoute('business.attachment', now()->addMinutes(10), ['uuid' => $file->uuid]) => $file->original_name])->all();
    }

    public function createAction(): Action
    {
        return Action::make('create')->label(__('business.create_branch'))->form([
            TextInput::make('name')->label(__('business.name'))->required()->maxLength(255),
            TextInput::make('code')->label(__('business.code'))->required()->maxLength(20)->regex('/^[A-Z0-9-]+$/')->unique(table: 'branches', modifyRuleUsing: fn ($rule) => $rule->where('company_id', app(BusinessContext::class)->companyId)),
            Select::make('timezone')->label(__('business.timezone'))->options(array_combine(timezone_identifiers_list(), timezone_identifiers_list()))->searchable()->default('Asia/Dhaka')->required(),
        ])->action(function (array $data): void {
            app(CreateBranch::class)->handle(auth()->user(), $data);
            Notification::make()->title(__('business.saved'))->success()->send();
            $this->redirect(static::getUrl());
        });
    }

    /** @return list<array{id: int, name: string, code: string}> */
    public function branchList(): array
    {
        return Branch::query()->whereIn('id', array_keys(app(BranchChoices::class)->all()))->get()
            ->map(fn (Branch $branch): array => ['id' => $branch->id, 'name' => $branch->name, 'code' => $branch->code])->values()->all();
    }
}
