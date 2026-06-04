# Just A Gate

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Total Downloads][ico-downloads]][link-downloads]

A simple, flexible, and powerful ACL (Access Control List) system for Laravel 10/11/12.

Roles are assigned to users. Permissions are assigned to roles. A middleware auto-checks permissions based on the route's controller action. A fluent PHP API and a Facade cover every operation without writing SQL.

---

## Table of Contents

- [Requirements](#requirements)
- [Installation](#installation)
- [Configuration](#configuration)
- [Database Schema](#database-schema)
- [Quick Start](#quick-start)
- [Artisan Commands](#artisan-commands)
- [Protecting Routes — Middleware](#protecting-routes--middleware)
- [Authorizable Trait](#authorizable-trait)
- [Role Model](#role-model)
- [Permission Model](#permission-model)
- [JustAGate Facade](#justAgate-facade)
- [Blade Directives](#blade-directives)
- [Livewire Integration](#livewire-integration)
- [Testing](#testing)
- [Security](#security)
- [License](#license)

---

## Requirements

| Dependency | Version |
|---|---|
| PHP | `^8.1` |
| Laravel | `^10.0 \| ^11.0 \| ^12.0 \| ^13.0` |
| `illuminate/support` | `^10.0 \| ^11.0 \| ^12.0 \| ^13.0` |
| `laravel/prompts` | `^0.3` |

> **Note on PHP version** — Laravel 10/11/12 require PHP 8.1/8.2; Laravel 13 requires PHP 8.3.
> The package runtime is compatible with PHP 8.1+. Your actual minimum PHP version is determined
> by the Laravel version you install.

---

## Installation

### 1. Require the package

```bash
composer require fazzinipierluigi/just-a-gate
```

### 2. Add the `Authorizable` trait to your User model

```php
<?php

namespace App\Models;

use Fazzinipierluigi\JustAGate\Traits\Authorizable;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use Authorizable;
}
```

> **Note** — The trait overrides Laravel's built-in `can()` / `cannot()` methods and replaces Gate/Policy
> resolution with a lightweight DB-backed permission check. Do not use this package alongside
> Laravel Policies on the same User model.

### 3. Publish the configuration file

```bash
php artisan vendor:publish --provider="Fazzinipierluigi\JustAGate\JustAGateServiceProvider"
```

This creates `config/acl.php` in your application.

### 4. Initialize the package

```bash
php artisan permission:init
```

This command:
- Runs the four package migrations (creates `roles`, `permissions`, `permission_role`, `role_user` tables).
- Creates the built-in **Administrator** role (`slug: admin`, `is_admin: true`, `is_system: true`).

---

## Configuration

File: `config/acl.php`

```php
<?php

return [

    /*
    | Name used to register the ACL middleware.
    | Apply it to routes with Route::middleware('acl').
    | Type: string
    | Default: 'acl'
    */
    'middleware' => 'acl',

    /*
    | Extra permissions created during `permission:import`.
    | Format: 'permission_key' => 'Display name'
    | Type: array<string, string>
    | Default: []
    */
    'additional' => [
        // 'reports.export' => 'Export reports',
    ],

    /*
    | When true, `permission:import` auto-generates one permission per existing role,
    | with key "user.create.role_{slug}". Useful for controlling which roles a user
    | is allowed to assign during registration/admin flows.
    | Type: bool
    | Default: true
    */
    'role_user_creation' => true,

    /*
    | When true, `permission:import` deletes permissions that no longer correspond
    | to any route protected by the 'acl' middleware or any 'additional' entry.
    | Type: bool
    | Default: true
    */
    'clean_permission' => true,

    /*
    | Maps permission keys to human-readable names, applied during `permission:import`.
    | Useful for overriding auto-generated names.
    | Format: 'permission_key' => 'Display name'
    | Type: array<string, string>
    | Default: []
    */
    'translate' => [
        // 'user.index' => 'View user list',
    ],

    /*
    | Auto-assigns permissions to roles during `permission:import`.
    | Format: 'permission_key' => ['role_slug_1', 'role_slug_2']
    | Type: array<string, list<string>>
    | Default: []
    */
    'assign' => [
        // 'user.index' => ['admin', 'moderator'],
    ],

];
```

---

## Database Schema

### Tables

```
roles
├── id                bigint unsigned  PK
├── name              varchar          Human-readable label
├── slug              varchar          Unique identifier used in code  (indexed)
├── is_admin          boolean          Grants access to every permission when true
├── is_system         boolean          Marks built-in roles (prevents accidental deletion in UI)
├── created_at        timestamp
└── updated_at        timestamp

permissions
├── id                bigint unsigned  PK
├── key               varchar          Dot-notation identifier, e.g. "user.index"  (indexed)
├── name              varchar nullable Human-readable label
├── description       text    nullable Extended description
├── created_at        timestamp
└── updated_at        timestamp

permission_role   (pivot)
├── id                bigint unsigned  PK
├── permission_id     FK → permissions.id  ON DELETE CASCADE
└── role_id           FK → roles.id        ON DELETE CASCADE

role_user         (pivot)
├── id                bigint unsigned  PK
├── role_id           FK → roles.id        ON DELETE CASCADE
└── user_id           FK → users.id        ON DELETE CASCADE
```

### Relationships

```
User  ──< role_user >──  Role  ──< permission_role >──  Permission
```

- A user can have **many roles**.
- A role can have **many permissions**.
- A role can belong to **many users**.
- A permission can belong to **many roles**.

---

## Quick Start

```php
use Fazzinipierluigi\JustAGate\Facades\JustAGate;

// 1. Create roles
$admin  = JustAGate::createRole('Administrator', 'admin', isAdmin: true);
$editor = JustAGate::createRole('Editor', 'editor');

// 2. Create permissions
JustAGate::createPermission('post.index',   'View posts');
JustAGate::createPermission('post.create',  'Create posts');
JustAGate::createPermission('post.destroy', 'Delete posts');

// 3. Assign permissions to roles
JustAGate::givePermission('editor', 'post.index', 'post.create');
// admin has is_admin=true, so it bypasses all permission checks automatically

// 4. Assign roles to users
$user = User::find(1);
JustAGate::assignRole($user, 'editor');

// 5. Check access
$user->can('post.index');   // true
$user->can('post.destroy'); // false
$user->hasRole('editor');   // true
```

---

## Artisan Commands

### `permission:init`

Initializes the package on first use.

```bash
php artisan permission:init
```

- Runs pending package migrations.
- Creates the `admin` role if it does not exist.

---

### `permission:create`

Creates a single permission.

```bash
php artisan permission:create {key?} {name?}
```

| Argument | Type | Required | Description |
|---|---|---|---|
| `key` | string | No | Dot-notation key, e.g. `post.index`. Interactive prompt if omitted. |
| `name` | string | No | Human-readable display name. |

```bash
php artisan permission:create post.index "View posts"
php artisan permission:create   # launches interactive prompt
```

Exit codes: `0` = success, `1` = key already exists.

---

### `permission:assign`

Assigns a permission to a role.

```bash
php artisan permission:assign {key?} {role?}
```

| Argument | Type | Required | Description |
|---|---|---|---|
| `key`  | string | No | Permission key. Interactive select if omitted. |
| `role` | string | No | Role slug. Interactive select if omitted. |

```bash
php artisan permission:assign post.index editor
php artisan permission:assign   # launches interactive selects
```

Exit codes: `0` = success, `1` = not found or already assigned.

---

### `permission:import`

Bulk-imports permissions from routes, config, and roles. Intended to be run after deploying route or config changes.

```bash
php artisan permission:import
```

**What it does (in order):**

1. Creates permissions from `config('acl.additional')`.
2. Scans all routes protected by the `acl` middleware and creates one permission per route using the [key derivation](#permission-key-derivation) formula.
3. If `config('acl.role_user_creation')` is `true`, creates `user.create.role_{slug}` for every existing role.
4. If `config('acl.clean_permission')` is `true`, deletes permissions that are no longer present in steps 1–3.
5. Applies name translations from `config('acl.translate')`.
6. Auto-assigns permissions to roles from `config('acl.assign')`.

---

## Protecting Routes — Middleware

### Applying the middleware

```php
// Single route
Route::get('/posts', [PostController::class, 'index'])->middleware('acl');

// Group
Route::middleware('acl')->group(function () {
    Route::get('/posts',         [PostController::class, 'index']);
    Route::post('/posts',        [PostController::class, 'store']);
    Route::delete('/posts/{id}', [PostController::class, 'destroy']);
});
```

The middleware alias defaults to `'acl'`. Override it in `config/acl.php` under `'middleware'`.

### Permission key derivation

The middleware (and `permission:import`) derives the required permission key from the route's controller action using this algorithm:

```
App\Http\Controllers\PostController@index
  └─ explode('\')     → ['App', 'Http', 'Controllers', 'PostController@index']
  └─ take last        → 'PostController@index'
  └─ replace 'Controller@' with '.'  → 'Post.index'
  └─ strtolower()     → 'post.index'
```

| Controller action | Derived permission key |
|---|---|
| `PostController@index`   | `post.index`   |
| `PostController@store`   | `post.store`   |
| `PostController@destroy` | `post.destroy` |
| `UserController@show`    | `user.show`    |

### Unauthenticated users

> **Warning** — The middleware does not redirect unauthenticated users. It calls `Auth::user()->can(...)`,
> which will throw a `TypeError` if no user is authenticated. Always place `auth` middleware
> before `acl` on any route that requires authentication.

```php
Route::middleware(['auth', 'acl'])->group(function () {
    // ...
});
```

---

## Authorizable Trait

**Namespace:** `Fazzinipierluigi\JustAGate\Traits\Authorizable`

Add this trait to any Eloquent model that represents an authenticated user.

### Properties (internal cache)

| Property | Type | Description |
|---|---|---|
| `$justgate_authorizations` | `?array` | Per-instance cache of resolved permission keys. `null` = not yet loaded. |
| `$just_gate_is_admin` | `bool` | Per-instance flag set when the user holds an `is_admin` role. |

Cache is **instance-scoped** — two User instances never share state.

---

### `roles()`

```php
public function roles(): BelongsToMany
```

Eloquent relationship returning all roles assigned to the user.

```php
$user->roles;              // Collection<Role>
$user->roles()->count();   // int
```

---

### `can(string $ability, mixed $arguments = []): bool`

Returns `true` if the user holds a permission with the given key, or if the user has an `is_admin` role.

```php
$user->can('post.index');    // bool
$user->can('post.destroy');  // bool
```

**Caching:** on first call, all permissions for all roles are loaded and stored in the instance. Subsequent calls are in-memory only with no DB queries.

**Admin shortcut:** if any of the user's roles has `is_admin = true`, `can()` returns `true` immediately for any ability, and the admin flag is cached so no further DB lookups occur.

---

### `cannot(string $ability, mixed $arguments = []): bool`

```php
public function cannot(string $ability, mixed $arguments = []): bool
```

Inverse of `can()`.

```php
$user->cannot('post.destroy'); // bool
```

---

### `clearPermissionCache(): void`

```php
public function clearPermissionCache(): void
```

Resets the instance-level permission cache and unloads the `roles` Eloquent relation.
Call this after changing a user's roles programmatically within the same request.

```php
$user->assignRole('editor');
// cache already cleared automatically by assignRole()

// Manual clear if you modified pivot tables directly:
$user->clearPermissionCache();
```

---

### `assignRole(Role|string|int ...$roles): static`

```php
public function assignRole(Role|string|int ...$roles): static
```

Attaches one or more roles to the user. Accepts Role instances, slug strings, or IDs. Idempotent (duplicate assignments are ignored). Clears the permission cache automatically. Returns `$this` for chaining.

```php
$user->assignRole('editor');
$user->assignRole($roleA, $roleB);
$user->assignRole('editor', 'moderator');
$user->assignRole(3);
```

---

### `removeRole(Role|string|int ...$roles): static`

```php
public function removeRole(Role|string|int ...$roles): static
```

Detaches one or more roles from the user. Clears the permission cache automatically. Returns `$this` for chaining.

```php
$user->removeRole('editor');
$user->removeRole($roleA, $roleB);
```

---

### `syncRoles(array $roles): static`

```php
public function syncRoles(array $roles): static
```

Replaces all of the user's roles with the given set. Pass an empty array to remove all roles. Clears the permission cache automatically. Returns `$this` for chaining.

```php
$user->syncRoles(['editor', 'moderator']);
$user->syncRoles([]);  // removes all roles
```

---

### `hasRole(Role|string $role): bool`

```php
public function hasRole(Role|string $role): bool
```

Returns `true` if the user has the specified role. Accepts a Role instance or a slug string.

```php
$user->hasRole('admin');    // bool
$user->hasRole($roleObj);   // bool
```

---

### `hasAnyRole(Role|string ...$roles): bool`

```php
public function hasAnyRole(Role|string ...$roles): bool
```

Returns `true` if the user has **at least one** of the given roles.

```php
$user->hasAnyRole('editor', 'moderator', 'admin'); // bool
```

---

### `hasAllRoles(Role|string ...$roles): bool`

```php
public function hasAllRoles(Role|string ...$roles): bool
```

Returns `true` if the user has **every** given role. Returns `false` for an empty argument list.

```php
$user->hasAllRoles('editor', 'moderator'); // bool
```

---

### `getRoles(): Collection`

```php
public function getRoles(): \Illuminate\Database\Eloquent\Collection
```

Returns the Collection of Role models currently assigned to the user (uses Eloquent relation cache).

```php
$user->getRoles()->pluck('slug'); // Collection<string>
```

---

## Role Model

**Namespace:** `Fazzinipierluigi\JustAGate\Models\Role`  
**Table:** `roles`

### Properties

| Property | Type | Default | Description |
|---|---|---|---|
| `id` | `int` | — | Auto-increment primary key |
| `name` | `string` | — | Human-readable label, e.g. `"Editor"` |
| `slug` | `string` | — | Unique code identifier used in all API calls, e.g. `"editor"` |
| `is_admin` | `bool` | `false` | When `true`, users with this role pass every `can()` check |
| `is_system` | `bool` | `false` | Marks built-in roles (informational; no automatic behavior) |

`is_admin` and `is_system` are cast to `bool` automatically.

### Creating roles

```php
use Fazzinipierluigi\JustAGate\Models\Role;

// Direct model creation
$role = Role::create([
    'name'      => 'Editor',
    'slug'      => 'editor',
    'is_admin'  => false,
    'is_system' => false,
]);

// Via Facade (recommended)
$role = JustAGate::createRole('Editor', 'editor');
$admin = JustAGate::createRole('Administrator', 'admin', isAdmin: true, isSystem: true);
```

---

### `permissions()`

```php
public function permissions(): BelongsToMany
```

Eloquent relationship returning all permissions assigned to this role.

---

### `users()`

```php
public function users(): BelongsToMany
```

Eloquent relationship returning all users that have this role.

---

### `givePermission(Permission|string ...$permissions): static`

```php
public function givePermission(Permission|string ...$permissions): static
```

Attaches one or more permissions to the role. Accepts Permission instances or key strings. Idempotent. Unsets the `permissions` relation cache. Returns `$this` for chaining.

```php
$role->givePermission('post.index');
$role->givePermission('post.index', 'post.create', 'post.destroy');
$role->givePermission($permissionModel);
```

---

### `revokePermission(Permission|string ...$permissions): static`

```php
public function revokePermission(Permission|string ...$permissions): static
```

Detaches one or more permissions from the role. Returns `$this` for chaining.

```php
$role->revokePermission('post.destroy');
$role->revokePermission($permA, $permB);
```

---

### `syncPermissions(array $permissions): static`

```php
public function syncPermissions(array $permissions): static
```

Replaces all of the role's permissions with the given set. Returns `$this` for chaining.

```php
$role->syncPermissions(['post.index', 'post.create']);
$role->syncPermissions([]); // removes all permissions from this role
```

---

### `hasPermission(string $key): bool`

```php
public function hasPermission(string $key): bool
```

Returns `true` if this role has the permission identified by `$key`.

```php
$role->hasPermission('post.index'); // bool
```

---

### Method chaining example

```php
$role->givePermission('post.index', 'post.create')
     ->revokePermission('post.destroy');
```

---

## Permission Model

**Namespace:** `Fazzinipierluigi\JustAGate\Models\Permission`  
**Table:** `permissions`

### Properties

| Property | Type | Default | Description |
|---|---|---|---|
| `id` | `int` | — | Auto-increment primary key |
| `key` | `string` | — | Dot-notation identifier, always lowercase, e.g. `"post.index"` |
| `name` | `string\|null` | `null` | Human-readable label |
| `description` | `string\|null` | `null` | Extended description |

### Creating permissions

```php
use Fazzinipierluigi\JustAGate\Models\Permission;

// Direct model creation
$perm = Permission::create(['key' => 'post.index', 'name' => 'View posts']);

// Via Facade (recommended — also lowercases the key)
$perm = JustAGate::createPermission('post.index', 'View posts', 'Allows viewing the posts list');
```

---

### `findByKey(mixed $key): ?Permission` (static)

```php
public static function findByKey(mixed $key): ?Permission
```

Returns the Permission with the given key, or `null` if not found or if `$key` is not a non-empty string.

```php
Permission::findByKey('post.index');  // Permission|null
Permission::findByKey(null);          // null
Permission::findByKey('');            // null
Permission::findByKey(42);            // null
```

---

### `roles()`

```php
public function roles(): BelongsToMany
```

Eloquent relationship returning all roles that have this permission assigned.

```php
$permission->roles; // Collection<Role>
```

---

## JustAGate Facade

**Facade class:** `Fazzinipierluigi\JustAGate\Facades\JustAGate`  
**Alias:** `JustAGate` (registered automatically via Laravel's package auto-discovery)  
**Service class:** `Fazzinipierluigi\JustAGate\JustAGate` (bound as singleton `'just_a_gate'`)

The Facade provides a central, static-style API for every ACL operation without needing to inject models or use the trait directly.

```php
use Fazzinipierluigi\JustAGate\Facades\JustAGate;
```

---

### Role management

#### `createRole(string $name, string $slug, bool $isAdmin = false, bool $isSystem = false): Role`

```php
$role = JustAGate::createRole('Editor', 'editor');
$admin = JustAGate::createRole('Administrator', 'admin', isAdmin: true, isSystem: true);
```

#### `findRole(string $slug): ?Role`

```php
$role = JustAGate::findRole('editor'); // Role|null
```

#### `allRoles(): Collection`

```php
$roles = JustAGate::allRoles(); // Collection<Role>
```

#### `deleteRole(Role|string $role): bool`

```php
JustAGate::deleteRole('editor');   // by slug
JustAGate::deleteRole($roleObj);   // by instance
```

---

### Permission management

#### `createPermission(string $key, ?string $name = null, ?string $description = null): Permission`

Lowercases `$key` automatically.

```php
$perm = JustAGate::createPermission('post.index', 'View posts', 'Allows listing all posts');
```

#### `findPermission(string $key): ?Permission`

```php
$perm = JustAGate::findPermission('post.index'); // Permission|null
```

#### `allPermissions(): Collection`

```php
$perms = JustAGate::allPermissions(); // Collection<Permission>
```

#### `deletePermission(Permission|string $permission): bool`

```php
JustAGate::deletePermission('post.index');
JustAGate::deletePermission($permObj);
```

---

### User ↔ Role

#### `assignRole(Model $user, Role|string ...$roles): void`

```php
JustAGate::assignRole($user, 'editor');
JustAGate::assignRole($user, 'editor', 'moderator');
JustAGate::assignRole($user, $roleObj);
```

#### `removeRole(Model $user, Role|string ...$roles): void`

```php
JustAGate::removeRole($user, 'editor');
```

#### `syncRoles(Model $user, array $roles): void`

```php
JustAGate::syncRoles($user, ['editor', 'moderator']);
JustAGate::syncRoles($user, []);  // removes all roles
```

---

### Role ↔ Permission

#### `givePermission(Role|string $role, Permission|string ...$permissions): void`

```php
JustAGate::givePermission('editor', 'post.index', 'post.create');
JustAGate::givePermission($roleObj, $permObj);
```

#### `revokePermission(Role|string $role, Permission|string ...$permissions): void`

```php
JustAGate::revokePermission('editor', 'post.destroy');
```

#### `syncPermissions(Role|string $role, array $permissions): void`

```php
JustAGate::syncPermissions('editor', ['post.index', 'post.create']);
JustAGate::syncPermissions('editor', []); // removes all permissions from role
```

---

### Checks

#### `userCan(Model $user, string $ability): bool`

```php
JustAGate::userCan($user, 'post.index'); // bool
```

#### `userHasRole(Model $user, Role|string $role): bool`

```php
JustAGate::userHasRole($user, 'editor'); // bool
```

#### `roleHasPermission(Role|string $role, string $key): bool`

```php
JustAGate::roleHasPermission('editor', 'post.index'); // bool
```

---

### Discovery

#### `usersWithRole(Role|string $role): Collection`

Returns all users that have the given role.

```php
$editors = JustAGate::usersWithRole('editor'); // Collection<User>
```

#### `rolesForPermission(Permission|string $permission): Collection`

Returns all roles that have the given permission assigned.

```php
$roles = JustAGate::rolesForPermission('post.index'); // Collection<Role>
```

---

## Blade Directives

The package registers a `@can` / `@endcan` Blade directive backed by the package's permission system.

```blade
@can('post.index')
    <a href="{{ route('posts.index') }}">View Posts</a>
@endcan

@can('post.destroy')
    <form method="POST" action="{{ route('posts.destroy', $post) }}">
        @csrf @method('DELETE')
        <button type="submit">Delete</button>
    </form>
@endcan
```

> **Note** — The directive calls `Auth::user()->can($ability)`. If no user is authenticated,
> it will throw a `TypeError`. Guard the view with `@auth` when the user may not be logged in.

```blade
@auth
    @can('post.create')
        <a href="{{ route('posts.create') }}">New Post</a>
    @endcan
@endauth
```

---

## Livewire Integration

Livewire 3 routes all component updates through a single `/livewire/update` endpoint, making route-based ACL middleware ineffective. Just A Gate solves this with the `#[RequiresPermission]` PHP attribute and a Livewire 3 `ComponentHook` that enforces it automatically.

### How it works

- The hook is registered automatically when `livewire/livewire` is installed — no extra configuration needed.
- **Class-level** `#[RequiresPermission]` → checked on **every lifecycle** (initial mount + all subsequent AJAX updates).
- **Method-level** `#[RequiresPermission]` → checked only when **that specific Livewire action** is dispatched.
- Returns `HTTP 403` when the user is unauthenticated or lacks the required permission.

---

### Setup

No additional setup is required. Install Livewire and the hook self-registers:

```bash
composer require livewire/livewire
```

---

### Class-level permission (protect the entire component)

The user must hold the specified permission to mount **and** interact with the component at all.

```php
<?php

namespace App\Livewire;

use Fazzinipierluigi\JustAGate\Attributes\RequiresPermission;
use Livewire\Component;

#[RequiresPermission('post.index')]
class PostsList extends Component
{
    public function render()
    {
        return view('livewire.posts-list');
    }
}
```

---

### Method-level permission (protect individual actions)

The component is publicly visible, but specific actions require extra permissions.

```php
<?php

namespace App\Livewire;

use Fazzinipierluigi\JustAGate\Attributes\RequiresPermission;
use Livewire\Component;

class PostsList extends Component
{
    public function render()
    {
        return view('livewire.posts-list');
    }

    #[RequiresPermission('post.destroy')]
    public function delete(int $id): void
    {
        Post::findOrFail($id)->delete();
    }

    #[RequiresPermission('post.create')]
    public function create(array $data): void
    {
        Post::create($data);
    }
}
```

---

### Combining class-level and method-level

Both checks apply independently. A user must pass the class check on every request, **and** the method check when calling a specific action.

```php
#[RequiresPermission('post.index')]          // enforced on every request
class PostsList extends Component
{
    #[RequiresPermission('post.destroy')]    // additionally enforced on delete()
    public function delete(int $id): void
    {
        Post::findOrFail($id)->delete();
    }
}
```

---

### Full-page Livewire components (routed components)

For components mounted via a route, you can combine both the route middleware and the attribute for defense in depth:

```php
// routes/web.php
Route::middleware(['auth', 'acl'])->get('/posts', PostsList::class);
```

```php
// App\Livewire\PostsList.php
#[RequiresPermission('post.index')]
class PostsList extends Component { ... }
```

The `acl` middleware checks on initial HTTP load. The `#[RequiresPermission]` attribute re-checks on every Livewire AJAX update. This prevents a user whose role changes mid-session from continuing to interact with the component.

---

### Attribute reference

**Namespace:** `Fazzinipierluigi\JustAGate\Attributes\RequiresPermission`

```php
#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD)]
class RequiresPermission
{
    public function __construct(public readonly string $permission) {}
}
```

| Target | Behavior |
|---|---|
| `TARGET_CLASS` | Checked on every Livewire lifecycle (mount + updates) via `boot()` |
| `TARGET_METHOD` | Checked only when the attributed method is dispatched via `call()` |

---

### Hook reference

**Class:** `Fazzinipierluigi\JustAGate\Livewire\AclComponentHook`  
**Extends:** `Livewire\ComponentHook`  
**Registered:** automatically via `JustAGateServiceProvider` when `class_exists(\Livewire\Livewire::class)`

| Hook | Trigger | What it checks |
|---|---|---|
| `boot()` | Every Livewire request for this component | `#[RequiresPermission]` on the **class** |
| `call()` | Before a Livewire action method executes | `#[RequiresPermission]` on the **method** |

---

## Testing

```bash
composer test
# or
php artisan test
```

The test suite uses an in-memory SQLite database and covers:

- `Authorizable` trait: `can()`, `cannot()`, caching, static isolation, all role-management methods.
- `AclCheck` middleware: authenticated/unauthenticated requests, permission pass-through, admin bypass.
- `Role` model: CRUD, `givePermission`, `revokePermission`, `syncPermissions`, `hasPermission`, cascading deletes.
- `Permission` model: CRUD, `findByKey`, inverse `roles()` relationship, cascading deletes.
- `JustAGate` Facade: all 18 methods, Facade resolution.

---

## Security

If you discover a security-related issue please email **fazzinipierluigi@gmail.com** rather than using the public issue tracker.

---

## Credits

- [Pierluigi Fazzini][link-author]
- [All Contributors][link-contributors]

---

## License

MIT. See [LICENSE](LICENSE) for details.

---

[ico-version]: https://img.shields.io/packagist/v/fazzinipierluigi/just-a-gate.svg?style=flat-square
[ico-downloads]: https://img.shields.io/packagist/dt/fazzinipierluigi/just-a-gate.svg?style=flat-square

[link-packagist]: https://packagist.org/packages/fazzinipierluigi/just-a-gate
[link-downloads]: https://packagist.org/packages/fazzinipierluigi/just-a-gate
[link-author]: https://github.com/fazzinipierluigi
[link-contributors]: ../../contributors
