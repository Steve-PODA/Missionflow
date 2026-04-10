<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index()
    {
        $users = User::with('roles')
            ->withCount('missions')
            ->orderBy('name')
            ->get()
            ->map(fn($user) => [
                'id'             => $user->id,
                'name'           => $user->name,
                'email'          => $user->email,
                'phone_number'   => $user->phone_number,
                'role'           => $user->role,
                'availability'   => $user->availability,
                'spatie_role'    => $user->roles->first()?->name ?? 'technicien',
                'missions_count' => $user->missions_count,
            ]);

        $roles = Role::orderBy('name')->pluck('name');

        return Inertia::render('Users/Index', [
            'users' => $users,
            'roles' => $roles,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'         => 'required|string|max:255',
            'email'        => 'required|email|unique:users,email',
            'phone_number' => 'nullable|string|max:20',
            'password'     => ['required', Password::min(8)],
            'role'         => 'nullable|string|max:100',
            'spatie_role'  => 'required|exists:roles,name',
            'availability' => 'required|in:available,on_leave,unavailable',
        ]);

        $user = User::create([
            'name'         => $validated['name'],
            'email'        => $validated['email'],
            'phone_number' => $validated['phone_number'] ?? null,
            'password'     => Hash::make($validated['password']),
            'role'         => $validated['role'] ?? null,
            'availability' => $validated['availability'],
        ]);

        $user->assignRole($validated['spatie_role']);

        return Redirect::back()->with('success', "{$user->name} a été ajouté.");
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name'         => 'required|string|max:255',
            'email'        => "required|email|unique:users,email,{$user->id}",
            'phone_number' => 'nullable|string|max:20',
            'password'     => ['nullable', Password::min(8)],
            'role'         => 'nullable|string|max:100',
            'spatie_role'  => 'required|exists:roles,name',
            'availability' => 'required|in:available,on_leave,unavailable',
        ]);

        $data = [
            'name'         => $validated['name'],
            'email'        => $validated['email'],
            'phone_number' => $validated['phone_number'] ?? null,
            'role'         => $validated['role'] ?? null,
            'availability' => $validated['availability'],
        ];

        if (!empty($validated['password'])) {
            $data['password'] = Hash::make($validated['password']);
        }

        $user->update($data);
        $user->syncRoles([$validated['spatie_role']]);

        return Redirect::back()->with('success', "{$user->name} a été mis à jour.");
    }

    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return Redirect::back()->with('error', 'Vous ne pouvez pas supprimer votre propre compte.');
        }

        $name = $user->name;
        $user->delete();

        return Redirect::back()->with('success', "{$name} a été supprimé.");
    }
}
