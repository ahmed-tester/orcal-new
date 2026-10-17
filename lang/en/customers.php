<?php

return [
    'single' => 'Customer',
    'plural' => 'Customers',
    'sections' => [
        'basic_info' => 'Basic Customer Information',
        'basic_info_desc' => 'Define customer type (individual/company), name and identification data',
        'contact_info' => 'Contact & Address Information',
        'contact_info_desc' => 'Phone, email and national address',
    ],
    'fields' => [
        'customer_type' => 'Customer Type',
        'name' => 'Name',
        'national_id' => 'National ID / Iqama',
        'cr_number' => 'Commercial Registration No.',
        'tax_number' => 'Tax Number',
        'phone' => 'Phone / Mobile',
        'email' => 'Email',
        'address' => 'Address',
        'city' => 'City',
        'status' => 'Status',
        'notes' => 'Additional Notes',
    ],
    'types' => [
        'individual' => 'Individual',
        'company' => 'Company',
        'establishment' => 'Establishment',
        'government' => 'Government Entity',
        'other' => 'Other',
    ],
    'statuses' => [
        'active' => 'Active',
        'inactive' => 'Inactive',
    ],
];
