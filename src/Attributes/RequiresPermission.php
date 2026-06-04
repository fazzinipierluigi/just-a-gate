<?php

namespace Fazzinipierluigi\JustAGate\Attributes;

/**
 * Declares an ACL permission required to mount or call a Livewire component/method.
 *
 * On a class  → checked on every lifecycle (mount + subsequent updates).
 * On a method → checked only when that specific Livewire action is dispatched.
 *
 * Usage:
 *
 *   #[RequiresPermission('post.index')]
 *   class PostsList extends Component { ... }
 *
 *   class PostsList extends Component {
 *       #[RequiresPermission('post.destroy')]
 *       public function delete(int $id): void { ... }
 *   }
 */
#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD)]
class RequiresPermission
{
    public function __construct(public readonly string $permission) {}
}
