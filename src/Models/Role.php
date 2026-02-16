<?php

namespace Fazzinipierluigi\JustAGate\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    public function users() { return $this->belongsToMany(\App\Models\User::class); }
    public function permissions() { return $this->belongsToMany(\Fazzinipierluigi\JustAGate\Models\Permission::class); }
}
