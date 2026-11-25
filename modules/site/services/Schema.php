<?php

namespace modules\site\services;

use Craft;
use craft\base\FieldInterface;
use craft\elements\Asset;
use craft\elements\Entry;
use craft\elements\GlobalSet;
use craft\fieldlayoutelements\assets\AltField;
use craft\fieldlayoutelements\assets\AssetTitleField;
use craft\fieldlayoutelements\CustomField;
use craft\fieldlayoutelements\entries\EntryTitleField;
use craft\fields\Assets;
use craft\fields\Dropdown;
use craft\fields\Email;
use craft\fields\Entries;
use craft\fields\Lightswitch;
use craft\fields\Link;
use craft\fields\Matrix;
use craft\fields\Number;
use craft\fields\PlainText;
use craft\fields\Table;
use craft\fs\Local;
use craft\helpers\StringHelper;
use craft\models\EntryType;
use craft\models\FieldLayout;
use craft\models\FieldLayoutTab;
use craft\models\Section;
use craft\models\Section_SiteSettings;
use craft\models\Volume;
use RuntimeException;

/**
 * Maakt het contentmodel uit contentmodel.yaml aan in Craft: plugins, CKEditor-config, volumes, velden,
 * entry types (ook de blokken), secties en globals.
 *
 * Idempotent én aanvullend. Wat al bestaat (op handle) wordt niet opnieuw gemaakt, maar wel bijgewerkt:
 * - ontbrekende velden in veldlayouts (direct na het veld dat er in de definitie voor staat)
 * - gewijzigde labels in layouts, gewijzigde veldnamen en uitleg
 * - ontbrekende entry types in Matrix-velden en secties
 * - ontbrekende opties in keuzelijsten
 *
 * Wat het schema bewust NIET doet: velden of entry types verwijderen, handles hernoemen of een layout
 * vervangen. In Craft 5 hangt opgeslagen content aan de UID van het layoutelement; een vervangen layout
 * laat content los. Zulke wijzigingen doe je in het CP (zie docs/contentmodel.md).
 *
 * Na het draaien staat alles in config/project/. Commit die map; andere omgevingen draaien `php craft up`.
 */
class Schema
{
    /** @var callable */
    private $log;

    private Contentmodel $model;

    /** @var array<string, FieldInterface> */
    private array $fields = [];

    /** @var array<string, EntryType> */
    private array $types = [];

    /** @var array<string, Volume> */
    private array $volumes = [];

    /** @var array<string, Section> */
    private array $sections = [];

    /** @var array<string, true> bezig met aanmaken (om kringverwijzingen te vinden) */
    private array $bezig = [];

    private ?string $ckeConfigUid = null;

    public function __construct(callable $log, Contentmodel $model)
    {
        $this->log = $log;
        $this->model = $model;
    }

    public function run(): void
    {
        $this->plugins();
        foreach ($this->model->volumes as $handle => $naam) {
            $this->volume($handle, $naam);
        }
        foreach (array_keys($this->model->velden) as $handle) {
            $this->veld($handle);
        }
        foreach (array_keys($this->model->typen) as $handle) {
            $this->type($handle);
        }
        foreach ($this->model->secties as $handle => $def) {
            $this->sectie($handle, $def);
        }
        $this->relatiesBijwerken();
        foreach ($this->model->globals as $handle => $def) {
            $this->globalSet($handle, $def['naam'], $def['tabs']);
        }
        ($this->log)('Contentmodel klaar.');
    }

    // ------------------------------------------------------------------ Plugins en CKEditor

    private function plugins(): void
    {
        $plugins = Craft::$app->getPlugins();
        foreach ($this->model->plugins as $handle) {
            if (!$plugins->isPluginInstalled($handle)) {
                $plugins->installPlugin($handle);
                ($this->log)("Plugin geïnstalleerd: $handle");
            }
        }

        if (!$plugins->isPluginEnabled('ckeditor')) {
            return;
        }
        $naam = $this->model->ckeditor['naam'] ?? 'Standaard';
        $configs = \craft\ckeditor\Plugin::getInstance()->getCkeConfigs();
        foreach ($configs->getAll() as $config) {
            if ($config->name === $naam) {
                $this->ckeConfigUid = $config->uid;
                return;
            }
        }
        $config = new \craft\ckeditor\CkeConfig([
            'uid' => StringHelper::UUID(),
            'name' => $naam,
            'toolbar' => $this->model->ckeditor['toolbar'] ?? ['bold', 'italic', '|', 'link', '|', 'bulletedList', 'numberedList'],
            'headingLevels' => false,
        ]);
        if (!$configs->save($config)) {
            throw new RuntimeException('CKEditor-configuratie opslaan mislukt: ' . json_encode($config->getErrors()));
        }
        $this->ckeConfigUid = $config->uid;
        ($this->log)("CKEditor-configuratie aangemaakt: $naam");
    }

