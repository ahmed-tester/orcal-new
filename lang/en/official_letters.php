<?php

return [
    'single' => 'Official Letter',
    'plural' => 'Official Letters',
    'title'  => 'Official Letters',

    'sections' => [
        'basic_info'      => 'Letter Details',
        'basic_info_desc' => 'Select the company, letter number, date, subject, and status',
        'body'            => 'Letter Content',
        'body_desc'       => 'Write the official letter content using the rich text editor',
    ],

    'statuses' => [
        'draft'    => 'Draft',
        'approved' => 'Approved',
        'sent'     => 'Sent',
    ],

    'fields' => [
        'company'       => 'Company',
        'letter_number' => 'Letter Number',
        'letter_date'   => 'Letter Date',
        'subject'       => 'Subject',
        'status'        => 'Status',
        'body'          => 'Letter Content',
    ],

    'actions' => [
        'create'  => 'Create New Letter',
        'edit'    => 'Edit',
        'delete'  => 'Delete',
        'print'   => 'Print Letter',
        'preview' => 'Preview',
    ],

    'placeholders' => [
        'auto'    => 'Auto-generated',
        'subject' => 'e.g. Request for Clarification, Notice, Cover Letter...',
        'body'    => 'Write the letter content here...',
    ],
];
