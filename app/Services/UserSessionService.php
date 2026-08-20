<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class UserSessionService
{
    /** @return array<int, array{id:string,ip_address:?string,device:string,last_active:string,current:bool}> */
    public function sessions(User $user, Request $request): array
    {
        if (config('session.driver') !== 'database') {
            return [];
        }

        return DB::table(config('session.table', 'sessions'))
            ->where('user_id', $user->id)
            ->latest('last_activity')
            ->get()
            ->map(fn ($session): array => [
                'id' => (string) $session->id,
                'ip_address' => $session->ip_address,
                'device' => $this->deviceLabel((string) $session->user_agent),
                'last_active' => Carbon::createFromTimestamp((int) $session->last_activity)->diffForHumans(),
                'current' => hash_equals((string) $request->session()->getId(), (string) $session->id),
            ])->all();
    }

    /** @return array{sessions:int,tokens:int} */
    public function revokeOthers(User $user, Request $request): array
    {
        $sessions = 0;
        if (config('session.driver') === 'database') {
            $sessions = DB::table(config('session.table', 'sessions'))
                ->where('user_id', $user->id)
                ->where('id', '!=', $request->session()->getId())
                ->delete();
        }

        $tokens = $user->tokens()->delete();

        return ['sessions' => $sessions, 'tokens' => $tokens];
    }

    private function deviceLabel(string $userAgent): string
    {
        $browser = match (true) {
            str_contains($userAgent, 'Edg/') => 'Edge',
            str_contains($userAgent, 'Chrome/') => 'Chrome',
            str_contains($userAgent, 'Firefox/') => 'Firefox',
            str_contains($userAgent, 'Safari/') => 'Safari',
            default => 'متصفح غير معروف',
        };
        $platform = match (true) {
            str_contains($userAgent, 'Windows') => 'Windows',
            str_contains($userAgent, 'Android') => 'Android',
            str_contains($userAgent, 'iPhone'), str_contains($userAgent, 'iPad') => 'iOS',
            str_contains($userAgent, 'Macintosh') => 'macOS',
            str_contains($userAgent, 'Linux') => 'Linux',
            default => 'جهاز غير معروف',
        };

        return "{$browser} — {$platform}";
    }
}
