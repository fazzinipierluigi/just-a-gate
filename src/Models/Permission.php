<?php

namespace Fazzinipierluigi\JustAGate\Models;

use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    protected $guarded = [];

    public function roles()
    {
        return $this->belongsToMany(Role::class);
    }

    public static function findByKey($key = null): ?self
    {
        if (empty($key) || !is_string($key)) {
            return null;
        }

        return self::where('key', '=', $key)->first() ?? null;
    }
}
