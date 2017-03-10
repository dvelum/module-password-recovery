<?php
return [
    'forgot_password' => array(
        'subject' => 'Password recovery',
        'fromAddress' => 'noreply@MySite.com',
        'fromName' => '[MySite]',
        'template' => [
            'ru' => './dvelum_recovery/mail/forgot_password_ru.php',
            'en' => './dvelum_recovery/mail/forgot_password_en.php',
        ]
    ),
];