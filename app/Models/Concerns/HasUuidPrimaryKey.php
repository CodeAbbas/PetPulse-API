<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use Illuminate\Support\Str;

/**
 * Project-local UUID primary key trait.
 *
 * Wraps Laravel's native HasUuids behaviour to provide a single point of
 * customisation should the project later migrate to UUID v7 for improved
 * InnoDB index locality. All models with a UUID primary key MUST use this
 * trait rather than HasUuids directly, and MUST set the following properties:
 *
 *   protected $keyType = 'string';
 *   public $incrementing = false;
 *
 * Reference: AT2 §3.3 — UUID v4 primary keys (NFR-SEC-02).
 */
trait HasUuidPrimaryKey
{
    /**
     * Boot the trait — registers a creating-event hook that assigns
     * a UUID v4 to the primary key if not already set.
     */
    protected static function bootHasUuidPrimaryKey(): void
    {
        static::creating(function ($model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = (string) Str::uuid();
            }
        });
    }

    /**
     * Explicitly declare the primary-key type as a string.
     */
    public function getKeyType(): string
    {
        return 'string';
    }

    /**
     * Explicitly disable auto-incrementing.
     */
    public function getIncrementing(): bool
    {
        return false;
    }
}