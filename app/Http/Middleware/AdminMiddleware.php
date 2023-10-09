<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Http\Request;

class AdminMiddleware
{
    public function handle($request, Closure $next)
    {
        $userId = Auth::id();

        $isAdmin = User::where('id', $userId)->value('IsAdmin') == 1;

        if (Auth::check() && $isAdmin) {
            return $next($request);
        }

        return redirect('/');
    }
}
