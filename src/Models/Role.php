<?php

namespace Fazzinipierluigi\JustAGate\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    protected $guarded = [];

    protected $attributes = [
        'is_admin'  => false,
        'is_system' => false,
    ];

    protected $casts = [
        'is_admin'  => 'boolean',
        'is_system' => 'boolean',
    ];

    public function users()
    {
        return $this->belongsToMany(\App\Models\User::class);
    }

    public function permissions()
    {
        return $this->belongsToMany(Permission::class);
    }

    // ── Permission assignment ─────────────────────────────────────────────────

    public function givePermission(Permission|string ...$permissions): static
    {
        foreach ($permissions as $permission) {
            $this->permissions()->syncWithoutDetaching([$this->resolvePermission($permission)->id]);
        }
        $this->unsetRelation('permissions');
        return $this;
    }

    public function revokePermission(Permission|string ...$permissions): static
    {
        foreach ($permissions as $permission) {
            $this->permissions()->detach($this->resolvePermission($permission)->id);
        }
        $this->unsetRelation('permissions');
        return $this;
    }

    public function syncPermissions(array $permissions): static
    {
        $ids = array_map(fn($p) => $this->resolvePermission($p)->id, $permissions);
        $this->permissions()->sync($ids);
        $this->unsetRelation('permissions');
        return $this;
    }

    // ── Permission checks ─────────────────────────────────────────────────────

    public function hasPermission(string $key): bool
    {
        return $this->permissions->contains('key', $key);
    }

    // ── internals ─────────────────────────────────────────────────────────────

    private function resolvePermission(Permission|string $permission): Permission
    {
        if ($permission instanceof Permission) {
            return $permission;
        }
        return Permission::where('key', $permission)->firstOrFail();
    }
}
