<?php

return [
    'single' => 'Setting',
    'plural' => 'System Settings',
    'settings' => 'General Settings',

    'sections' => [
        'general' => 'Basic Setting Information',
        'general_desc' => 'Key, classification group, and user-facing label',
        'value_and_type' => 'Field Type & Stored Value',
        'value_and_type_desc' => 'Specify the input component type, value, and options',
    ],

    'groups' => [
        'general' => 'General Settings',
        'appearance' => 'Appearance & Branding',
        'alerts' => 'Alerts & Notifications',
        'uploads' => 'File Uploads & Documents',
        'ai' => 'AI & Integrations',
        'hr' => 'HR & Payroll',
        'company' => 'Entities & Branches',
        'contracts' => 'Contracting & Agreements',
    ],

    'input_types' => [
        'text' => 'Short Text',
        'textarea' => 'Multi-line Text',
        'number' => 'Number',
        'toggle' => 'Toggle (Yes / No)',
        'select' => 'Dropdown (Select)',
        'color' => 'Color Picker',
        'file' => 'File / Image',
        'date' => 'Date',
    ],

    'fields' => [
        'key' => 'Setting Key',
        'label' => 'Display Label',
        'group' => 'Setting Group',
        'input_type' => 'Input Type',
        'value' => 'Setting Value',
        'options' => 'Options (JSON or Key-Value)',
        'description' => 'Description / Help Text',
        'is_active' => 'Active Status (Enabled / Disabled)',
        'created_at' => 'Created At',
        'updated_at' => 'Updated At',
    ],

    'helpers' => [
        'key' => 'Unique key in English e.g. site_name or iqama_alert_days',
        'options' => 'For dropdown fields, enter options in JSON format or key-value pairs',
        'is_active' => 'When toggled off, this setting is temporarily deactivated without deletion',
    ],

    'manage' => [
        'title' => 'System Settings',
        'save_changes' => 'Save Changes',
        'save_success' => 'Settings have been saved successfully.',

        'general' => [
            'section_desc' => 'Core system data, organization details, and official communications',
            'site_name' => 'System / Organization Name',
            'site_name_helper' => 'Official name displayed in the top header and on official report letterheads',
            'company_phone' => 'General Contact Phone',
            'company_phone_helper' => 'Primary company phone number to print on contracts and quotation templates',
            'company_email' => 'Official Email',
            'company_email_helper' => 'Approved email address for official correspondences',
        ],

        'alerts' => [
            'section_desc' => 'Configure automated reminder thresholds for Iqama expiries and official documents',
            'iqama_alert_first_days' => 'First Iqama Expiry Reminder (Days)',
            'iqama_alert_first_days_helper' => 'Days remaining before sending the first reminder and updating status color',
            'iqama_alert_second_days' => 'Second Iqama Expiry Reminder (Days)',
            'iqama_alert_second_days_helper' => 'Days remaining for the second warning and medium alert level',
            'iqama_alert_third_days' => 'Critical Iqama Expiry Reminder (Days)',
            'iqama_alert_third_days_helper' => 'Urgent threshold (final notice) with high-priority notifications',
        ],

        'uploads' => [
            'section_desc' => 'Rules and restrictions for document uploads across the system',
            'allowed_upload_extensions' => 'Allowed File Extensions',
            'allowed_upload_extensions_helper' => 'Enter comma-separated extensions e.g.: pdf,jpg,jpeg,png,docx,xlsx',
            'max_upload_size_kb' => 'Maximum File Size (KB)',
            'max_upload_size_kb_helper' => 'Maximum allowed file size per upload (5120 KB = 5 MB)',
        ],

        'ai' => [
            'section_desc' => 'Connection settings with Google Gemini AI models for smart document analysis',
            'gemini_api_key' => 'Google Gemini API Key',
            'gemini_api_key_helper' => 'Secret key for Google Gemini AI to analyze and import documents',
            'gemini_model' => 'AI Model (Gemini Model)',
            'gemini_model_helper' => 'Choose the AI model used for processing and importing files',
            'models' => [
                'gemini_3_5_flash' => 'Gemini 3.5 Flash (Standard Model ⚡)',
                'gemini_3_6_flash' => 'Gemini 3.6 Flash',
                'gemini_3_7_flash' => 'Gemini 3.7 Flash',
                'gemini_3_8_flash' => 'Gemini 3.8 Flash',
                'gemini_3_5_flash_lite' => 'Gemini 3.5 Flash Lite (Fast & Lightweight 🚀)',
            ],
            'test_btn' => '⚡ Test Connection with Key & Selected Model',
            'test_header_btn' => 'Test Connection ⚡',
            'no_api_key_title' => 'Notice: Missing API Key',
            'no_api_key_body' => 'Please enter a Google Gemini API Key first to run the connection test.',
            'test_success_title' => 'Connection Successful! 🚀',
            'test_success_body' => 'API Key verified successfully, model (:model) is ready to process data.',
            'test_failed_title' => 'Connection to Google Gemini Failed ❌',
            'test_failed_body' => 'Reason: :error',
            'test_error_title' => 'Connection Error ❌',

            'errors' => [
                'invalid_api_key' => 'Invalid API key. Please ensure you copied the key correctly from Google AI Studio.',
                'quota_exceeded' => 'Your Google account quota has been exceeded (Quota Exceeded).',
                'model_not_found' => 'The selected model is currently unavailable or deprecated by Google.',
                'permission_denied' => 'Permission denied. Please verify Generative Language API is enabled.',
            ],
        ],
    ],
];
