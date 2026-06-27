<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

trait HasPublicId
{
    public static function bootHasPublicId(): void
    {
        static::creating(function (Model $model): void {
            if (blank($model->getAttribute('public_id'))) {
                $model->setAttribute('public_id', static::newUniquePublicId());
            }
        });

        static::saving(function (Model $model): void {
            if ($model->exists && $model->isDirty('public_id')) {
                $model->setAttribute('public_id', $model->getOriginal('public_id'));
            }
        });
    }

    protected static function newUniquePublicId(): string
    {
        do {
            $publicId = (string) Str::uuid();
        } while (static::query()->where('public_id', $publicId)->exists());

        return $publicId;
    }
}
