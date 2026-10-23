<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

final class InstallOwner extends Command
{
    protected $signature = 'app:install-owner {email} {--name=Owner}';

    protected $description = 'Create the initial platform owner with an interactively entered password';

    public function handle(): int
    {
        $email = $this->argument('email');
        $password = $this->secret('Owner password (minimum 12 characters, mixed case, numbers and symbols)');
        Validator::make(['email' => $email, 'password' => $password], [
            'email' => 'required|email|unique:users,email',
            'password' => ['required', Password::min(12)->mixedCase()->numbers()->symbols()],
        ])->validate();
        DB::transaction(function () use ($email, $password): void {
            setPermissionsTeamId(0);
            $user = User::create(['name' => $this->option('name'), 'email' => $email, 'password' => $password]);
            $user->givePermissionTo(['app.access', 'platform.console']);
        });
        $this->info('Owner created. Sign in at /app/login.');

        return self::SUCCESS;
    }
}
