<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CreateQrAdmin extends Command
{
    protected $signature = 'qr:admin {email} {--name=QR Admin} {--password=}';

    protected $description = 'Create or update a QR admin user';

    public function handle(): int
    {
        $email = $this->argument('email');
        $password = $this->option('password') ?: $this->secret('Password');
        $u = User::updateOrCreate(['email' => $email], ['name' => $this->option('name'), 'password' => Hash::make($password)]);
        $this->info("Admin siap: {$u->email}");

        return self::SUCCESS;
    }
}
