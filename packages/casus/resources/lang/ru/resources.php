<?php

return [
    'exception' => [
        'label' => 'Ошибки',
        'group' => 'Система',
        'actions' => [
            'delete' => 'Удалить',
            'triage' => 'Разобрать',
        ],
        'empty' => [
            'heading' => 'Ни одной ошибки 👀',
            'description' => 'Подозрительно, но приятно.',
        ],
        'fields' => [
            'id' => 'ID',
            'type' => 'Тип',
            'code' => 'Код',
            'message' => 'Сообщение',
            'file' => 'Файл',
            'line' => 'Строка',
            'link' => 'Файл и строка',
            'method' => 'Метод',
            'path' => 'Путь',
            'query' => 'Query-параметры',
            'body' => 'Тело запроса',
            'ip' => 'IP',
            'headers' => 'Заголовки',
            'header_key' => 'Заголовок',
            'header_value' => 'Значение',
            'cookies' => 'Cookies',
            'cookie_key' => 'Имя',
            'cookie_value' => 'Значение',
            'context' => 'Контекст',
            'trace' => 'Stack Trace',
            'status' => 'Статус',
            'occurrences' => 'Повторения',
            'note' => 'Внутренняя заметка',
            'occurred_at' => 'Дата и время',
        ],
        'filters' => [
            'occurred_at' => 'Дата',
            'type' => 'Тип',
            'method' => 'Метод',
            'status' => 'Статус',
        ],
        'pages' => [
            'list' => 'Ошибки приложения',
            'view' => 'Детали ошибки :type::code',
        ],
        'sections' => [
            'main' => 'Основное',
            'http' => 'HTTP',
            'headers' => 'Заголовки',
            'cookies' => 'Cookies',
            'context' => 'Контекст',
            'trace' => 'Стек вызовов',
        ],
        'statuses' => [
            'open' => 'Открыта',
            'acknowledged' => 'Принята',
            'resolved' => 'Решена',
        ],
        'notifications' => [
            'reported' => [
                'subject' => 'Зафиксирована ошибка приложения',
                'summary' => ':type: :message',
                'occurrences' => 'Повторения: :count',
            ],
        ],
    ],
];
