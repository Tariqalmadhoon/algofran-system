<?php

namespace App\Services;

class WhatsAppLinkService
{
    /**
     * Build a safe WhatsApp conversation URL from a locally-entered or
     * international phone number. Local Palestinian numbers are expanded to
     * the configured calling code, while full international numbers are kept.
     */
    public function conversationUrl(?string $phone): ?string
    {
        $number = $this->normalizedNumber($phone);

        return $number ? 'https://wa.me/'.$number : null;
    }

    public function normalizedNumber(?string $phone): ?string
    {
        $number = preg_replace('/\D+/', '', (string) $phone);
        if ($number === '') {
            return null;
        }

        if (str_starts_with($number, '00')) {
            $number = substr($number, 2);
        }

        $defaultCountryCode = preg_replace('/\D+/', '', (string) config('system.contact_phone.default_country_calling_code', '972'));
        if ($defaultCountryCode !== '' && strlen($number) === 10 && str_starts_with($number, '0')) {
            $number = $defaultCountryCode.substr($number, 1);
        } elseif ($defaultCountryCode !== '' && strlen($number) === 9 && str_starts_with($number, '5')) {
            $number = $defaultCountryCode.$number;
        }

        return preg_match('/\A[1-9]\d{7,14}\z/', $number) ? $number : null;
    }
}
