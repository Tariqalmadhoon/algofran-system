<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Throwable;

class MailTestCommand extends Command
{
    protected $signature = 'system:mail-test {recipient : Email address that should receive the test message}';

    protected $description = 'Send a real delivery test using the configured production mailer';

    public function handle(): int
    {
        $recipient = trim((string) $this->argument('recipient'));
        $validator = Validator::make(['recipient' => $recipient], ['recipient' => ['required', 'email:rfc']]);

        if ($validator->fails()) {
            $this->error('Recipient must be a valid email address.');

            return self::INVALID;
        }

        if (in_array(config('mail.default'), ['array', 'log'], true)) {
            $this->error('A delivery mailer is not configured. Set MAIL_MAILER=smtp first.');

            return self::FAILURE;
        }

        try {
            Mail::raw(
                'هذه رسالة اختبار من نظام مركز الغفران. إذا وصلتك فالربط مع خدمة البريد يعمل بصورة صحيحة.',
                fn ($message) => $message
                    ->to($recipient)
                    ->subject('اختبار البريد | '.config('app.name')),
            );
        } catch (Throwable $exception) {
            report($exception);
            $this->error('Mail delivery failed. Review the SMTP configuration and the application log.');

            return self::FAILURE;
        }

        $this->info("Test message sent to {$recipient}.");

        return self::SUCCESS;
    }
}
