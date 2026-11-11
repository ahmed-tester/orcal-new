<?php

return [
    'single' => 'Owner',
    'plural' => 'Owners',

    'sections' => [
        'info' => 'Basic Owner Information',
        'financial_info' => 'Owner Financial Information',
    ],

    'fields' => [
        'user' => 'User Account',
        'name' => 'Owner Name',
        'name_en' => 'Name (English)',
        'name_en_placeholder' => 'Enter English name (e.g. Mohammed Ali)',
        'email' => 'Email Address',
        'phone' => 'Phone Number',
        'password' => 'Password',
        'password_help' => 'Leave blank to keep current password',
        'company' => 'Company / Entity',
        'national_id' => 'National ID / Iqama',
        'nationality' => 'Nationality',
        'birth_date' => 'Birth Date',
        'ownership_percentage' => 'Ownership Percentage (%)',
        'employment_date' => 'Ownership Start Date',
        'termination_date' => 'Ownership End Date',
        'status' => 'Status',
        'image' => 'Owner / ID Photo',
        'address' => 'Address',
        'monthly_deduction_percentage' => 'Monthly Deduction Percentage for Owner',
        'owner_advances' => 'Owner Advances',
        'debts' => 'Debts / Liabilities',
        'other_financials' => 'Free Notes',
        'other_financials_placeholder' => 'Enter any free notes...',
        'person_capacity' => 'Capacity',
        'capacity_owner' => 'Establishment Owner',
        'capacity_employee_and_owner' => 'Employee & Establishment Owner',
        'registration_source' => 'Registration Source',
        'registration_manual' => 'Manual Registration',
        'registration_from_saudi_employee' => 'Import from Saudi Employees',
        'selected_employee' => 'Saudi Employee',
        'selected_employee_placeholder' => 'Search by name or national ID...',
        'password_help_employee' => 'Optional, leave blank to keep employee\'s current password',
        'errors' => [
            'employee_data_unavailable' => 'Selected employee data is unavailable.',
            'employee_already_owner' => 'This employee is already registered as an owner in this company.',
        ],
    ],

    'statuses' => [
        'active' => 'Active',
        'inactive' => 'Inactive',
    ],
    'edit_title' => 'Edit Owner Information',
    'deleted_successfully' => 'Owner deleted successfully',
];
