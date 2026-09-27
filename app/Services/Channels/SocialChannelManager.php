<?php

declare(strict_types=1);

namespace App\Services\Channels;

use App\Services\Channels\Contracts\SocialChannelInterface;
use InvalidArgumentException;

final class SocialChannelManager
{
    /**
     * @var array<string, SocialChannelInterface>
     */
    private array $channels = [];

    public function __construct(
        FacebookChannelService $facebook,
        TwitterChannelService $twitter,
        LinkedInChannelService $linkedin,
    ) {
        $this->register($facebook);
        $this->register($twitter);
        $this->register($linkedin);
    }

    public function register(SocialChannelInterface $channel): void
    {
        $this->channels[strtolower($channel->provider())] = $channel;
    }

    public function channel(string $provider): SocialChannelInterface
    {
        $key = strtolower($provider);

        if (! isset($this->channels[$key])) {
            throw new InvalidArgumentException("Unsupported social channel provider: [{$provider}]");
        }

        return $this->channels[$key];
    }

    /**
     * @return list<string>
     */
    public function supportedProviders(): array
    {
        return array_keys($this->channels);
    }
}
