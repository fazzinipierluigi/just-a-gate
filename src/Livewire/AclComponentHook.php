<?php

namespace Fazzinipierluigi\JustAGate\Livewire;

use Fazzinipierluigi\JustAGate\Attributes\RequiresPermission;
use Illuminate\Support\Facades\Auth;
use Livewire\ComponentHook;

/**
 * Livewire 3 ComponentHook that enforces #[RequiresPermission] on components and methods.
 *
 * Registered automatically by JustAGateServiceProvider when livewire/livewire is installed.
 *
 * Class-level check  → runs on boot() — every lifecycle (initial + AJAX updates).
 * Method-level check → runs on call() — only when that action is dispatched.
 *
 * Both checks abort(403) when the authenticated user lacks the required permission,
 * or when no user is authenticated.
 */
class AclComponentHook extends ComponentHook
{
    public function boot(): void
    {
        $permission = $this->classPermission();

        if ($permission === null) {
            return;
        }

        $this->authorizeOrAbort($permission);
    }

    public function call($method, $params, $returnEarly): void
    {
        $permission = $this->methodPermission($method);

        if ($permission === null) {
            return;
        }

        $this->authorizeOrAbort($permission);
    }

    // ── internals ─────────────────────────────────────────────────────────────

    private function classPermission(): ?string
    {
        $attributes = (new \ReflectionClass($this->component))
            ->getAttributes(RequiresPermission::class);

        return $attributes ? $attributes[0]->newInstance()->permission : null;
    }

    private function methodPermission(string $method): ?string
    {
        try {
            $reflection = new \ReflectionMethod($this->component, $method);
        } catch (\ReflectionException) {
            return null;
        }

        $attributes = $reflection->getAttributes(RequiresPermission::class);

        return $attributes ? $attributes[0]->newInstance()->permission : null;
    }

    private function authorizeOrAbort(string $permission): void
    {
        $user = Auth::user();

        if ($user === null || !$user->can($permission)) {
            abort(403);
        }
    }
}
