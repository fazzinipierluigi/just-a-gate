<?php

namespace Fazzinipierluigi\JustAGate\Traits;

use Fazzinipierluigi\JustAGate\JustAGate;

trait Authorizable
{
    private static $justgate_authorizations = NULL;
    private static $just_gate_is_admin = FALSE;

    public function roles() { return $this->belongsToMany(\Fazzinipierluigi\JustAGate\Models\Role::class); }

    public function can($ability, $arguments = [])
    {
        if(empty(self::$justgate_authorizations) && self::$just_gate_is_admin == FALSE)
        {
            $roles = $this->roles;
            if($roles->isNotEmpty())
            {
                self::$justgate_authorizations = [];
                foreach($roles as $role)
                {
                    $authorizations = $role->permissions;
                    self::$justgate_authorizations = array_merge(self::$justgate_authorizations, $authorizations->pluck('key')->toArray());

                    if ($role->is_admin) {
                        self::$just_gate_is_admin = TRUE;
                        return TRUE;
                    }
                }

                self::$justgate_authorizations = array_unique(self::$justgate_authorizations);
            }
            else
            {
                return FALSE;
            }
        }

        return ( in_array($ability, self::$justgate_authorizations) || self::$just_gate_is_admin );
    }

    public function cannot($ability, $arguments = [])
    {
        return !$this->can($ability, $arguments);
    }
}
