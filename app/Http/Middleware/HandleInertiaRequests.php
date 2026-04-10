<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user(),
                'roles' => $request->user()?->getRoleNames() ?? [],
                'can' => [
                    'view_missions'       => $request->user()?->can('view missions') ?? false,
                    'create_missions'     => $request->user()?->can('create missions') ?? false,
                    'edit_missions'       => $request->user()?->can('edit missions') ?? false,
                    'update_mission_status' => $request->user()?->can('update mission status') ?? false,
                    'view_personnel'      => $request->user()?->can('view personnel') ?? false,
                    'manage_personnel'    => $request->user()?->can('manage personnel') ?? false,
                    'manage_users'        => $request->user()?->can('manage users') ?? false,
                ],
            ],
            'flash' => [
                'success' => $request->session()->get('success'),
                'error'   => $request->session()->get('error'),
            ],
        ];
    }
}