    // ------------------------------------------------------------------ Volumes

    private function volume(string $handle, string $naam): Volume
    {
        $volumes = Craft::$app->getVolumes();
        if ($existing = $volumes->getVolumeByHandle($handle)) {
            return $this->volumes[$handle] = $existing;
        }

        $fsService = Craft::$app->getFs();
        if (!$fsService->getFilesystemByHandle($handle)) {
            $fs = new Local([
                'name' => $naam,
                'handle' => $handle,
                'hasUrls' => true,
                'url' => '@web/uploads/' . $handle,
                'path' => '@webroot/uploads/' . $handle,
            ]);
            if (!$fsService->saveFilesystem($fs)) {
                throw new RuntimeException("Bestandssysteem $handle: " . json_encode($fs->getErrors()));
            }
        }

        $volume = new Volume(['name' => $naam, 'handle' => $handle]);
        $volume->setFsHandle($handle);
        $layout = new FieldLayout(['type' => Asset::class]);
        $tab = new FieldLayoutTab(['name' => 'Inhoud', 'layout' => $layout]);
        $tab->setElements([new AssetTitleField(), new AltField(['instructions' => 'Beschrijf wat er op het beeld te zien is. Leeg laten bij decoratief beeld.'])]);
        $layout->setTabs([$tab]);
        $volume->setFieldLayout($layout);

        if (!$volumes->saveVolume($volume)) {
            throw new RuntimeException("Volume $handle: " . json_encode($volume->getErrors()));
        }
        ($this->log)("Volume aangemaakt: $naam");
        return $this->volumes[$handle] = $volume;
    }

    // ------------------------------------------------------------------ Velden

    private function veld(string $handle): FieldInterface
    {
        if (isset($this->fields[$handle])) {
            return $this->fields[$handle];
        }
        $def = $this->model->velden[$handle] ?? throw new RuntimeException("Veld $handle staat niet in contentmodel.yaml");
        if (isset($this->bezig["veld:$handle"])) {
            throw new RuntimeException("Kringverwijzing: veld $handle verwijst (via een type) naar zichzelf.");
        }
        $this->bezig["veld:$handle"] = true;

        [$class, $config] = $this->veldConfig($handle, $def);
        $field = $this->field($class, $handle, $def['naam'] ?? $handle, $config, $def['uitleg'] ?? null);

        unset($this->bezig["veld:$handle"]);
        return $field;
    }

