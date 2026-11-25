<?php
/**
 * Applicatieconfiguratie: registreert de projectmodule 'site'
 * (setupcommando, Twig-functie vite(), afhandeling contactformulier).
 */

use craft\helpers\App;

return [
    'id' => App::env('CRAFT_APP_ID') ?: 'site',
    'modules' => [
        'site' => \modules\site\Module::class,
    ],
    'bootstrap' => ['site'],
];
