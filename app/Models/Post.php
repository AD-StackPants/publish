<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $organization_id
 * @property string $content
 * @property array<string, mixed>|null $platform_overrides
 * @property string|null $media_url
 * @property array<string, mixed>|null $link_metadata
 * @property string $status
 * @property Carbon|null $scheduled_at
 * @property string $idempotency_key
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
final class Post extends Model
{
    use HasUuids;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'organization_id',
        'content',
        'platform_overrides',
        'media_url',
        'link_metadata',
        'status',
        'scheduled_at',
        'idempotency_key',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'platform_overrides' => 'array',
            'link_metadata' => 'array',
            'scheduled_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Organization, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * @return HasMany<PostCheckpoint, $this>
     */
    public function checkpoints(): HasMany
    {
        return $this->hasMany(PostCheckpoint::class);
    }
}
