<?php

namespace App\Services;

use App\Models\User;
use PragmaRX\Google2FA\Google2FA;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

class TwoFactorService
{
    protected Google2FA $google2fa;

    public function __construct()
    {
        $this->google2fa = new Google2FA();
    }

    public function generateSecret(User $user): string
    {
        $secret = $this->google2fa->generateSecretKey();
        $user->two_factor_secret = encrypt($secret);
        $user->save();

        return $secret;
    }

    public function getQrCodeUrl(User $user, string $secret): string
    {
        $qrCodeUrl = $this->google2fa->getQRCodeUrl(
            config('app.name'),
            $user->email,
            $secret
        );

        $renderer = new ImageRenderer(
            new RendererStyle(200),
            new SvgImageBackEnd()
        );

        $writer = new Writer($renderer);
        return $writer->writeString($qrCodeUrl);
    }

    public function generateCode(User $user): string
    {
        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        \Cache::put('2fa_code_' . $user->id, [
            'code' => $code,
            'expires_at' => now()->addMinutes(10)
        ], 10);

        // send the code

        return $code;
    }

    public function verifyCode(User $user, string $code): bool
    {
        $storedCode = \Cache::get('2fa_code_' . $user->id);

        if (!$storedCode || now()->isAfter($storedCode['expires_at'])) {
            return false;
        }

        if ($storedCode['code'] !== $code) {
            return false;
        }

        \Cache::forget('2fa_code_' . $user->id);
        return true;
    }

    public function verifyGoogle2FA(User $user, string $code): bool
    {
        if (!$user->two_factor_secret) {
            return false;
        }

        $secret = decrypt($user->two_factor_secret);
        return $this->google2fa->verifyKey($secret, $code);
    }
}
