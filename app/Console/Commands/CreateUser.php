<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

final class CreateUser extends Command
{
    protected $signature = 'app:create-user {email} {--name=}';

    protected $description = 'Create a user without platform access; a company owner must then assign membership';

    public function handle(): int
    {
        $data = ['email' => $this->argument('email'), 'name' => $this->option('name'), 'password' => $this->secret('Password')];
        $values = Validator::make($data, ['email' => 'required|email|unique:users,email', 'name' => 'required|string|max:255', 'password' => ['required', Password::min(12)->mixedCase()->numbers()->symbols()]])->validate();
        User::create($values);
        $this->info('User created without company or platform access.');

        return self::SUCCESS;
    }
}
