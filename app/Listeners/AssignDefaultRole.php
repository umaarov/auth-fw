<?php

namespace App\Listeners;

use App\Events\UserRegistered;
use Spatie\Permission\Models\Role;

class AssignDefaultRole
{
    public function handle(UserRegistered $event): void
    {
        $role = Role::firstOrCreate(['name' => 'user']);
        $event->user->assignRole($role);
    }
}
