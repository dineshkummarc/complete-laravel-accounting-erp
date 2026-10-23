<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Models\Role;
use App\Modules\Core\Actions\AddCompanyMember;
use App\Modules\Core\Models\Branch;
use App\Modules\Core\Services\BusinessContext;
use App\Modules\Core\Services\CompanyAccess;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;

final class Team extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static string $view = 'filament.pages.team';

    public static function getNavigationLabel(): string
    {
        return __('business.team');
    }

    public function getTitle(): string
    {
        return __('business.team');
    }

    public static function canAccess(): bool
    {
        return auth()->check() && app(CompanyAccess::class)->isOwner(auth()->user());
    }

    public static function shouldRegisterNavigation(): bool
    {
        return self::canAccess();
    }

    public function memberAction(): Action
    {
        return Action::make('member')->label(__('business.add_member'))->form([
            TextInput::make('email')->label(__('business.email'))->email()->required(),
            Select::make('branches')->label(__('business.branches'))->options(fn (): array => Branch::query()->pluck('name', 'id')->all())->multiple()->required(),
            Select::make('role')->label(__('business.role'))->options(fn (): array => Role::query()->where('team_id', app(BusinessContext::class)->companyId)->pluck('name', 'name')->all())->default('staff')->required(),
        ])->requiresConfirmation()->action(fn (array $data) => app(AddCompanyMember::class)->handle(auth()->user(), $data['email'], array_map(intval(...), $data['branches']), $data['role']));
    }

    /** @return list<array{name: string, email: string, is_owner: bool}> */
    public function members(): array
    {
        return DB::table('users')->join('company_user', 'users.id', '=', 'company_user.user_id')->where('company_user.company_id', app(BusinessContext::class)->companyId)
            ->select('users.name', 'users.email', 'company_user.is_owner')->get()->map(fn ($row): array => ['name' => $row->name, 'email' => $row->email, 'is_owner' => (bool) $row->is_owner])->all();
    }
}
