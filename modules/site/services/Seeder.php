<?php

namespace modules\site\services;

use Craft;
use craft\base\ElementInterface;
use craft\elements\Asset;
use craft\elements\Entry;
use craft\elements\GlobalSet;
use craft\helpers\FileHelper;
use craft\models\Section;
use DateTime;
use RuntimeException;

/**
 * Laadt de startinhoud uit seed/content.json in Craft. Dezelfde JSON vult ook het prototype.
 *
 * {
 *   "assets":  [ { "key": "hero", "volume": "beelden", "file": "home/hero.jpg", "alt": "...", "focal": [0.5, 0.4] } ],
 *   "entries": [ { "sectie": "paginas", "type": "pagina", "slug": "over-ons", "titel": "Over ons",
 *                  "velden": { ... }, "kinderen": [ ... ], "datum": "2026-01-31" } ],
 *   "globals": { "footer": { ... } }
 * }
 *
 * Waarden in "velden":
 * - "@asset:<key>"                 asset uit "assets" (ook in een lijst: ["@asset:a", "@asset:b"])
 * - "@entry:<sectie>/<slug>"       entry; "@entry:home" = de homepage
 * - links: { "type": "entry", "entry": "paginas/contact", "label": "..." } of { "type": "url", "value": "https://...", "label": "..." }
 * - keuzes: de waarde ("primair")
 * - Matrix (ook blokken): lijst van { "type": "<entry type>", "titel"?: "...", "velden": { ... } }
 * - tabellen: lijst van rijen met de kolomhandles als sleutels
 *
 * Bestaande entries (zelfde sectie + slug) worden bijgewerkt, dus het commando kan opnieuw draaien.
 * Let op: het overschrijft wat er in Craft met de hand is aangepast in die entries en globals.
 */
class Seeder
{
    /** @var callable */
    private $log;

    private array $data;

    /** @var array<string,int> */
    private array $assets = [];

    private int $siteId;

    private string $seedPath;

    public function __construct(callable $log, string $seedPath)
    {
        $this->log = $log;
        $this->seedPath = rtrim($seedPath, '/');
        $json = @file_get_contents($this->seedPath . '/content.json');
        if ($json === false) {
            throw new RuntimeException('seed/content.json niet gevonden');
        }
        $this->data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        $this->siteId = Craft::$app->getSites()->getPrimarySite()->id;
    }

    public function run(): void
    {
        $this->importAssets();

        // Ronde 1: alle entries bestaan (titel, slug, plek in de structuur), zodat links en relaties kunnen.
        foreach ($this->data['entries'] ?? [] as $def) {
            $this->entry($def, null, false);
        }
        // Ronde 2: alle velden, blokken en links.
        foreach ($this->data['entries'] ?? [] as $def) {
            $this->entry($def, null, true);
        }

        foreach ($this->data['globals'] ?? [] as $handle => $fields) {
            $set = GlobalSet::find()->handle($handle)->one();
            if (!$set) {
                ($this->log)("  Let op: global $handle niet gevonden.");
                continue;
            }
            $set->setFieldValues($this->resolveFields($fields));
            $this->save($set, "Global $handle");
        }

        ($this->log)('Startinhoud geladen.');
    }

    // ------------------------------------------------------------------ Assets

    private function importAssets(): void
    {
        $assetsService = Craft::$app->getAssets();
        foreach ($this->data['assets'] ?? [] as $def) {
            $volume = Craft::$app->getVolumes()->getVolumeByHandle($def['volume']);
            if (!$volume) {
                throw new RuntimeException("Volume {$def['volume']} ontbreekt; draai eerst het schema.");
            }
            $filename = $def['filename'] ?? basename($def['file']);
            $existing = Asset::find()->volume($volume)->filename($filename)->one();
            if ($existing) {
                $this->assets[$def['key']] = $existing->id;
                continue;
            }

            $bron = $this->seedPath . '/bestanden/' . $def['file'];
            if (!is_file($bron)) {
                ($this->log)("  Let op: bestand ontbreekt: {$def['file']}");
                continue;
            }
            $tmp = Craft::$app->getPath()->getTempPath() . '/' . uniqid('seed', true) . '-' . $filename;
            copy($bron, $tmp);

            $asset = new Asset();
            $asset->tempFilePath = $tmp;
            $asset->setFilename($filename);
            $asset->newFolderId = $assetsService->getRootFolderByVolumeId($volume->id)->id;
            $asset->setVolumeId($volume->id);
            $asset->avoidFilenameConflicts = true;
            $asset->alt = $def['alt'] ?? null;
            if (!empty($def['focal'])) {
                $asset->setFocalPoint(['x' => $def['focal'][0], 'y' => $def['focal'][1]]);
            }
            $asset->setScenario(Asset::SCENARIO_CREATE);
            $this->save($asset, "Asset $filename");
            $this->assets[$def['key']] = $asset->id;
            if (is_file($tmp)) {
                FileHelper::unlink($tmp);
            }
        }
    }

    // ------------------------------------------------------------------ Entries

