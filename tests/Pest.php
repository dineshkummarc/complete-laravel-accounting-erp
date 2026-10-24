<?php

declare(strict_types=1);
use App\Models\User;
use App\Modules\Core\Actions\CreateCompany;
use App\Modules\Core\Models\Company;
use Tests\TestCase;

pest()->extend(TestCase::class)->in('Feature');

function businessCompany(User $owner, string $name = 'বাংলা ব্যবসা'): Company
{
    return app(CreateCompany::class)->handle($owner, ['name' => $name, 'currency' => 'BDT', 'locale' => 'en', 'timezone' => 'Asia/Dhaka', 'localization_pack' => 'bd']);
}
