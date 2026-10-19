<?php

return [
    'single' => 'Official Letter',
    'plural' => 'Official Letters',
    'title'  => 'Official Letters',

    'sections' => [
        'basic_info'         => 'Letter Details',
        'basic_info_desc'    => 'Select the company, letter number, date, subject, and status',
        'body'               => 'Letter Content',
        'body_desc'          => 'Write the official letter content using the rich text editor',
        'print_options'      => 'Print & Display Options',
        'print_options_desc' => 'Configure visibility for logo, stamp, and signature section',
    ],

    'statuses' => [
        'draft'    => 'Draft',
        'approved' => 'Approved',
        'sent'     => 'Sent',
    ],

    'fields' => [
        'company'        => 'Company',
        'letter_number'  => 'Letter Number',
        'letter_date'    => 'Letter Date',
        'subject'        => 'Subject',
        'status'         => 'Status',
        'body'           => 'Letter Content',
        'show_logo'      => 'Show Logo',
        'show_stamp'     => 'Show Stamp',
        'show_signature' => 'Show Signature Section',
    ],

    'helpers' => [
        'show_logo'      => 'Display company logo at the top of the letter',
        'show_stamp'     => 'Display official company stamp at the bottom of the letter',
        'show_signature' => 'Display signature section (company name, signature line, phone)',
    ],

    'modals' => [
        'print_heading'        => 'Letter Print Options',
        'print_description'    => 'Select the elements you want to display on the letter before previewing',
        'print_submit'         => 'Preview & Print',
        'download_heading'     => 'Download Letter PDF',
        'download_description' => 'Select the elements you want to include in the downloaded PDF',
        'download_submit'      => 'Download PDF',
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
