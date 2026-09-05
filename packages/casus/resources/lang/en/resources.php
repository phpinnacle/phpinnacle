<?php

return [
    'exception' => [
        'label' => 'Exceptions',
        'group' => 'System',
        'actions' => [
            'delete' => 'Delete',
            'triage' => 'Triage',
        ],
        'empty' => [
            'heading' => 'No errors — hooray!',
            'description' => 'Hopefully it stays that way.',
        ],
        'fields' => [
            'id' => 'ID',
            'type' => 'Type',
            'code' => 'Code',
            'message' => 'Message',
            'file' => 'File',
            'line' => 'Line',
            'link' => 'File & Line',
            'method' => 'Method',
            'path' => 'Path',
            'query' => 'Query',
            'body' => 'Body',
            'ip' => 'IP',
            'headers' => 'Headers',
            'header_key' => 'Header',
            'header_value' => 'Value',
            'cookies' => 'Cookies',
            'cookie_key' => 'Name',
            'cookie_value' => 'Value',
            'context' => 'Context',
            'trace' => 'Stack Trace',
            'status' => 'Status',
            'occurrences' => 'Occurrences',
            'note' => 'Internal note',
            'occurred_at' => 'Occurred At',
        ],
        'filters' => [
            'occurred_at' => 'Date',
            'type' => 'Type',
            'method' => 'Method',
            'status' => 'Status',
        ],
        'pages' => [
            'list' => 'Exceptions',
            'view' => 'Exception :type::code details',
        ],
        'sections' => [
            'main' => 'Main',
            'http' => 'HTTP',
            'headers' => 'Headers',
            'cookies' => 'Cookies',
            'context' => 'Context',
            'trace' => 'Stack Trace',
        ],
        'statuses' => [
            'open' => 'Open',
            'acknowledged' => 'Acknowledged',
            'resolved' => 'Resolved',
        ],
        'notifications' => [
            'reported' => [
                'subject' => 'Application exception reported',
                'summary' => ':type: :message',
                'occurrences' => 'Occurrences: :count',
            ],
        ],
    ],
];
