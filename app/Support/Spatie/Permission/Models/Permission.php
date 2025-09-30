<?php

namespace Spatie\Permission\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'guard_name',
    ];

    public static function firstOrCreate(array $attributes, array $values = []): self
    {
        $permission = static::query()->where($attributes)->first();

        if ($permission) {
            return $permission;
        }

        return tap(new static(array_merge($attributes, $values)), function (self $model): void {
            $model->save();
        });
    }
}
