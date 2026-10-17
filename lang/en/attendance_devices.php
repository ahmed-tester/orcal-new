<?php

return [
    'single' => 'Biometric Device',
    'plural' => 'Biometric Devices',
    'navigation_label' => 'Biometric Devices',
    'sections' => [
        'device_info' => 'Biometric Device Info',
        'device_info_desc' => 'Connection and identifier settings (ZKTeco ADMS / Push)',
    ],
    'fields' => [
        'name' => 'Device Name / Location',
        'code' => 'Serial Number (SN)',
        'code_helper' => 'Must match the Serial Number on the device exactly',
        'company' => 'Company',
        'branch' => 'Branch',
        'model' => 'Device Model',
        'device_type' => 'Verification Type',
        'ip_address' => 'Local IP Address (Optional)',
        'location' => 'Installation Location',
        'status' => 'Device Status',
        'last_seen_at' => 'Last Heartbeat / Connection',
    ],
    'placeholders' => [
        'name' => 'e.g. Main Entrance Device - Riyadh',
        'code' => 'e.g. CL542300188 or ZK-SIM-001',
        'model' => 'e.g. ZKTeco F34 Lite / MB20 / MB460',
        'ip' => 'e.g. 192.168.1.200',
        'location' => 'e.g. Gate 1',
        'unspecified' => 'Unspecified',
    ],
    'types' => [
        'fingerprint' => 'Fingerprint Only',
        'face' => 'Face Recognition Only',
        'fingerprint_face' => 'Fingerprint + Face',
        'rfid_card' => 'RFID Card / PIN',
    ],
    'statuses' => [
        'active' => 'Active',
        'inactive' => 'Inactive',
        'maintenance' => 'Maintenance',
    ],
    'actions' => [
        'create' => 'Add New Biometric Device',
    ],
];
