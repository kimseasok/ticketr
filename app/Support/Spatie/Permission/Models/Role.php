<?php

namespace Spatie\Permission\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Role extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'guard_name',
    ];

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'role_has_permissions');
    }

    public function syncPermissions(array $permissions): void
    {
        $permissionIds = collect($permissions)
            ->map(fn ($permission) => $permission instanceof Permission
                ? $permission->getKey()
                : Permission::firstOrCreate([
                    'name' => $permission,
                    'guard_name' => $this->guard_name ?? 'web',
                ])->getKey())
            ->all();

        $this->permissions()->sync($permissionIds);
    }

    public static function firstOrCreate(array $attributes, array $values = []): self
    {
        $role = static::query()->where($attributes)->first();

        if ($role) {
            return $role;
        }

        return tap(new static(array_merge($attributes, $values)), function (self $model): void {
            $model->save();
        });
    }
}