    private function entry(array $def, ?Entry $parent, bool $metVelden): Entry
    {
        $section = $def['sectie'];
        $sectionModel = Craft::$app->getEntries()->getSectionByHandle($section) ?? throw new RuntimeException("Sectie $section ontbreekt");
        $slug = $def['slug'] ?? null;
        $titel = $def['titel'] ?? null;

        $query = Entry::find()->section($section)->status(null)->siteId($this->siteId);
        if ($sectionModel->type === Section::TYPE_SINGLE) {
            $entry = $query->one() ?? throw new RuntimeException("Single $section heeft geen entry");
        } else {
            $query = $slug ? $query->slug($slug) : $query->title($titel);
            if ($parent) {
                $query->descendantOf($parent)->descendantDist(1);
            }
            $entry = $query->one() ?? new Entry();
            $entry->sectionId = $sectionModel->id;
            $entry->siteId = $this->siteId;
        }

        if (isset($def['type'])) {
            $typeModel = Craft::$app->getEntries()->getEntryTypeByHandle($def['type']) ?? throw new RuntimeException("Entry type {$def['type']} ontbreekt");
            $entry->typeId = $typeModel->id;
        }
        if ($titel !== null) {
            $entry->title = $titel;
        }
        if ($slug) {
            $entry->slug = $slug;
        }
        if (!empty($def['datum'])) {
            $entry->postDate = new DateTime($def['datum']);
        }
        if ($parent && $sectionModel->type === Section::TYPE_STRUCTURE) {
            $entry->setParentId($parent->id);
        }
        $label = "$section/" . ($slug ?? $titel ?? '');

        if ($metVelden) {
            $entry->setFieldValues($this->resolveFields($def['velden'] ?? []));
            $this->save($entry, $label);
        } elseif (!$entry->id) {
            // Ronde 1: alleen nieuwe entries aanmaken, zonder validatie (verplichte velden volgen in ronde 2).
            $this->save($entry, $label, false);
        }

        foreach ($def['kinderen'] ?? [] as $kind) {
            $this->entry($kind + ['sectie' => $section], $entry, $metVelden);
        }
        return $entry;
    }

    // ------------------------------------------------------------------ Waarden omzetten

    private function resolveFields(array $fields): array
    {
        $out = [];
        foreach ($fields as $handle => $value) {
            $out[$handle] = $this->resolveValue($value);
        }
        return $out;
    }

    private function resolveValue(mixed $value): mixed
    {
        if (is_string($value)) {
            if (str_starts_with($value, '@asset:')) {
                return array_filter([$this->assets[substr($value, 7)] ?? null]);
            }
            if (str_starts_with($value, '@entry:')) {
                $id = $this->entryId(substr($value, 7));
                return $id ? [$id] : [];
            }
            return $value;
        }
        if (!is_array($value)) {
            return $value;
        }

        // Link-veld
        if (isset($value['type']) && in_array($value['type'], ['entry', 'url', 'email', 'tel'], true) && !isset($value['velden'])) {
            if ($value['type'] === 'entry') {
                $id = $this->entryId($value['entry']);
                return $id ? array_filter([
                    'type' => 'entry',
                    'value' => sprintf('{entry:%s@%s:url}', $id, $this->siteId),
                    'label' => $value['label'] ?? null,
                ]) : null;
            }
            return array_filter(['type' => $value['type'], 'value' => $value['value'], 'label' => $value['label'] ?? null]);
        }

        // Matrix: lijst van { type, titel?, velden }
        if (array_is_list($value) && isset($value[0]['type']) && array_key_exists('velden', $value[0])) {
            $entries = [];
            $order = [];
            foreach ($value as $i => $item) {
                $key = 'new' . ($i + 1);
                $order[] = $key;
                $entries[$key] = array_filter([
                    'type' => $item['type'],
                    'enabled' => true,
                    'title' => $item['titel'] ?? null,
                    'fields' => $this->resolveFields($item['velden'] ?? []),
                ], fn($v) => $v !== null);
            }
            return ['sortOrder' => $order, 'entries' => $entries];
        }

        // Lijst van verwijzingen (entries/assets)
        if (array_is_list($value) && $value && is_string($value[0]) && str_starts_with($value[0], '@')) {
            $ids = [];
            foreach ($value as $v) {
                $ids = array_merge($ids, (array)$this->resolveValue($v));
            }
            return $ids;
        }

        // Tabel of overige array: ongewijzigd
        return $value;
    }

    private function entryId(string $ref): ?int
    {
        if ($ref === 'home') {
            return Entry::find()->section('home')->status(null)->ids()[0] ?? null;
        }
        [$section, $slug] = explode('/', $ref, 2) + [1 => null];
        $id = $slug ? (Entry::find()->section($section)->slug($slug)->status(null)->ids()[0] ?? null) : null;
        if (!$id) {
            ($this->log)("  Let op: verwijzing naar $ref niet gevonden.");
        }
        return $id;
    }

    private function save(ElementInterface $element, string $label, bool $valideren = true): void
    {
        if (!Craft::$app->getElements()->saveElement($element, $valideren)) {
            throw new RuntimeException("$label opslaan mislukt: " . json_encode($element->getErrors()));
        }
    }
}
