<?php
return [
    'id' => 'dvelum-module-password-recovery',
    'version' => '2.0.1',
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
    'post-install'=>'\\Dvelum\\PasswordRecovery\\Installer'
];