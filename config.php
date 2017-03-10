<?php
return [
    'id' => 'dvelum_passwordrecovery',
    'version' => '1.0.0',
    'author' => 'Kirill Egorov',
    'name' => 'DVelum Password Recovery',
    'configs' => './configs',
    'locales' => './locales',
    'resources' =>'./resources',
    'templates' => './templates',
    'vendor'=>'Dvelum',
    'autoloader'=> [
        './classes'
    ],
    'objects' =>[
    ],
    'post-install'=>'Dvelum_Backend_PasswordRecovery_Installer'
];