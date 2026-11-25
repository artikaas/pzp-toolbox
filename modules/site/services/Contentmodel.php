<?php

namespace modules\site\services;

use RuntimeException;
use Symfony\Component\Yaml\Yaml;

/**
 * Leest contentmodel.yaml en zet de korte notatie om naar een vaste structuur.
 *
 * Layoutelement (string):  'kop', 'kop!', 'kop | Label', 'kop! | Label', 'title | Label'
 * wordt:                    ['handle' => 'kop', 'verplicht' => bool, 'label' => ?string]
 */
class Contentmodel
{
    public array $plugins = [];
    public array $ckeditor = [];
    /** @var array<string,string> handle => naam */
    public array $volumes = [];
    /** @var array<string,array> handle => definitie */
    public array $velden = [];
    /** @var array<string,array> handle => ['naam','icoon','titel','tabs' => [tab => [elementen]]] */
    public array $typen = [];
    /** @var string[] handles van de blokken, in volgorde */
    public array $blokken = [];
    public array $secties = [];
    public array $globals = [];

    public static function laden(string $pad): self
    {
        if (!is_file($pad)) {
            throw new RuntimeException("Contentmodel niet gevonden: $pad");
        }
        $data = Yaml::parseFile($pad) ?? [];
        $m = new self();
        $m->plugins = $data['plugins'] ?? [];
        $m->ckeditor = $data['ckeditor'] ?? [];
        $m->volumes = $data['volumes'] ?? [];
        $m->velden = $data['velden'] ?? [];
        $m->secties = $data['secties'] ?? [];

        foreach ($data['typen'] ?? [] as $handle => $def) {
            $m->typen[$handle] = $m->type($handle, $def);
        }
        foreach ($data['blokken'] ?? [] as $handle => $def) {
            if (isset($m->typen[$handle])) {
                throw new RuntimeException("'$handle' staat zowel bij typen als bij blokken.");
            }
            $m->typen[$handle] = $m->type($handle, $def);
            $m->blokken[] = $handle;
        }
        // Het Matrix-veld 'blokken' met alle blokken, tenzij het zelf is gedefinieerd.
        if ($m->blokken && !isset($m->velden['blokken'])) {
            $m->velden['blokken'] = ['soort' => 'matrix', 'naam' => 'Blokken', 'typen' => $m->blokken, 'knop' => 'Blok toevoegen'];
        }
        foreach ($data['globals'] ?? [] as $handle => $def) {
            $m->globals[$handle] = [
                'naam' => $def['naam'] ?? ucfirst($handle),
                'tabs' => array_map(fn($els) => array_map([self::class, 'element'], $els ?? []), $def['tabs'] ?? []),
            ];
        }
        $m->controleer();
        return $m;
    }

    private function type(string $handle, array $def): array
    {
        $tabs = $def['tabs'] ?? ['Inhoud' => $def['velden'] ?? []];
        $tabs = array_map(fn($els) => array_map([self::class, 'element'], $els ?? []), $tabs);
        $heeftTitel = false;
        foreach ($tabs as $els) {
            foreach ($els as $el) {
                $heeftTitel = $heeftTitel || $el['handle'] === 'title';
            }
        }
        return [
            'naam' => $def['naam'] ?? ucfirst($handle),
            'icoon' => $def['icoon'] ?? null,
            // Een type heeft een titelveld als 'title' in de layout staat (of titel: true).
            'titel' => (bool)($def['titel'] ?? $heeftTitel),
            'tabs' => $tabs,
        ];
    }

    public static function element(string|array $el): array
    {
        if (is_array($el)) {
            // Uitgebreide notatie: { veld: kop, label: ..., verplicht: true }
            return ['handle' => $el['veld'], 'verplicht' => (bool)($el['verplicht'] ?? false), 'label' => $el['label'] ?? null];
        }
        $delen = array_map('trim', explode('|', $el, 2));
        $handle = $delen[0];
        $verplicht = str_ends_with($handle, '!');
        return [
            'handle' => rtrim($handle, '!'),
            'verplicht' => $verplicht,
            'label' => ($delen[1] ?? '') !== '' ? $delen[1] : null,
        ];
    }

    /** Vangt tikfouten vroeg af: elk veld en type waarnaar verwezen wordt, moet bestaan. */
    private function controleer(): void
    {
        $fouten = [];
        $check = function (array $tabs, string $waar) use (&$fouten) {
            foreach ($tabs as $els) {
                foreach ($els as $el) {
                    if ($el['handle'] !== 'title' && !isset($this->velden[$el['handle']])) {
                        $fouten[] = "$waar: veld '{$el['handle']}' bestaat niet onder velden";
                    }
                }
            }
        };
        foreach ($this->typen as $h => $t) {
            $check($t['tabs'], "type $h");
        }
        foreach ($this->globals as $h => $g) {
            $check($g['tabs'], "global $h");
        }
        foreach ($this->velden as $h => $v) {
            if (!isset($v['soort'])) {
                $fouten[] = "veld $h: 'soort' ontbreekt";
            }
            if ($h === 'link') {
                $fouten[] = "veld link: 'link' is gereserveerd in Craft, gebruik bijv. 'linkDoel'";
            }
            if (($v['soort'] ?? null) === 'matrix') {
                foreach ($v['typen'] ?? [] as $t) {
                    if (!isset($this->typen[$t])) {
                        $fouten[] = "matrix $h: type '$t' bestaat niet";
                    }
                }
            }
            if (($v['soort'] ?? null) === 'beeld' && !isset($this->volumes[$v['volume'] ?? ''])) {
                $fouten[] = "veld $h: volume '" . ($v['volume'] ?? '') . "' bestaat niet";
            }
            foreach ($v['bronnen'] ?? [] as $s) {
                if (!isset($this->secties[$s])) {
                    $fouten[] = "veld $h: sectie '$s' bestaat niet";
                }
            }
        }
        foreach ($this->secties as $h => $s) {
            foreach ($s['typen'] ?? [] as $t) {
                if (!isset($this->typen[$t])) {
                    $fouten[] = "sectie $h: type '$t' bestaat niet";
                }
            }
        }
        if ($fouten) {
            throw new RuntimeException("Fouten in contentmodel.yaml:\n- " . implode("\n- ", $fouten));
        }
    }
}
