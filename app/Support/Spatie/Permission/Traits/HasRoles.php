<?php

namespace Spatie\Permission\Traits;

use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Collection;
use Spatie\Permission\Models\Role;

trait HasRoles
{
    public function roles(): MorphToMany
    {
        return $this->morphToMany(Role::class, 'model', 'model_has_roles', 'model_id', 'role_id');
    }

    public function permissions(): Collection
    {
        return $this->roles()->with('permissions')->get()->pluck('permissions')->flatten()->unique('id')->values();
    }

    public function assignRole(...$roles): self
    {
        $roles = collect($roles)->flatten();

        $roleModels = $roles->map(function ($role) {
            if ($role instanceof Role) {
                return $role;
            }

            return Role::firstOrCreate([
                'name' => (string) $role,
                'guard_name' => $this->getDefaultGuardName(),
            ]);
        });

        $this->roles()->syncWithoutDetaching($roleModels->pluck('id')->all());

        return $this;
    }

    public function hasRole(string $role): bool
    {
        return $this->roles()->where('name', $role)->exists();
    }

    protected function getDefaultGuardName(): string
    {
        return property_exists($this, 'guard_name') ? $this->guard_name : config('auth.defaults.guard', 'web');
    }
}
