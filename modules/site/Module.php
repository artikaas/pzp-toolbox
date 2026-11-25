<?php

namespace modules\site;

use Craft;
use craft\console\Application as ConsoleApplication;
use craft\elements\GlobalSet;
use modules\site\twig\Extension;
use yii\base\Event;
use yii\base\Model;
use yii\base\Module as BaseModule;

/**
 * Projectmodule (uit de Artica website-starter).
 *
 * - Console: `php craft site/setup` maakt het contentmodel en de startinhoud aan (zie console/controllers).
 * - Twig: de functie vite() voor de frontend-bundel (zie twig/Extension.php).
 * - Contactformulier: honeypot tegen spam, afzendernaam uit voor- en achternaam, ontvanger uit de global Contact.
 */
class Module extends BaseModule
{
    public function init(): void
    {
        Craft::setAlias('@modules/site', __DIR__);

        if (Craft::$app instanceof ConsoleApplication) {
            $this->controllerNamespace = 'modules\\site\\console\\controllers';
        } else {
            $this->controllerNamespace = 'modules\\site\\controllers';
        }

        parent::init();

        Craft::$app->onInit(function () {
            Craft::$app->getView()->registerTwigExtension(new Extension());
            $this->registerContactFormEvents();
        });
    }

    private function registerContactFormEvents(): void
    {
        if (!class_exists(\craft\contactform\Mailer::class)) {
            return;
        }

        // Afzendernaam samenstellen uit message[Voornaam] en message[Achternaam] als fromName leeg is.
        Event::on(\craft\contactform\models\Submission::class, Model::EVENT_BEFORE_VALIDATE, function (\yii\base\ModelEvent $event) {
            /** @var \craft\contactform\models\Submission $submission */
            $submission = $event->sender;
            if (!$submission->fromName && is_array($submission->message)) {
                $naam = trim(($submission->message['Voornaam'] ?? '') . ' ' . ($submission->message['Achternaam'] ?? ''));
                $submission->fromName = $naam ?: null;
            }
            // De berichttekst is verplicht, ook als alleen andere message[...]-velden zijn ingevuld.
            if (is_array($submission->message) && array_key_exists('body', $submission->message) && trim((string)$submission->message['body']) === '') {
                $submission->addError('message', 'Vul je bericht in.');
            }
        });

        Event::on(\craft\contactform\Mailer::class, \craft\contactform\Mailer::EVENT_BEFORE_SEND, function (\craft\contactform\events\SendEvent $event) {
            $request = Craft::$app->getRequest();

            // Honeypot: het verborgen veld 'website' wordt alleen door bots ingevuld.
            if (!$request->getIsConsoleRequest() && trim((string)$request->getBodyParam('website', '')) !== '') {
                $event->isSpam = true;
                return;
            }

            // Ontvanger(s) uit de global Contact (komma-gescheiden), anders config/contact-form.php.
            $set = GlobalSet::find()->handle('contact')->one();
            $ontvanger = $set ? trim((string)$set->getFieldValue('ontvanger')) : '';
            if ($ontvanger !== '') {
                $event->toEmails = array_values(array_filter(array_map('trim', explode(',', $ontvanger))));
            }
        });
    }
}
