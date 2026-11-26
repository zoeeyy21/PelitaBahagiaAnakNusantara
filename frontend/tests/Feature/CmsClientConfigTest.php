<?php

namespace Tests\Feature;

use App\Services\CmsClient;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CmsClientConfigTest extends TestCase
{
    public function test_asset_url_uses_configured_cms_asset_url(): void
    {
        config(['cms.asset_url' => 'https://cms.example.test/storage/']);

        $this->assertSame(
            'https://cms.example.test/storage/qris/abc.jpg',
            CmsClient::assetUrl('qris/abc.jpg'),
        );
    }

    public function test_get_fetches_from_configured_cms_api_url(): void
    {
        config(['cms.api_url' => 'https://cms.example.test/api', 'cms.cache_ttl' => 0]);

        Http::fake([
            'cms.example.test/api/site' => Http::response(['data' => ['qris_image' => 'qris/abc.jpg']]),
        ]);

        $site = CmsClient::get('site', 'site_test', fn () => ['qris_image' => null]);

        $this->assertSame('qris/abc.jpg', $site['qris_image']);
    }
}
