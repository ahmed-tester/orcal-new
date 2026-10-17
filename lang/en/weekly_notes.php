<?php

return [
    'single' => 'Weekly Note',
    'plural' => 'Weekly Notes',
    
    'sections' => [
        'info' => 'Weekly Note Details',
        'info_desc' => 'Track and resolve operational branch-level issues and notes',
    ],

    'fields' => [
        'voucher_number' => 'Voucher No.',
        'note_date' => 'Note Date',
        'company' => 'Company / Branch',
        'title' => 'Note Title',
        'status' => 'Status',
        'content' => 'Note Content & Action Details',
        'assigned_to' => 'Assigned Follow-up Person',
        'created_by' => 'Created By',
    ],

    'statuses' => [
        'pending' => 'Pending / Open',
        'in_progress' => 'In Progress',
        'completed' => 'Resolved / Completed',
        'postponed' => 'Postponed',
        'cancelled' => 'Cancelled',
    ],
];
