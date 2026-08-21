<?php

namespace App\Http\Middleware;

use App\Services\AccessControl;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        if (! $user->is_active) {
            auth()->logout();

            return redirect()->route('login')->withErrors([
                'email' => 'Akun ini dinonaktifkan. Hubungi SuperAdmin.',
            ]);
        }

        $idgudang = $request->route('idgudang');
        if ($idgudang !== null && ! $user->canAccessGudang((int) $idgudang)) {
            return redirect()->route('home')->with('error', 'Anda tidak punya akses ke gudang ini. Hubungi SuperAdmin jika perlu ditugaskan.');
        }

        $requirement = AccessControl::requirementFor($request->route()?->getName());
        if ($requirement && ! AccessControl::allows($user, $requirement[0], $requirement[1])) {
            return redirect()->route('home')->with('error', 'Anda tidak punya akses ke menu tersebut.');
        }

        return $next($request);
    }
}
