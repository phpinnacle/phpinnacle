<?php

return [
    'login' => [
        'as' => 'Войти как:',
    ],
    'profile' => [
        'label' => 'Профиль',
        'sections' => [
            'general' => 'Общее',
            'password' => 'Пароль',
            'oauth_accounts' => 'Подключённые аккаунты',
            'two_factor' => 'Двухфакторная аутентификация',
        ],
        'descriptions' => [
            'oauth_accounts' => 'Управляйте подключёнными социальными аккаунтами.',
            'general' => 'Настройте свой профиль',
            'password' => 'Обновите свой пароль',
            'two_factor' => 'Управляйте настройками двухфакторной аутентификации',
        ],
        'actions' => [
            'update_profile' => 'Обновить профиль',
            'change_password' => 'Изменить пароль',
        ],
        'fields' => [
            'name' => 'Имя',
            'email' => 'Электронная почта',
            'password' => 'Пароль',
            'confirmation' => 'Подтверждение пароля',
        ],
    ],
];
