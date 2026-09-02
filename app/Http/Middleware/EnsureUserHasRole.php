<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * Validasi kolom users.role (enum) sesuai DATABASE_SCHEMA.md.
     * Contoh penggunaan: role:teknisi|kabeng|kaprog
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(401, 'Unauthenticated.');
        }

        // Laravel mengirim 'role:teknisi|kabeng' sebagai satu string,
        // jadi pecah berdasarkan '|'.
        $allowed = collect($roles)
            ->flatMap(fn (string $role) => explode('|', $role))
            ->map(fn (string $role) => trim($role))
            ->filter()
            ->all();

        $userRole = $user->role?->value;

        if (! in_array($userRole, $allowed, true)) {
            abort(403, 'Anda tidak memiliki akses ke halaman ini.');
        }

        return $next($request);
    }
}
