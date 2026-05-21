<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index()
    {
        $users = User::with('roles')->get();
        return Inertia::render('Admin/Users/Index', [
            'users' => $users
        ]);
    }

    public function edit(User $user)
    {
        $roles = Role::where('guard_name', 'web')->get();
        $user->load('roles');

        return Inertia::render('Admin/Users/Edit', [
            'user' => $user,
            'roles' => $roles,
            'userRoles' => $user->roles->pluck('name')
        ]);
    }

    public function update(Request $request, User $user)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $user->id,
            'roles' => 'array'
        ]);

        $user->update([
            'name' => $request->name,
            'email' => $request->email,
        ]);

        $rolesToSync = [];
        if ($request->has('roles')) {
            $rolesToSync = $request->roles;
        }

        if ($user->email === 'raul.andres.devs@gmail.com') {
            if (!in_array('super-admin', $rolesToSync)) {
                $rolesToSync[] = 'super-admin';
            }
        }

        $roles = Role::whereIn('name', $rolesToSync)->where('guard_name', 'web')->get();
        $user->syncRoles($roles);

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        return redirect()->route('admin.users.index')->with('success', 'Usuario actualizado correctamente.');
    }

    public function destroy(User $user)
    {
        if ($user->email === 'raul.andres.devs@gmail.com') {
            return redirect()->route('admin.users.index')->with('error', 'Acción denegada: Este usuario no puede ser eliminado.');
        }

        $user->delete();
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        return redirect()->route('admin.users.index')->with('success', 'Usuario eliminado correctamente.');
    }
}