    /** Vertaalt een velddefinitie uit de YAML naar een Craft-veldklasse en instellingen. */
    private function veldConfig(string $handle, array $def): array
    {
        $soort = $def['soort'];
        switch ($soort) {
            case 'tekst':
                $regels = (int)($def['regels'] ?? 1);
                return [PlainText::class, array_filter([
                    'multiline' => $regels > 1 ?: null,
                    'initialRows' => $regels > 1 ? $regels : null,
                    'charLimit' => $def['max'] ?? null,
                ], fn($v) => $v !== null)];

            case 'rijketekst':
                return [\craft\ckeditor\Field::class, ['ckeConfig' => $this->ckeConfigUid, 'purifyHtml' => true]];

            case 'beeld':
                $volume = $this->volumes[$def['volume']] ?? throw new RuntimeException("Veld $handle: volume {$def['volume']} ontbreekt");
                return [Assets::class, [
                    'sources' => ['volume:' . $volume->uid],
                    'defaultUploadLocationSource' => 'volume:' . $volume->uid,
                    'restrictFiles' => true,
                    'allowedKinds' => $def['bestanden'] ?? ['image'],
                    'maxRelations' => $def['max'] ?? 1,
                    'viewMode' => 'large',
                ]];

            case 'link':
                // Url-links mogen relatief zijn (/contact/?onderwerp=…) en een anker hebben (#formulier).
                $soorten = $def['soorten'] ?? ['entry', 'url', 'email'];
                return [Link::class, [
                    'types' => $soorten,
                    'showLabelField' => $def['label'] ?? true,
                    'typeSettings' => in_array('url', $soorten, true) ? ['url' => ['allowRootRelativeUrls' => true, 'allowAnchors' => true]] : [],
                ]];

            case 'keuze':
                $opties = $def['opties'] ?? [];
                $standaard = $def['standaard'] ?? array_key_first($opties);
                $options = [];
                foreach ($opties as $waarde => $label) {
                    $options[] = ['label' => $label, 'value' => (string)$waarde, 'default' => (string)$waarde === (string)$standaard];
                }
                return [Dropdown::class, ['options' => $options]];

            case 'schakelaar':
                return [Lightswitch::class, ['default' => (bool)($def['standaard'] ?? false)]];

            case 'getal':
                return [Number::class, array_filter([
                    'min' => $def['min'] ?? null,
                    'max' => $def['max'] ?? null,
                    'defaultValue' => $def['standaard'] ?? null,
                    'decimals' => $def['decimalen'] ?? 0,
                ], fn($v) => $v !== null)];

            case 'email':
                return [Email::class, []];

            case 'tabel':
                $columns = [];
                $i = 1;
                foreach ($def['kolommen'] ?? ['waarde' => 'Waarde'] as $kolomHandle => $kop) {
                    $columns['col' . $i++] = ['heading' => $kop, 'handle' => $kolomHandle, 'type' => 'singleline', 'width' => ''];
                }
                return [Table::class, ['columns' => $columns, 'addRowLabel' => $def['knop'] ?? 'Rij toevoegen']];

            case 'entries':
                // Bronnen zijn secties. Bestaan die nog niet, dan eerst 'alle'; relatiesBijwerken() beperkt het later.
                return [Entries::class, array_filter([
                    'sources' => $this->bronnen($def) ?? '*',
                    'maxRelations' => $def['max'] ?? null,
                ], fn($v) => $v !== null)];

            case 'matrix':
                $types = array_map(fn($t) => $this->type($t), $def['typen'] ?? []);
                return [Matrix::class, array_filter([
                    'viewMode' => Matrix::VIEW_MODE_BLOCKS,
                    'minEntries' => $def['min'] ?? null,
                    'maxEntries' => $def['max'] ?? null,
                    'createButtonLabel' => $def['knop'] ?? null,
                    '_entryTypes' => $types,
                ], fn($v) => $v !== null)];
        }
        throw new RuntimeException("Veld $handle: onbekende soort '$soort'");
    }

    /** Sectiebronnen voor een entries-veld, of null als nog niet alle secties bestaan. */
    private function bronnen(array $def): ?array
    {
        if (empty($def['bronnen'])) {
            return null;
        }
        $bronnen = [];
        foreach ($def['bronnen'] as $sectie) {
            $s = $this->sections[$sectie] ?? Craft::$app->getEntries()->getSectionByHandle($sectie);
            if (!$s) {
                return null;
            }
            $bronnen[] = 'section:' . $s->uid;
        }
        return $bronnen;
    }

