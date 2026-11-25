<?php

namespace modules\site\twig;

use Craft;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;
use Twig\TwigFunction;

/**
 * Twig-functie vite('src/js/app.js'): zet de <link>- en <script>-tags uit web/dist/.vite/manifest.json in de pagina.
 * Bouw de bundel met `npm run build` (of `npm run dev` voor bouwen bij elke wijziging).
 * Daarnaast de globale variabele `prototype` (false), zodat templates weten dat ze in Craft draaien.
 */
class Extension extends AbstractExtension implements GlobalsInterface
{
    /** In Craft is 'prototype' altijd false; de statische prototype-build zet hem op true. */
    public function getGlobals(): array
    {
        return ['prototype' => false];
    }

    private ?array $manifest = null;

    public function getFunctions(): array
    {
        return [
            new TwigFunction('vite', [$this, 'vite'], ['is_safe' => ['html']]),
        ];
    }

    public function vite(string $entry): string
    {
        $path = Craft::getAlias('@webroot/dist/.vite/manifest.json');
        if (!is_file($path)) {
            return Craft::$app->getConfig()->getGeneral()->devMode
                ? '<!-- vite: geen manifest gevonden, draai `npm run build` -->'
                : '';
        }

        $this->manifest ??= json_decode((string)file_get_contents($path), true) ?: [];
        $chunk = $this->manifest[$entry] ?? null;
        if (!$chunk) {
            return '';
        }

        $base = '/dist/';
        $tags = [];
        foreach ($chunk['css'] ?? [] as $css) {
            $tags[] = sprintf('<link rel="stylesheet" href="%s%s">', $base, $css);
        }
        $tags[] = sprintf('<script type="module" src="%s%s"></script>', $base, $chunk['file']);

        return implode("\n  ", $tags);
    }
}
