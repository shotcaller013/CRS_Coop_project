<?php
// app/Http/Middleware/CheckUserActive.php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckUserActive
{
    /**
     * Block deactivated users from any authenticated endpoint.
     * Register this in Kernel.php after the auth:sanctum middleware.
     */
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if ($user && !$user->is_active) {
            return response()->json([
                'message' => 'Your account has been deactivated. Please contact the administrator.',
            ], 401);
        }

        return $next($request);
    }
}
