<?php

declare(strict_types=1);

namespace App\Modules\Core\Actions;

use App\Models\User;
use App\Modules\Core\Models\Branch;
use App\Modules\Core\Services\AuditTrail;
use App\Modules\Core\Services\BusinessContext;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

final class CreateBranch
{
    /** @param array<string, mixed> $data */
    public function handle(User $actor, array $data): Branch
    {
        $context = app(BusinessContext::class);
        if (! DB::table('company_user')->where('company_id', $context->companyId)->where('user_id', $actor->id)->where('is_owner', true)->exists()) {
            throw new AuthorizationException('Only company owners may create branches.');
        }
        $values = Validator::make($data, ['name' => 'required|string|max:255', 'code' => ['required', 'string', 'max:20', 'regex:/^[A-Z0-9-]+$/',
            Rule::unique('branches', 'code')->where('company_id', $context->companyId)], 'timezone' => 'required|timezone'])->validate();

        return DB::transaction(function () use ($context, $actor, $values): Branch {
            $branch = new Branch($values);
            $branch->created_by = $actor->id;
            $branch->save();
            app(AuditTrail::class)->record($context->companyId, $branch->id, $actor->id, 'branch.created', Branch::class, (string) $branch->id, [], $values);

            return $branch;
        });
    }
}
