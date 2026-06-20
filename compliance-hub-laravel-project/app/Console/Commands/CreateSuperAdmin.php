<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules;

class CreateSuperAdmin extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:create-super-admin';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Creates the initial super admin user for the application';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Creating a new Super Admin user...');

        // 1. Get User Input
        $username = $this->ask('Enter a username for the admin');
        $email = $this->ask('Enter an email for the admin');
        $password = $this->secret('Enter a password for the admin');
        $confirmPassword = $this->secret('Confirm the password');

        // 2. Validate the input
        $validator = Validator::make([
            'username' => $username,
            'email' => $email,
            'password' => $password,
            'password_confirmation' => $confirmPassword,
        ], [
            'username' => ['required', 'string', 'max:255', 'unique:users'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        if ($validator->fails()) {
            $this->error('Admin user creation failed!');
            foreach ($validator->errors()->all() as $error) {
                $this->line($error);
            }
            return 1; // Return an error code
        }

        // 3. Find or Create the 'Admin' role
        $adminRole = Role::firstOrCreate(['name' => 'Admin']);
        $this->info('Admin role found or created.');

        // 4. Create the User
        $user = User::create([
            'username' => $username,
            'email' => $email,
            'password' => $password, // The model will hash this automatically
            'is_verified' => 1,
        ]);

        // 5. Attach the Admin role
        $user->roles()->attach($adminRole->id);
        $this->info('User record created and Admin role assigned.');

        $this->info('Super Admin user created successfully!');
        return 0; // Return a success code
    }
}
