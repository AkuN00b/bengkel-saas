<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveActiveWorkshop
{
    public function handle(Request $request, Closure $next): Response
    {
        // Never retain context from another request or trust request input as tenant context.
        $request->attributes->remove('activeWorkshop');
        $selected = $request->session()->get('active_workshop_id');
        $workshop = null;

        if ($selected !== null) {
            $validId = (is_int($selected) || is_string($selected))
                && filter_var($selected, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) !== false;
            if ($validId) {
                $workshop = $request->user()->workshops()->whereKey($selected)->first();
            }

            if ($workshop === null) {
                $request->session()->forget('active_workshop_id');

                return $this->requireSelection($request);
            }
        } else {
            // Loading at most two is enough to distinguish zero, one, or several memberships.
            $workshops = $request->user()->workshops()->limit(2)->get();
            if ($workshops->count() !== 1) {
                return $this->requireSelection($request);
            }
            $workshop = $workshops->first();
        }

        $request->session()->put('active_workshop_id', $workshop->id);
        $request->attributes->set('activeWorkshop', $workshop);

        return $next($request);
    }

    private function requireSelection(Request $request): Response
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => 'Pilih bengkel yang dapat Anda akses.'], 403);
        }

        return redirect()->route('workshops.select')->with('status', 'Pilih bengkel yang dapat Anda akses.');
    }
}
