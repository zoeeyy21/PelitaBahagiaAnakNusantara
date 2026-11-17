<?php

use App\Services\CmsClient;

if (! function_exists('cms_asset')) {
    /**
     * Dapatkan URL aset dari CMS Backend Pelita Bahagia.
     */
    function cms_asset(?string $path): string
    {
        return CmsClient::assetUrl($path);
    }
}

