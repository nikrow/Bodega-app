<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class UpdateLastActivity
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if ($user) {
            Log::info('Updating last activity for user ID: ' . $user->id);
            $user->update([
                'last_activity_at' => now(),
            ]);
        } else {
            Log::warning('User not found in request');
        }

        return $next($request);
    }
}
