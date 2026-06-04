<?php

namespace Fazzinipierluigi\JustAGate\Traits;

use Fazzinipierluigi\JustAGate\Models\Role;
use Illuminate\Database\Eloquent\Collection;

trait Authorizable
{
    private ?array $justgate_authorizations = null;
    private bool $just_gate_is_admin = false;

    public function roles()
    {
        return $this->belongsToMany(Role::class);
    }

    // ── ACL checks ────────────────────────────────────────────────────────────

    public function can($ability, $arguments = []): bool
    {
        if ($this->just_gate_is_admin) {
            return true;
        }

        if ($this->justgate_authorizations === null) {
            $this->justgate_authorizations = [];
            foreach ($this->roles as $role) {
                if ($role->is_admin) {
                    $this->just_gate_is_admin = true;
                    return true;
                }
                foreach ($role->permissions as $permission) {
                    $this->justgate_authorizations[] = $permission->key;
                }
            }
            $this->justgate_authorizations = array_unique($this->justgate_authorizations);
        }

        return in_array($ability, $this->justgate_authorizations);
    }

    public function cannot($ability, $arguments = []): bool
    {
        return !$this->can($ability, $arguments);
    }

    public function clearPermissionCache(): void
    {
        $this->justgate_authorizations = null;
        $this->just_gate_is_admin = false;
        $this->unsetRelation('roles');
    }

    // ── Role assignment ───────────────────────────────────────────────────────

    public function assignRole(Role|string|int ...$roles): static
    {
        $ids = array_map(fn($r) => $this->resolveRole($r)->id, $roles);
        $this->roles()->syncWithoutDetaching($ids);
        $this->clearPermissionCache();
        return $this;
    }

    public function removeRole(Role|string|int ...$roles): static
    {
        $ids = array_map(fn($r) => $this->resolveRole($r)->id, $roles);
        $this->roles()->detach($ids);
        $this->clearPermissionCache();
        return $this;
    }

    public function syncRoles(array $roles): static
    {
        $ids = array_map(fn($r) => $this->resolveRole($r)->id, $roles);
        $this->roles()->sync($ids);
        $this->clearPermissionCache();
        return $this;
    }

    // ── Role checks ───────────────────────────────────────────────────────────

    public function hasRole(Role|string $role): bool
    {
        if (is_string($role)) {
            return $this->roles->contains('slug', $role);
        }
        return $this->roles->contains('id', $role->id);
    }

    public function hasAnyRole(Role|string ...$roles): bool
    {
        foreach ($roles as $role) {
            if ($this->hasRole($role)) {
                return true;
            }
        }
        return false;
    }

    public function hasAllRoles(Role|string ...$roles): bool
    {
        if (empty($roles)) {
            return false;
        }
        foreach ($roles as $role) {
            if (!$this->hasRole($role)) {
                return false;
            }
        }
        return true;
    }

    public function getRoles(): Collection
    {
        return $this->roles;
    }

    // ── internals ─────────────────────────────────────────────────────────────

    private function resolveRole(Role|string|int $role): Role
    {
        if ($role instanceof Role) {
            return $role;
        }
        if (is_int($role)) {
            return Role::findOrFail($role);
        }
        return Role::where('slug', $role)->firstOrFail();
    }
}
