<?php

namespace modules\site\console\controllers;

use Craft;
use craft\console\Controller;
use modules\site\services\Contentmodel;
use modules\site\services\Schema;
use modules\site\services\Seeder;
use Throwable;
use yii\console\ExitCode;
use yii\helpers\Console;

/**
 * Richt de site in volgens contentmodel.yaml en seed/content.json.
 *
 *   php craft site/setup              contentmodel + startinhoud (eerste keer)
 *   php craft site/setup/schema       alleen het contentmodel; vult een bestaande site aan, content blijft staan
 *   php craft site/setup/seed         alleen de startinhoud (overschrijft de demo-entries en globals!)
 *   php craft site/setup/controleer   alleen contentmodel.yaml controleren, niets aanpassen
 *
 * Draai dit op de eerste (lokale) omgeving. Commit daarna config/project/ en draai op andere
 * omgevingen `php craft up`.
 */
class SetupController extends Controller
{
    public $defaultAction = 'index';

    public function actionIndex(): int
    {
        $code = $this->actionSchema();
        return $code === ExitCode::OK ? $this->actionSeed() : $code;
    }

    public function actionControleer(): int
    {
        try {
            $model = Contentmodel::laden(Craft::getAlias('@root/contentmodel.yaml'));
        } catch (Throwable $e) {
            $this->stderr($e->getMessage() . "\n", Console::FG_RED);
            return ExitCode::DATAERR;
        }
        $this->stdout(sprintf(
            "contentmodel.yaml is in orde: %d velden, %d typen (waarvan %d blokken), %d secties, %d globals.\n",
            count($model->velden), count($model->typen), count($model->blokken), count($model->secties), count($model->globals)
        ), Console::FG_GREEN);
        return ExitCode::OK;
    }

    public function actionSchema(): int
    {
        if (!Craft::$app->getConfig()->getGeneral()->allowAdminChanges) {
            $this->stderr("allowAdminChanges staat uit. Zet CRAFT_ALLOW_ADMIN_CHANGES=true in .env (alleen lokaal).\n", Console::FG_RED);
            return ExitCode::CONFIG;
        }
        $this->stdout("Contentmodel aanmaken of aanvullen...\n", Console::FG_CYAN);
        try {
            $model = Contentmodel::laden(Craft::getAlias('@root/contentmodel.yaml'));
            (new Schema(fn(string $m) => $this->stdout($m . "\n"), $model))->run();
        } catch (Throwable $e) {
            $this->stderr($e->getMessage() . "\n", Console::FG_RED);
            Craft::error($e, __METHOD__);
            return ExitCode::UNSPECIFIED_ERROR;
        }
        return ExitCode::OK;
    }

    public function actionSeed(): int
    {
        $this->stdout("Startinhoud laden...\n", Console::FG_CYAN);
        try {
            (new Seeder(fn(string $m) => $this->stdout($m . "\n"), Craft::getAlias('@root/seed')))->run();
        } catch (Throwable $e) {
            $this->stderr($e->getMessage() . "\n", Console::FG_RED);
            Craft::error($e, __METHOD__);
            return ExitCode::UNSPECIFIED_ERROR;
        }
        $this->stdout("Klaar. Bouw de frontend met `npm install && npm run build`.\n", Console::FG_GREEN);
        return ExitCode::OK;
    }
}
