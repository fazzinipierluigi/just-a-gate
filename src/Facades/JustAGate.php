<?php

namespace Fazzinipierluigi\JustAGate\Facades;

use Fazzinipierluigi\JustAGate\Models\Permission;
use Fazzinipierluigi\JustAGate\Models\Role;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Facade;

/**
 * @method static Role           createRole(string $name, string $slug, bool $isAdmin = false, bool $isSystem = false)
 * @method static Role|null      findRole(string $slug)
 * @method static Collection     allRoles()
 * @method static bool           deleteRole(Role|string $role)
 *
 * @method static Permission     createPermission(string $key, ?string $name = null, ?string $description = null)
 * @method static Permission|null findPermission(string $key)
 * @method static Collection     allPermissions()
 * @method static bool           deletePermission(Permission|string $permission)
 *
 * @method static void           assignRole(Model $user, Role|string ...$roles)
 * @method static void           removeRole(Model $user, Role|string ...$roles)
 * @method static void           syncRoles(Model $user, array $roles)
 *
 * @method static void           givePermission(Role|string $role, Permission|string ...$permissions)
 * @method static void           revokePermission(Role|string $role, Permission|string ...$permissions)
 * @method static void           syncPermissions(Role|string $role, array $permissions)
 *
 * @method static bool           userCan(Model $user, string $ability)
 * @method static bool           userHasRole(Model $user, Role|string $role)
 * @method static bool           roleHasPermission(Role|string $role, string $key)
 *
 * @method static Collection     usersWithRole(Role|string $role)
 * @method static Collection     rolesForPermission(Permission|string $permission)
 *
 * @see \Fazzinipierluigi\JustAGate\JustAGate
 */
class JustAGate extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'just_a_gate';
    }
}
