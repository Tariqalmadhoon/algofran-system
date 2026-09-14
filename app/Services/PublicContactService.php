<?php

namespace App\Services;

use BaconQrCode\Renderer\Color\Rgb;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\Fill;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use LogicException;

class PublicContactService
{
    public function whatsappUrl(): string
    {
        return $this->contactData()['whatsapp_url'];
    }

    /**
     * @return array{
     *     whatsapp_number: string,
     *     whatsapp_display_number: string,
     *     whatsapp_url: string,
     *     map_url: string,
     *     map_embed_url: string,
     *     coordinates: string,
     *     qr_svg: string
     * }
     */
    public function details(): array
    {
        $contact = $this->contactData();

        return [
            ...$contact,
            'qr_svg' => $this->qrCode($contact['whatsapp_url']),
        ];
    }

    /**
     * @return array{
     *     whatsapp_number: string,
     *     whatsapp_display_number: string,
     *     whatsapp_url: string,
     *     map_url: string,
     *     map_embed_url: string,
     *     coordinates: string
     * }
     */
    private function contactData(): array
    {
        $number = preg_replace('/\D+/', '', (string) config('system.public_contact.whatsapp_number'));
        $latitude = trim((string) config('system.public_contact.latitude'));
        $longitude = trim((string) config('system.public_contact.longitude'));

        if (! is_string($number) || ! preg_match('/\A[1-9]\d{6,14}\z/', $number)) {
            throw new LogicException('The public WhatsApp number is not valid.');
        }

        if (! $this->validCoordinate($latitude, -90, 90) || ! $this->validCoordinate($longitude, -180, 180)) {
            throw new LogicException('The public center coordinates are not valid.');
        }

        $message = trim((string) config('system.public_contact.whatsapp_message'));
        $whatsappUrl = 'https://wa.me/'.$number.($message === '' ? '' : '?text='.rawurlencode($message));
        $coordinates = $latitude.','.$longitude;

        return [
            'whatsapp_number' => $number,
            'whatsapp_display_number' => trim((string) config('system.public_contact.whatsapp_display_number')) ?: '+'.$number,
            'whatsapp_url' => $whatsappUrl,
            'map_url' => 'https://www.google.com/maps/search/?api=1&query='.rawurlencode($coordinates),
            'map_embed_url' => 'https://www.google.com/maps?output=embed&q='.rawurlencode($coordinates).'&z=16',
            'coordinates' => $coordinates,
        ];
    }

    private function validCoordinate(string $value, float $minimum, float $maximum): bool
    {
        return is_numeric($value) && (float) $value >= $minimum && (float) $value <= $maximum;
    }

    private function qrCode(string $url): string
    {
        $svg = (new Writer(new ImageRenderer(
            new RendererStyle(232, 0, null, null, Fill::uniformColor(new Rgb(255, 255, 255), new Rgb(6, 78, 59))),
            new SvgImageBackEnd,
        )))->writeString($url);

        return trim(substr($svg, strpos($svg, "\n") + 1));
    }
}