    /**
     * Maakt een veld aan als het nog niet bestaat. Bestaat het wel, dan worden naam, uitleg, ontbrekende
     * keuzes en ontbrekende Matrix-typen bijgewerkt. Instellingen die deze Craft-versie niet kent,
     * worden overgeslagen en gemeld.
     */
    private function field(string $class, string $handle, string $name, array $config = [], ?string $instructions = null): FieldInterface
    {
        $fields = Craft::$app->getFields();
        $entryTypes = $config['_entryTypes'] ?? null;
        unset($config['_entryTypes']);

        if ($existing = $fields->getFieldByHandle($handle)) {
            if (!$existing instanceof $class) {
                ($this->log)("  Let op: veld $handle bestaat al als " . get_class($existing) . ", niet als $class. Niet aangepast.");
                return $this->fields[$handle] = $existing;
            }
            $wijzigingen = [];
            if ($existing->name !== $name) {
                $existing->name = $name;
                $wijzigingen[] = 'naam';
            }
            if (($existing->instructions ?? null) !== $instructions && $instructions !== null) {
                $existing->instructions = $instructions;
                $wijzigingen[] = 'uitleg';
            }
            if ($existing instanceof Dropdown && isset($config['options'])) {
                $waarden = array_map(fn($o) => is_array($o) ? ($o['value'] ?? null) : null, $existing->options);
                $nieuw = array_filter($config['options'], fn($o) => !in_array($o['value'], $waarden, true));
                if ($nieuw) {
                    $existing->options = array_merge($existing->options, array_map(fn($o) => ['default' => false] + $o, array_values($nieuw)));
                    $wijzigingen[] = 'keuzes';
                }
            }
            if ($existing instanceof Matrix && $entryTypes) {
                $bestaand = array_map(fn($t) => $t->handle, $existing->getEntryTypes());
                $ontbrekend = array_values(array_filter($entryTypes, fn($t) => !in_array($t->handle, $bestaand, true)));
                if ($ontbrekend) {
                    $existing->setEntryTypes(array_merge($existing->getEntryTypes(), $ontbrekend));
                    $wijzigingen[] = 'typen +' . implode(', ', array_map(fn($t) => $t->handle, $ontbrekend));
                }
            }
            if ($wijzigingen) {
                if (!$fields->saveField($existing)) {
                    throw new RuntimeException("Veld $handle bijwerken: " . json_encode($existing->getErrors()));
                }
                ($this->log)("Veld bijgewerkt: $handle (" . implode(', ', $wijzigingen) . ')');
            }
            return $this->fields[$handle] = $existing;
        }

        /** @var FieldInterface $field */
        $field = new $class();
        $field->name = $name;
        $field->handle = $handle;
        $field->instructions = $instructions;
        foreach ($config as $key => $value) {
            if ($field->canSetProperty($key)) {
                $field->$key = $value;
            } else {
                ($this->log)("  Let op: instelling '$key' bestaat niet op $class, overgeslagen ($handle).");
            }
        }
        if ($entryTypes !== null && $field instanceof Matrix) {
            $field->setEntryTypes($entryTypes);
        }

        if (!$fields->saveField($field)) {
            throw new RuntimeException("Veld $handle: " . json_encode($field->getErrors()));
        }
        ($this->log)("Veld aangemaakt: $name ($handle)");
        return $this->fields[$handle] = $field;
    }

    // ------------------------------------------------------------------ Entry types

    private function type(string $handle): EntryType
    {
        if (isset($this->types[$handle])) {
            return $this->types[$handle];
        }
        $def = $this->model->typen[$handle] ?? throw new RuntimeException("Type $handle staat niet in contentmodel.yaml");
        if (isset($this->bezig["type:$handle"])) {
            throw new RuntimeException("Kringverwijzing: type $handle bevat (via een Matrix-veld) zichzelf.");
        }
        $this->bezig["type:$handle"] = true;

        // Eerst alle velden van de layout (en daarmee geneste typen).
        foreach ($def['tabs'] as $els) {
            foreach ($els as $el) {
                if ($el['handle'] !== 'title') {
                    $this->veld($el['handle']);
                }
            }
        }

        $entries = Craft::$app->getEntries();
        if ($existing = $entries->getEntryTypeByHandle($handle)) {
            $gewijzigd = $this->aanvullen($existing->getFieldLayout(), $def['tabs']);
            if ($existing->name !== $def['naam']) {
                $existing->name = $def['naam'];
                $gewijzigd = true;
            }
            if ($gewijzigd) {
                if (!$entries->saveEntryType($existing)) {
                    throw new RuntimeException("Entry type $handle bijwerken: " . json_encode($existing->getErrors()));
                }
                ($this->log)("Entry type bijgewerkt: {$def['naam']} ($handle)");
            }
            unset($this->bezig["type:$handle"]);
            return $this->types[$handle] = $existing;
        }

        $type = new EntryType();
        $type->name = $def['naam'];
        $type->handle = $handle;
        $type->hasTitleField = $def['titel'];
        if (!$type->hasTitleField) {
            $type->titleFormat = '{type.name}';
        }
        if ($def['icoon'] && $type->canSetProperty('icon')) {
            $type->icon = $def['icoon'];
        }
        $type->setFieldLayout($this->layout(Entry::class, $def['tabs']));

        if (!$entries->saveEntryType($type)) {
            throw new RuntimeException("Entry type $handle: " . json_encode($type->getErrors()));
        }
        ($this->log)("Entry type aangemaakt: {$def['naam']} ($handle)");
        unset($this->bezig["type:$handle"]);
        return $this->types[$handle] = $type;
    }

    // ------------------------------------------------------------------ Layouts

