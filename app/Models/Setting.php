<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One owner-editable override of a value in config/gtrack.php.
 * Read it through App\Support\ShopSettings, which falls back to the config.
 */
class Setting extends Model
{
    protected $fillable = ['key', 'value', 'updated_by'];

    protected $casts = [
        'value' => 'array',
    ];

    public static function put(string $key, mixed $value, ?int $userId): self
    {
        return static::query()->updateOrCreate(
            ['key' => $key],
            ['value' => $value, 'updated_by' => $userId],
        );
    }

    public static function forget(string $key): void
    {
        static::query()->where('key', $key)->delete();
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
