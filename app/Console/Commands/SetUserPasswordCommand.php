<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class SetUserPasswordCommand extends Command
{
    protected $signature = 'system:user-password {email : Email address of the account}';

    protected $description = 'Safely set a user password with the configured bcrypt hasher';

    public function handle(AuditLogger $auditLogger): int
    {
        $email = Str::lower(trim((string) $this->argument('email')));
        $user = User::query()->whereRaw('LOWER(email) = ?', [$email])->first();

        if (! $user) {
            $this->error('No account was found for this email address.');

            return self::FAILURE;
        }

        $password = (string) $this->secret('New password');
        $confirmation = (string) $this->secret('Confirm new password');
        $validator = Validator::make([
            'password' => $password,
            'password_confirmation' => $confirmation,
        ], [
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $message) {
                $this->error($message);
            }

            return self::FAILURE;
        }

        DB::transaction(function () use ($auditLogger, $password, $user): void {
            $user->forceFill([
                'password' => Hash::make($password),
                'remember_token' => Str::random(60),
            ])->save();

            $user->tokens()->delete();
            DB::table('sessions')->where('user_id', $user->id)->delete();
            DB::table(config('auth.passwords.users.table', 'password_reset_tokens'))
                ->where('email', $user->email)
                ->delete();

            $auditLogger->record('auth.password.console_reset', $user, newValues: ['source' => 'console']);
        });

        $this->info('Password updated securely with bcrypt. Existing sessions and API tokens were revoked.');

        return self::SUCCESS;
    }
}
