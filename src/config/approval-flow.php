<?php

return [
    /*
     * Enable or disable approval activity logging
     */
    'log_approvals' => env('APPROVAL_FLOW_LOG_ENABLED', true),

    /*
     * Models used by the approval flow system
     */
    'models' => [
        'approval_log' => \Jodeveloper\ApprovalFlow\Models\ApprovalLog::class,
    ],

    /*
     * Table names used by the approval flow system
     */
    'table_names' => [
        'approval_logs' => 'approval_logs',
    ],

    /*
     * Notification settings
     */
    'notifications' => [
        'enabled' => env('APPROVAL_FLOW_NOTIFICATIONS_ENABLED', true),
        'channels' => ['mail', 'database'],
    ],
];