    private function layout(string $elementType, array $tabs): FieldLayout
    {
        $layout = new FieldLayout(['type' => $elementType]);
        $tabModels = [];
        foreach ($tabs as $tabName => $elements) {
            $tab = new FieldLayoutTab(['name' => $tabName, 'layout' => $layout]);
            $tab->setElements(array_map(fn($el) => $this->layoutElement($el), $elements));
            $tabModels[] = $tab;
        }
        $layout->setTabs($tabModels);
        return $layout;
    }

    /**
     * Vult een bestaande veldlayout aan met de velden uit de definitie die er nog niet in staan.
     * Bestaande elementen (en daarmee de opgeslagen content) blijven ongemoeid; nieuwe velden komen
     * direct na het veld dat er in de definitie voor staat. Labels en verplicht worden bijgewerkt.
     */
    private function aanvullen(FieldLayout $layout, array $tabs): bool
    {
        $handleVan = function ($el): ?string {
            if ($el instanceof CustomField) {
                try {
                    return $el->getField()->handle;
                } catch (\Throwable) {
                    return null;
                }
            }
            return $el instanceof EntryTitleField ? 'title' : null;
        };
        $aanwezig = [];
        foreach ($layout->getTabs() as $tab) {
            foreach ($tab->getElements() as $el) {
                if ($h = $handleVan($el)) {
                    $aanwezig[$h] = true;
                }
            }
        }

        $gewijzigd = false;
        $tabModels = $layout->getTabs();
        foreach ($tabs as $tabName => $elements) {
            $tab = null;
            foreach ($tabModels as $t) {
                if ($t->name === $tabName) {
                    $tab = $t;
                }
            }
            if (!$tab) {
                $tab = new FieldLayoutTab(['name' => $tabName, 'layout' => $layout]);
                $tab->setElements([]);
                $tabModels[] = $tab;
            }
            $els = $tab->getElements();
            $vorige = null;
            foreach ($elements as $element) {
                $handle = $element['handle'];
                if (isset($aanwezig[$handle])) {
                    // Label en verplicht bijwerken (alleen in deze tab; staat het veld in een andere tab, dan ongemoeid)
                    foreach ($els as $el) {
                        if ($el instanceof CustomField && $handleVan($el) === $handle) {
                            if ($el->label !== $element['label']) {
                                $el->label = $element['label'];
                                $gewijzigd = true;
                                ($this->log)("  ~ label $handle: " . ($element['label'] ?? '(standaard)'));
                            }
                            if ((bool)$el->required !== $element['verplicht']) {
                                $el->required = $element['verplicht'];
                                $gewijzigd = true;
                                ($this->log)("  ~ verplicht $handle: " . ($element['verplicht'] ? 'ja' : 'nee'));
                            }
                        }
                    }
                    $vorige = $handle;
                    continue;
                }
                if ($handle === 'title') {
                    continue;
                }
                $nieuw = $this->layoutElement($element);
                $pos = count($els);
                if ($vorige !== null) {
                    foreach ($els as $i => $el) {
                        if ($handleVan($el) === $vorige) {
                            $pos = $i + 1;
                        }
                    }
                }
                array_splice($els, $pos, 0, [$nieuw]);
                $aanwezig[$handle] = true;
                $vorige = $handle;
                $gewijzigd = true;
                ($this->log)("  + veld $handle in tab '$tabName'");
            }
            $tab->setElements($els);
        }
        if ($gewijzigd) {
            $layout->setTabs($tabModels);
        }
        return $gewijzigd;
    }

    private function layoutElement(array $element): CustomField|EntryTitleField
    {
        if ($element['handle'] === 'title') {
            return new EntryTitleField(array_filter(['label' => $element['label']]));
        }
        $field = $this->fields[$element['handle']] ?? Craft::$app->getFields()->getFieldByHandle($element['handle']);
        if (!$field) {
            throw new RuntimeException("Veld {$element['handle']} ontbreekt voor de layout");
        }
        return new CustomField($field, array_filter(['required' => $element['verplicht'], 'label' => $element['label']]));
    }

    // ------------------------------------------------------------------ Secties

