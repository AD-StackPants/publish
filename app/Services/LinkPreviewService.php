<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Throwable;

final class LinkPreviewService
{
    /**
     * @return array{url: string, title: string, description: string, image: string, image_url: string, domain: string}
     */
    public function preview(string $url): array
    {
        $domain = parse_url($url, PHP_URL_HOST) ?: 'example.com';
        $title = "Preview for {$domain}";
        $description = "Extracted link preview content for {$domain}.";
        $image = 'https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe';

        try {
            $response = Http::timeout(3)
                ->withHeaders(['User-Agent' => 'Posexei-Bot/1.0 (+https://posexei.io)'])
                ->get($url);

            if ($response->successful()) {
                $html = $response->body();

                // Extract OpenGraph tags
                if (preg_match('/<meta[^>]+property=["\']og:title["\'][^>]+content=["\']([^"\']+)["\']/i', $html, $matches)) {
                    $title = html_entity_decode($matches[1], ENT_QUOTES, 'UTF-8');
                } elseif (preg_match('/<title[^>]*>([^<]+)<\/title>/i', $html, $matches)) {
                    $title = html_entity_decode($matches[1], ENT_QUOTES, 'UTF-8');
                }

                if (preg_match('/<meta[^>]+property=["\']og:description["\'][^>]+content=["\']([^"\']+)["\']/i', $html, $matches)) {
                    $description = html_entity_decode($matches[1], ENT_QUOTES, 'UTF-8');
                } elseif (preg_match('/<meta[^>]+name=["\']description["\'][^>]+content=["\']([^"\']+)["\']/i', $html, $matches)) {
                    $description = html_entity_decode($matches[1], ENT_QUOTES, 'UTF-8');
                }

                if (preg_match('/<meta[^>]+property=["\']og:image["\'][^>]+content=["\']([^"\']+)["\']/i', $html, $matches)) {
                    $image = html_entity_decode($matches[1], ENT_QUOTES, 'UTF-8');
                }
            }
        } catch (Throwable) {
            // Graceful fallback on connection/timeout errors
        }

        return [
            'url' => $url,
            'title' => $title,
            'description' => $description,
            'image' => $image,
            'image_url' => $image,
            'domain' => $domain,
        ];
    }
}
