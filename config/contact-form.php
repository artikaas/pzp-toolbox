<?php
/**
 * Instellingen voor de plugin Contact Form.
 * De ontvanger kan in de CMS-global 'Contact' worden overschreven (zie modules/site/Module.php).
 * Zonder werkende mail (SMTP of mailprovider in Instellingen > E-mail) komen berichten niet aan.
 */

use craft\helpers\App;

return [
    'toEmail' => App::env('CONTACT_FORM_TO') ?: 'info@voorbeeld.nl',
    'prependSubject' => 'Contactformulier: ',
    'prependSender' => '',
    'allowAttachments' => false,
    'successFlashMessage' => 'Bedankt, je bericht is verstuurd.',
];
