<?php
// app/Http/Middleware/UpdateLastLogin.php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UpdateLastLogin
{
    /**
     * Update last_login_at and last_login_ip on every authenticated request.
     * Throttled to once per hour to avoid hammering the DB on every API call.
     */
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if ($user) {
            $threshold = now()->subHour();
            if (!$user->last_login_at || $user->last_login_at->lt($threshold)) {
                // Use DB::table to avoid triggering model observers
                DB::table('users')->where('id', $user->id)->update([
                    'last_login_at' => now(),
                    'last_login_ip' => $request->ip(),
                ]);
            }
        }

        return $next($request);
    }
}
