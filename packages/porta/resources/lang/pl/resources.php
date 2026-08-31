<?php

return [
    'integration' => [
        'label' => 'Integracje',
        'group' => 'Ustawienia',
        'actions' => [
            'create' => 'Dodaj',
            'update' => 'Edytuj',
            'delete' => 'Usuń',
            'clone' => 'Klonuj',
            'test' => 'Testuj',
        ],
        'empty' => [
            'heading' => 'Brak integracji',
            'description' => 'Utwórz swoją pierwszą integrację, aby rozpocząć automatyzację zadań.',
        ],
        'fields' => [
            'title' => 'Tytuł',
            'type' => 'Typ',
            'format' => 'Format',
            'auth' => 'Uwierzytelnianie',
            'auth_key' => 'Klucz uwierzytelniania',
            'auth_secret' => 'Sekret uwierzytelniania',
            'response_kind' => 'Rodzaj odpowiedzi',
            'response_code' => 'Kod odpowiedzi',
            'transforms' => 'Transformacje',
            'transforms_type' => 'Typ',
            'transforms_path' => 'Ścieżka',
            'transforms_config' => 'Konfiguracja',
        ],
        'filters' => [
            'type' => 'Typ',
            'format' => 'Format',
            'auth' => 'Uwierzytelnianie',
            'response_kind' => 'Odpowiedź',
        ],
        'pages' => [
            'list' => 'Integracje',
            'create' => 'Utwórz integrację',
            'edit' => 'Edytuj integrację',
            'webhooks' => 'Webhooki',
        ],
        'sections' => [
            'general' => [
                'heading' => 'Ogólne',
            ],
            'auth' => [
                'heading' => 'Uwierzytelnianie',
            ],
            'transforms' => [
                'heading' => 'Transformacje',
            ],
        ],
    ],
    'webhook' => [
        'label' => 'Webhooki',
        'group' => 'Ustawienia',
        'actions' => [
            'view' => 'Zobacz',
            'create' => 'Dodaj',
            'update' => 'Edytuj',
            'delete' => 'Usuń',
            'clone' => 'Klonuj',
            'retry' => 'Ponów',
        ],
        'empty' => [
            'heading' => 'Brak webhooków',
            'description' => 'Utwórz swój pierwszy webhook, aby rozpocząć odbieranie zdarzeń.',
        ],
        'fields' => [
            'id' => 'ID',
            'origin' => 'URL źródła',
            'status' => 'Status',
            'payload' => 'Ładunek',
            'headers' => 'Nagłówki',
            'error' => 'Szczegóły błędu',
            'error_type' => 'Typ błędu',
            'error_file' => 'Plik błędu',
            'error_message' => 'Komunikat błędu',
            'error_validation' => 'Błędy walidacji',
            'processed_at' => 'Przetworzono',
        ],
        'filters' => [
            'status' => 'Status',
            'processed_at' => 'Przetworzono',
        ],
        'pages' => [
            'list' => 'Webhooki',
            'create' => 'Utwórz webhook',
            'edit' => 'Edytuj webhook',
        ],
        'sections' => [
            'general' => [
                'heading' => 'Ogólne',
            ],
            'error' => [
                'heading' => 'Szczegóły błędu',
            ],
        ],
        'tabs' => [
            'general' => 'Ogólne',
            'error' => 'Szczegóły błędu',
        ],
    ],
];
