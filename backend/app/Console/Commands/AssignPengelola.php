<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Enums\RoleEnum;

class AssignPengelola extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:assign-pengelola {email : Email of the user to assign as pengelola}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Assign a user as pengelola';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email');

        if (!$email) {
            $this->error('Email is required');
            return 1;
        }

        $user = User::where('email', $email)->first();

        if (!$user) {
            $this->error('User not found');
            return 1;
        }

        $user->removeRole(RoleEnum::PENGUNJUNG);
        $user->removeRole(RoleEnum::PENELITI);
        $user->removeRole(RoleEnum::ADMIN);
        $user->assignRole(RoleEnum::PENGELOLA);

        $this->info('User assigned as pengelola successfully');
        return 0;
    }
}
