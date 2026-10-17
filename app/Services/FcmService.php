<?php

namespace App\Services;

use App\Models\FcmDeviceToken;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FcmService
{
    const PROJECT_ID = 'nuzul-d2161';
    const CLIENT_EMAIL = 'firebase-adminsdk-fbsvc@nuzul-d2161.iam.gserviceaccount.com';
    const VAPID_KEY = 'BJcfKVomEe-5fyPcnaLttqggMH-kzlwXR-XJ823qehOdwW6elZu3rUEq2jn54Hq7iKQ7ZfPnvIZVArzLaulxYSY';

    public static function getPrivateKey(): string
    {
        $custom = env('FIREBASE_PRIVATE_KEY', '');
        if (!empty($custom)) {
            return str_replace('\n', "\n", $custom);
        }

        return "-----BEGIN PRIVATE KEY-----\n" .
            "MIIEvgIBADANBgkqhkiG9w0BAQEFAASCBKgwggSkAgEAAoIBAQDBgyhEONqm50us\n" .
            "HrnT+Y4zUoG9sI8FFQyUPIp0SpcPIJWX+z9DSazGJU4/NKvIMAYOMKSjDRdol5YR\n" .
            "HrNuxO9JIIlRXDDgPlYIkSImzpVgRF4gv3Wq9L8nEFHSQzhwqn7H8vs7xOPa44tR\n" .
            "I8mrCIizD60+LFRQakG70fVfPY5dCyaia9sXmvCIB2893KPISHTtf7y0vZ8gAE2z\n" .
            "Sy2BaywNksHn/jxuEB1YWCSvIWMD3jrypGPsAPOK2xVhS90P4BM5lJQ04t5wof4L\n" .
            "nNwx2F4f0Pym+PGfpgRCHInEGQdDnioPWr1z4SnS59u0+9cgOXWRx7McY3TDrRVm\n" .
            "8EV+51j9AgMBAAECggEACsILTUhO3uauwOB1asODLwxoHOmm+HycK3/hH51ySpuV\n" .
            "uB0Lk5FR5bMhvOb6oiwJ+M8l+/rolqk6D8oXO3Ip2QoncCqR/LQieCKfate4cLa5\n" .
            "opRE9aPac6YXMmYHyiWvBi0r3+hZKtb/F1vyucuFPqqBXQU9tmRt3UP1zPLBCnWR\n" .
            "CmlSXST+tPL/Oas2fTkp7xVsaqfsGIVnQb6bKT9Y+UENzUnryki3h6nFdAzu1jIv\n" .
            "FagX9ZAieKWLCkuCKdrjlzmndrIgNIQmlzwa2RSD12IlGpWqiu2K0cJV9bsaoD4V\n" .
            "oqhCKBZtRwwTPZ1JAnYg7ej6v1NWFx3ho1htOFNM+QKBgQD8/0h3ryzWS7+95FxB\n" .
            "ZrdlUU2ykuYTfNrvbMgSyfBu3jZXSvdU8rbECxZHtz1qaU5HcpFiM5aiAayu1ITL\n" .
            "ht9gPrLDU2gi64gRLaW4T+hZpMIqTysy9oTQld55G+62LgfVTrry/+MLPVuYeAjH\n" .
            "5Ie579sBCGEek3JsMaicp83NVQKBgQDDzyILsSC5KgnENyoZ35poHoJx1frt0Tcy\n" .
            "GrNb1oqP5JS2GXFHJ6cY1N/WS0iyOz+FPopyaBFouH87UeApUrtmRfWQeKDCC2Kn\n" .
            "A/DegfAL45H+Ql2xyLmOpKYfOGCJBmg9pGE47KEtB/KU3wdJZ+pih5CFoGtgCNMs\n" .
            "g9wHSsadCQKBgHW41+CRC3z/rE3Q17i68fVcGppQTmyc+UbVSFetzYnZLlH6GRJX\n" .
            "CgI44nomYpaEP59xSQL33EywGMkVLGGD9abUJdxLkDZHgH31zpnv3L7xskN7qS9C\n" .
            "lFR2mBmkjLQKwSxZQRZXSS9uspNEr1lMFnfHixF087QFigWcKcgTMOktAoGBAJME\n" .
            "FDXfGgisjYSrPosKne4K6jn6RVsFuIGAFqafoI2FPDq1Q53/IbC9NAq7n6tiXMD4\n" .
            "Qg7AIEIWNEEfqkScf2O8pzF/wIOw9LMdfF40BT2H8feLcLmpUdjNuoS6WZDIJMAT\n" .
            "iYOp9yHOU85+3gO5Z/vueQ47ciM6vQMxRaSb+h+xAoGBAPeyVPWVxDLnGVXV90k2\n" .
            "DQf44y+HweoCJE6A0SOqrkca33C2cTkUX0ZUspRfcN9Xyn4DR8AcBNmpViKKVKHS\n" .
            "SxD7lzyhWworXj7tDQzySMmlOXD6phpyjOagnGLpJNBlCFKMgj7w+sPdSWyYlKBe\n" .
            "U5BQMdtB7OSIi20iTNV/vZuX\n" .
            "-----END PRIVATE KEY-----";
    }

    public static function getAccessToken(): ?string
    {
        return Cache::remember('adm_fcm_oauth_token', 3300, function () {
            $key = self::getPrivateKey();
            $now = time();

            $header = self::base64UrlEncode(json_encode([
                'alg' => 'RS256',
                'typ' => 'JWT',
            ]));

            $claims = self::base64UrlEncode(json_encode([
                'iss' => self::CLIENT_EMAIL,
                'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
                'aud' => 'https://oauth2.googleapis.com/token',
                'exp' => $now + 3600,
                'iat' => $now,
            ]));

            $signature = '';
            $signSuccess = openssl_sign($header . '.' . $claims, $signature, $key, OPENSSL_ALGO_SHA256);
            if (!$signSuccess) {
                Log::error('[FcmService] OpenSSL sign failed');
                return null;
            }

            $jwt = $header . '.' . $claims . '.' . self::base64UrlEncode($signature);

            try {
                $response = Http::timeout(15)->withoutVerifying()->asForm()->post('https://oauth2.googleapis.com/token', [
                    'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                    'assertion' => $jwt,
                ]);

                if ($response->successful()) {
                    return $response->json('access_token');
                }
            } catch (\Throwable $e) {
                Log::error('[FcmService] Token exception: ' . $e->getMessage());
            }

            return null;
        });
    }

    public static function sendToToken(string $deviceToken, string $title, string $body, ?string $clickUrl = null): array
    {
        $accessToken = self::getAccessToken();
        if (!$accessToken) {
            return ['success' => false, 'error' => 'Google OAuth Token generation failed.'];
        }

        $url = 'https://fcm.googleapis.com/v1/projects/' . self::PROJECT_ID . '/messages:send';

        $payload = [
            'message' => [
                'token' => $deviceToken,
                'notification' => [
                    'title' => $title,
                    'body' => $body,
                ],
                'webpush' => [
                    'notification' => [
                        'title' => $title,
                        'body' => $body,
                        'icon' => asset('assets/icons/icon-192x192.png'),
                        'badge' => asset('assets/icons/icon-192x192.png'),
                    ],
                    'fcm_options' => [
                        'link' => $clickUrl ?: url('/'),
                    ],
                ],
            ],
        ];

        try {
            $response = Http::withToken($accessToken)
                ->timeout(12)
                ->withoutVerifying()
                ->post($url, $payload);

            if ($response->successful()) {
                return ['success' => true, 'response' => $response->json()];
            }

            $respBody = $response->body();
            if (str_contains($respBody, 'UNREGISTERED') || str_contains($respBody, 'NOT_FOUND')) {
                FcmDeviceToken::where('token', $deviceToken)->delete();
            }

            return ['success' => false, 'error' => $respBody];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public static function broadcastPush(string $title, string $body, ?string $clickUrl = null): int
    {
        $tokens = FcmDeviceToken::pluck('token')->unique();
        $sent = 0;
        foreach ($tokens as $t) {
            $res = self::sendToToken($t, $title, $body, $clickUrl);
            if ($res['success']) {
                $sent++;
            }
        }
        return $sent;
    }

    public static function getDeviceCount(): int
    {
        try {
            return FcmDeviceToken::distinct('token')->count('token');
        } catch (\Throwable $e) {
            return 0;
        }
    }

    public static function sendToAdmins(string $title, string $body, ?string $clickUrl = null): array
    {
        try {
            $tokens = FcmDeviceToken::pluck('token')->unique();
            $total = $tokens->count();
            if ($total === 0) {
                return ['total' => 0, 'success' => 0, 'failed' => 0];
            }
            $success = 0;
            foreach ($tokens as $t) {
                $res = self::sendToToken($t, $title, $body, $clickUrl);
                if (!empty($res['success'])) {
                    $success++;
                }
            }
            return [
                'total' => $total,
                'success' => $success,
                'failed' => $total - $success,
            ];
        } catch (\Throwable $e) {
            return ['total' => 0, 'success' => 0, 'failed' => 0, 'error' => $e->getMessage()];
        }
    }

    public static function isNotificationEnabled(string $eventKey): bool
    {
        if (!\App\Models\Setting::isTrue('adm_fcm_notifications_enabled', true)) {
            return false;
        }

        return \App\Models\Setting::isTrue($eventKey, true);
    }

    public static function notifyEvent(string $eventKey, string $title, string $body, ?string $clickUrl = null, $property = null): array
    {
        if (!self::isNotificationEnabled($eventKey)) {
            return ['skipped' => true, 'reason' => 'Notification disabled in settings'];
        }

        try {
            $tokens = FcmDeviceToken::pluck('token')->unique();

            if ($property && \App\Models\Setting::isTrue('adm_fcm_notify_agent_enabled', false)) {
                if (!empty($property->created_by_user_id)) {
                    $agentTokens = FcmDeviceToken::where('user_id', $property->created_by_user_id)
                        ->pluck('token');
                    $tokens = $tokens->merge($agentTokens)->unique();
                }
            }

            if ($tokens->isEmpty()) {
                return ['total' => 0, 'success' => 0, 'failed' => 0];
            }

            $success = 0;
            foreach ($tokens as $t) {
                $res = self::sendToToken($t, $title, $body, $clickUrl);
                if (!empty($res['success'])) {
                    $success++;
                }
            }

            return [
                'total' => $tokens->count(),
                'success' => $success,
                'failed' => $tokens->count() - $success,
            ];
        } catch (\Throwable $e) {
            Log::error('[FcmService] notifyEvent error: ' . $e->getMessage());
            return ['total' => 0, 'success' => 0, 'failed' => 0, 'error' => $e->getMessage()];
        }
    }

    private static function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
