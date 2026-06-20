<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules;

class CreateUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:create-user';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Creates a new user and assigns them a role';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Creating a new user...');

        // 1. Get User Input
        $username = $this->ask('Enter a username');
        $email = $this->ask('Enter an email');
        $password = $this->secret('Enter a password');
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
            $this->error('User creation failed!');
            foreach ($validator->errors()->all() as $error) {
                $this->line($error);
            }
            return 1; // Return an error code
        }

        // 3. Fetch available roles and let the user choose
        $roles = Role::pluck('name', 'id')->all();
        if (empty($roles)) {
            $this->error('No roles found in the database. Please run the RoleSeeder first.');
            return 1;
        }

        $roleName = $this->choice(
            'Which role should this user have?',
            $roles
        );

        $role = Role::where('name', $roleName)->first();

        // 4. Create the User
        $user = User::create([
            'username' => $username,
            'email' => $email,
            'password' => $password, // The model will hash this automatically
            'is_verified' => 1,
        ]);

        // 5. Attach the selected role
        $user->roles()->attach($role->id);
        $this->info("User '{$user->username}' created successfully with the '{$role->name}' role.");

        return 0; // Return a success code
    }
}
