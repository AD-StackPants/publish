<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $organization_id
 * @property string $provider
 * @property string|null $customer_id
 * @property string|null $subscription_id
 * @property string $status
 * @property Carbon $current_period_start
 * @property Carbon $current_period_end
 * @property bool $cancel_at_period_end
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
final class Subscription extends Model
{
    use HasUuids;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'organization_id',
        'provider',
        'customer_id',
        'subscription_id',
        'status',
        'current_period_start',
        'current_period_end',
        'cancel_at_period_end',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'current_period_start' => 'datetime',
            'current_period_end' => 'datetime',
            'cancel_at_period_end' => 'boolean',
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
     * @return HasMany<SubscriptionItem, $this>
     */
    public function subscriptionItems(): HasMany
    {
        return $this->hasMany(SubscriptionItem::class);
    }

    /**
     * Alias for subscriptionItems
     *
     * @return HasMany<SubscriptionItem, $this>
     */
    public function items(): HasMany
    {
        return $this->subscriptionItems();
    }

    /**
     * @return HasMany<Invoice, $this>
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * Resolves related Plan where type === 'base_plan'.
     */
    public function basePlan(): ?Plan
    {
        /** @var SubscriptionItem|null $item */
        $item = $this->subscriptionItems()
            ->whereHas('plan', fn ($query) => $query->where('type', 'base_plan'))
            ->with('plan')
            ->first();

        return $item?->plan;
    }

    /**
     * Returns Collection of SubscriptionItem instances where plan.type === 'addon'.
     *
     * @return Collection<int, SubscriptionItem>
     */
    public function activeAddons(): Collection
    {
        return $this->subscriptionItems()
            ->whereHas('plan', fn ($query) => $query->where('type', 'addon'))
            ->with('plan')
            ->get();
    }

    /**
     * Computes base plan channel entitlement + sum(addon_quantity * 3).
     */
    public function totalChannelsAllowed(): int
    {
        $basePlan = $this->basePlan();
        $features = is_array($basePlan?->features) ? $basePlan->features : [];
        $baseLimit = (int) ($features['channels_limit'] ?? 1);

        $addonsCount = (int) $this->activeAddons()->sum(function (SubscriptionItem $item): int {
            $plan = $item->plan;
            $features = is_array($plan?->features) ? $plan->features : [];
            $channelsPerAddon = (int) ($features['channels_limit'] ?? 3);

            return (int) $item->quantity * $channelsPerAddon;
        });

        return $baseLimit + $addonsCount;
    }
}
