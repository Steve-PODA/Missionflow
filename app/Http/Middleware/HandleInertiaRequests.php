<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    public function handle(Request $request, \Closure $next): \Symfony\Component\HttpFoundation\Response
    {
        $response = parent::handle($request, $next);

        if (Auth::check()) {
            $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate');
            $response->headers->set('Pragma', 'no-cache');
        }

        return $response;
    }

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
        $user = $request->user();

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user?->only(['id', 'name', 'email', 'role', 'avatar', 'availability']),
                'roles' => $user?->getRoleNames() ?? [],
                'can' => [
                    'view_missions'         => $user?->can('view missions') ?? false,
                    'create_missions'       => $user?->can('create missions') ?? false,
                    'edit_missions'         => $user?->can('edit missions') ?? false,
                    'update_mission_status' => $user?->can('update mission status') ?? false,
                    'view_personnel'        => $user?->can('view personnel') ?? false,
                    'manage_personnel'      => $user?->can('manage personnel') ?? false,
                    'manage_users'          => $user?->can('manage users') ?? false,
                ],
            ],
            'flash' => [
                'success' => $request->session()->get('success'),
                'error'   => $request->session()->get('error'),
            ],
            'notifications' => [
                'unread_count' => $user?->unreadNotifications()->count() ?? 0,
                'items' => $user?->unreadNotifications()->latest()->take(10)->get()->map(fn($n) => [
                    'id'         => $n->id,
                    'data'       => $n->data,
                    'created_at' => $n->created_at->diffForHumans(),
                ])->toArray() ?? [],
            ],
        ];
    }
}