    private function sectie(string $handle, array $def): Section
    {
        $entries = Craft::$app->getEntries();
        $typeHandles = $def['typen'] ?? [];
        $naam = $def['naam'] ?? ucfirst($handle);

        if ($existing = $entries->getSectionByHandle($handle)) {
            $bestaand = array_map(fn($t) => $t->handle, $existing->getEntryTypes());
            $ontbrekend = array_values(array_filter($typeHandles, fn($h) => !in_array($h, $bestaand, true)));
            if ($ontbrekend) {
                // Volgorde van de definitie aanhouden (het eerste type is het standaardtype bij 'Nieuwe entry')
                $alle = array_merge($existing->getEntryTypes(), array_map(fn($h) => $this->type($h), $ontbrekend));
                usort($alle, function ($a, $b) use ($typeHandles) {
                    $ia = array_search($a->handle, $typeHandles, true);
                    $ib = array_search($b->handle, $typeHandles, true);
                    return ($ia === false ? PHP_INT_MAX : $ia) <=> ($ib === false ? PHP_INT_MAX : $ib);
                });
                $existing->setEntryTypes($alle);
                if (!$entries->saveSection($existing)) {
                    throw new RuntimeException("Sectie $handle bijwerken: " . json_encode($existing->getErrors()));
                }
                ($this->log)("Sectie bijgewerkt: $naam ($handle, +" . implode(', ', $ontbrekend) . ')');
            }
            return $this->sections[$handle] = $existing;
        }

        $soorten = ['single' => Section::TYPE_SINGLE, 'structure' => Section::TYPE_STRUCTURE, 'channel' => Section::TYPE_CHANNEL];
        $section = new Section();
        $section->name = $naam;
        $section->handle = $handle;
        $section->type = $soorten[$def['soort'] ?? 'channel'] ?? throw new RuntimeException("Sectie $handle: onbekende soort {$def['soort']}");
        $section->enableVersioning = true;
        if ($section->type === Section::TYPE_STRUCTURE) {
            $section->maxLevels = $def['niveaus'] ?? null;
        }
        $section->setEntryTypes(array_map(fn($h) => $this->type($h), $typeHandles));

        $siteSettings = [];
        foreach (Craft::$app->getSites()->getAllSites() as $s) {
            $siteSettings[$s->id] = new Section_SiteSettings([
                'siteId' => $s->id,
                'enabledByDefault' => true,
                'hasUrls' => isset($def['uri']),
                'uriFormat' => $def['uri'] ?? null,
                'template' => $def['template'] ?? null,
            ]);
        }
        $section->setSiteSettings($siteSettings);

        if (!$entries->saveSection($section)) {
            throw new RuntimeException("Sectie $handle: " . json_encode($section->getErrors()));
        }
        ($this->log)("Sectie aangemaakt: $naam ($handle)");
        return $this->sections[$handle] = $section;
    }

    /** Entries-velden die met 'alle' zijn gemaakt omdat hun secties nog niet bestonden, nu beperken. */
    private function relatiesBijwerken(): void
    {
        $fields = Craft::$app->getFields();
        foreach ($this->model->velden as $handle => $def) {
            if (($def['soort'] ?? null) !== 'entries' || empty($def['bronnen'])) {
                continue;
            }
            $field = $fields->getFieldByHandle($handle);
            $bronnen = $this->bronnen($def);
            if ($field instanceof Entries && $field->sources === '*' && $bronnen) {
                $field->sources = $bronnen;
                if (!$fields->saveField($field)) {
                    throw new RuntimeException("Relatieveld $handle: " . json_encode($field->getErrors()));
                }
                ($this->log)("Relatieveld beperkt: $handle (" . implode(', ', $def['bronnen']) . ')');
            }
        }
    }

    // ------------------------------------------------------------------ Globals

    private function globalSet(string $handle, string $naam, array $tabs): GlobalSet
    {
        foreach ($tabs as $els) {
            foreach ($els as $el) {
                $this->veld($el['handle']);
            }
        }
        $globals = Craft::$app->getGlobals();
        if ($existing = $globals->getSetByHandle($handle)) {
            if ($this->aanvullen($existing->getFieldLayout(), $tabs)) {
                if (!$globals->saveSet($existing)) {
                    throw new RuntimeException("Global $handle bijwerken: " . json_encode($existing->getErrors()));
                }
                ($this->log)("Global bijgewerkt: $naam ($handle)");
            }
            return $existing;
        }
        $set = new GlobalSet(['name' => $naam, 'handle' => $handle]);
        $set->setFieldLayout($this->layout(GlobalSet::class, $tabs));
        if (!$globals->saveSet($set)) {
            throw new RuntimeException("Global $handle: " . json_encode($set->getErrors()));
        }
        ($this->log)("Global aangemaakt: $naam ($handle)");
        return $set;
    }
}
