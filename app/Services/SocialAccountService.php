<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\SocialAccount;
use App\Services\Channels\SocialChannelManager;
use Illuminate\Database\Eloquent\Collection;

final class SocialAccountService
{
    public function __construct(
        private readonly SocialChannelManager $channelManager,
    ) {}

    /**
     * @return Collection<int, SocialAccount>
     */
    public function getAccountsForTenant(string $tenantId): Collection
    {
        return SocialAccount::query()
            ->where('organization_id', $tenantId)
            ->get();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function connectAccount(string $tenantId, array $data): SocialAccount
    {
        $provider = (string) $data['provider'];
        $name = (string) ($data['name'] ?? ucfirst($provider).' Account');
        $accountId = (string) ($data['account_id'] ?? "{$provider}_".time());
        $avatarUrl = isset($data['avatar_url']) ? (string) $data['avatar_url'] : null;

        // Optionally validate token if token is provided
        if (! empty($data['access_token']) && is_string($data['access_token'])) {
            $channel = $this->channelManager->channel($provider);
            $validation = $channel->validateToken($data['access_token'], $accountId);
            if ($validation->valid && $validation->accountName) {
                $name = $validation->accountName;
            }
        }

        /** @var SocialAccount $account */
        $account = SocialAccount::query()->create([
            'organization_id' => $tenantId,
            'provider' => $provider,
            'account_id' => $accountId,
            'name' => $name,
            'avatar_url' => $avatarUrl,
            'status' => 'healthy',
            'token_expires_at' => now()->addDays(90),
        ]);

        return $account;
    }

    public function disconnectAccount(string $tenantId, string $accountId): void
    {
        SocialAccount::query()
            ->where('organization_id', $tenantId)
            ->where('id', $accountId)
            ->firstOrFail()
            ->delete();
    }

    public function reconnectAccount(string $tenantId, string $accountId): SocialAccount
    {
        /** @var SocialAccount $account */
        $account = SocialAccount::query()
            ->where('organization_id', $tenantId)
            ->where('id', $accountId)
            ->firstOrFail();

        $account->update([
            'status' => 'healthy',
            'token_expires_at' => now()->addDays(90),
        ]);

        return $account;
    }
}
