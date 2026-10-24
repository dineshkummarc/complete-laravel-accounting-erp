<?php

declare(strict_types=1);

namespace App\Modules\Accounting\Services;

use App\Models\User;
use App\Modules\Accounting\Models\Account;
use App\Modules\Accounting\Models\FiscalYear;
use App\Modules\Accounting\Reports\ReportParameters;
use App\Modules\Core\Models\Company;
use App\Modules\Core\Services\AuditTrail;
use App\Modules\Core\Services\BusinessContext;
use App\Modules\Core\Services\CompanyAccess;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

final class ReportScope
{
    /** @param array<string,mixed> $parameters */
    public function handle(User $actor, string $key, array $parameters): ReportParameters
    {
        app(LedgerAccess::class)->authorize($actor, 'accounting.report.view', false);
        $context = app(BusinessContext::class);
        $company = Company::query()->findOrFail($context->companyId);
        $to = $parameters['to'] ?? now($company->timezone)->toDateString();
        $from = $parameters['from'] ?? FiscalYear::query()->where('starts_on', '<=', $to)->where('ends_on', '>=', $to)->value('starts_on') ?? substr($to, 0, 4).'-01-01';
        $from = CarbonImmutable::parse($from)->toDateString();
        $v = Validator::make($parameters + ['from' => $from, 'to' => $to], ['from' => 'required|date_format:Y-m-d', 'to' => 'required|date_format:Y-m-d|after_or_equal:from',
            'branch_ids' => 'sometimes|array|min:1', 'branch_ids.*' => 'integer|min:1', 'account_id' => 'nullable|integer|min:1', 'include_opening' => 'sometimes|boolean'])->validate();
        $query = DB::table('branches')->where('company_id', $context->companyId);
        if (! app(CompanyAccess::class)->isOwner($actor)) {
            $query->whereIn('id', DB::table('branch_user')->where('user_id', $actor->id)->select('branch_id'));
        }
        $allowed = array_map(intval(...), $query->pluck('id')->all());
        $requested = $v['branch_ids'] ?? (in_array($key, ['branch-comparison', 'consolidated'], true) ? $allowed : [$context->branchId]);
        $branches = array_values(array_unique(array_map(intval(...), $requested)));
        if ($branches === [] || array_diff($branches, $allowed) !== []) {
            throw new AuthorizationException('Report branches are not authorized.');
        }
        sort($branches);
        $account = isset($v['account_id']) ? (int) $v['account_id'] : null;
        if ($key === 'account-statement' && $account === null) {
            throw ValidationException::withMessages(['account_id' => __('reports.choose_account')]);
        }
        if ($account !== null) {
            Account::query()->findOrFail($account);
        }
        if ($branches !== [$context->branchId]) {
            app(AuditTrail::class)->record($context->companyId, null, $actor->id, 'report.cross_branch', 'report', $key, [], ['branches' => $branches, 'from' => $v['from'], 'to' => $v['to']]);
        }

        return new ReportParameters($v['from'], $v['to'], $branches, $account, (bool) ($v['include_opening'] ?? true));
    }
}
