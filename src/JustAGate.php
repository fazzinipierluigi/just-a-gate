<?php

namespace Fazzinipierluigi\JustAGate;

use Fazzinipierluigi\JustAGate\Models\Permission;
use Fazzinipierluigi\JustAGate\Models\Role;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class JustAGate
{
    // ── Role CRUD ─────────────────────────────────────────────────────────────

    public function createRole(string $name, string $slug, bool $isAdmin = false, bool $isSystem = false): Role
    {
        return Role::create([
            'name'      => $name,
            'slug'      => $slug,
            'is_admin'  => $isAdmin,
            'is_system' => $isSystem,
        ]);
    }

    public function findRole(string $slug): ?Role
    {
        return Role::where('slug', $slug)->first();
    }

    public function allRoles(): Collection
    {
        return Role::all();
    }

    public function deleteRole(Role|string $role): bool
    {
        return (bool) $this->resolveRole($role)->delete();
    }

    // ── Permission CRUD ───────────────────────────────────────────────────────

    public function createPermission(string $key, ?string $name = null, ?string $description = null): Permission
    {
        return Permission::create([
            'key'         => strtolower($key),
            'name'        => $name,
            'description' => $description,
        ]);
    }

    public function findPermission(string $key): ?Permission
    {
        return Permission::findByKey($key);
    }

    public function allPermissions(): Collection
    {
        return Permission::all();
    }

    public function deletePermission(Permission|string $permission): bool
    {
        return (bool) $this->resolvePermission($permission)->delete();
    }

    // ── User ↔ Role ───────────────────────────────────────────────────────────

    public function assignRole(Model $user, Role|string ...$roles): void
    {
        $user->assignRole(...$roles);
    }

    public function removeRole(Model $user, Role|string ...$roles): void
    {
        $user->removeRole(...$roles);
    }

    public function syncRoles(Model $user, array $roles): void
    {
        $user->syncRoles($roles);
    }

    // ── Role ↔ Permission ─────────────────────────────────────────────────────

    public function givePermission(Role|string $role, Permission|string ...$permissions): void
    {
        $this->resolveRole($role)->givePermission(...$permissions);
    }

    public function revokePermission(Role|string $role, Permission|string ...$permissions): void
    {
        $this->resolveRole($role)->revokePermission(...$permissions);
    }

    public function syncPermissions(Role|string $role, array $permissions): void
    {
        $this->resolveRole($role)->syncPermissions($permissions);
    }

    // ── Checks ────────────────────────────────────────────────────────────────

    public function userCan(Model $user, string $ability): bool
    {
        return $user->can($ability);
    }

    public function userHasRole(Model $user, Role|string $role): bool
    {
        return $user->hasRole($role);
    }

    public function roleHasPermission(Role|string $role, string $key): bool
    {
        return $this->resolveRole($role)->hasPermission($key);
    }

    // ── Discovery ─────────────────────────────────────────────────────────────

    public function usersWithRole(Role|string $role): Collection
    {
        return $this->resolveRole($role)->users;
    }

    public function rolesForPermission(Permission|string $permission): Collection
    {
        return $this->resolvePermission($permission)->roles;
    }

    // ── internals ─────────────────────────────────────────────────────────────

    private function resolveRole(Role|string $role): Role
    {
        if ($role instanceof Role) {
            return $role;
        }
        return Role::where('slug', $role)->firstOrFail();
    }

    private function resolvePermission(Permission|string $permission): Permission
    {
        if ($permission instanceof Permission) {
            return $permission;
        }
        return Permission::where('key', $permission)->firstOrFail();
    }
}
