<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),

            /*
             * Only what the shell renders. The constitution forbids exposing model
             * attributes wholesale, so this is deliberately name + role and nothing else
             * — no id, email, or timestamps (FR-009).
             */
            'auth' => [
                'user' => $user ? [
                    'name' => $user->name,
                    'role' => $user->role->value,
                ] : null,
            ],

            /*
             * The shell's message region (FR-010, FR-039). All three keys are always
             * present so the frontend never has to guard for a missing one. Note that no
             * controller flashes 'success' today and this feature may not add one
             * (FR-043) — the severity is supported, not yet exercised.
             */
            'flash' => [
                'success' => $request->session()->get('success'),
                'warning' => $request->session()->get('warning'),
                'error' => $request->session()->get('error'),
            ],
        ];
    }
}
