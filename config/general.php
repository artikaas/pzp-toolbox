<?php
/**
 * Algemene configuratie. Waarden per omgeving staan in .env.
 * @see craft\config\GeneralConfig
 */

use craft\config\GeneralConfig;
use craft\helpers\App;

return GeneralConfig::create()
    ->defaultWeekStartDay(1)
    ->omitScriptNameInUrls()
    ->preloadSingles()
    ->preventUserEnumeration()
    ->addTrailingSlashesToUrls()
    ->generateTransformsBeforePageLoad()
    ->defaultImageQuality(82)
    ->maxRevisions(30)
    ->aliases([
        '@webroot' => dirname(__DIR__) . '/web',
        '@web' => App::env('PRIMARY_SITE_URL'),
    ]);
