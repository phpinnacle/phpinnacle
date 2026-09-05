<?php

return [
    'action' => [
        'label' => 'Activity History',
    ],
    'permissions' => [
        'label' => 'Activity',
        'group' => 'Activity',
        'view_any_activity' => 'View activity history',
    ],
    'causer' => [
        'unknown' => 'Unknown',
    ],
    'changes' => [
        'label' => 'Changes',
    ],
    'revert' => [
        'label' => 'Revert',
        'heading' => 'Revert these changes?',
        'description' => 'The previous values will be restored and recorded as a new activity.',
        'submit' => 'Revert',
        'success' => 'Changes reverted',
    ],
    'empty' => [
        'heading' => 'No activity recorded',
        'description' => 'Activity records will appear here once changes are made.',
    ],
    'search' => [
        'placeholder' => 'Search activity',
    ],
];
