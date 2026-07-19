<?php

namespace App\Validation;

use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\DatabasePresenceVerifier;

class TenantPresenceVerifier extends DatabasePresenceVerifier
{
    /** Scope exists/unique validation to the authenticated account. */
    protected function table($table)
    {
        $query = parent::table($table);

        if (Auth::check() && in_array($table, config('tenancy.tables', []), true)) {
            $query->where($table.'.owner_id', Auth::id());
        }

        return $query;
    }
}
