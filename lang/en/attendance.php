<?php

return [
    'single' => 'Attendance Record',
    'plural' => 'Attendance & Departure',
    'navigation_label' => 'Attendance',
    'title' => 'Attendance & Departure',
    'group' => 'Human Resources',
    'breadcrumb' => 'Human Resources',

    'sections' => [
        'entry_data' => 'Employee Attendance & Departure Entry',
        'records_history' => 'Attendance Records — :date',
        'employee_history' => 'Attendance Records for (:name)',
        'date_history' => 'Attendance Records on (:date)',
    ],

    'tags' => [
        'branch' => 'Branch: :branch',
        'super_admin' => 'All Branches (Super Admin)',
    ],

    'fields' => [
        'record_number' => 'Record Number',
        'employee' => 'Employee Name',
        'select_employee' => '-- Select Employee --',
        'date' => 'Date',
        'check_in' => 'Check In Time',
        'check_out' => 'Check Out Time',
        'late_hours' => 'Late Minutes',
        'overtime_hours' => 'Overtime Hours',
        'total_hours' => 'Total Hours',
        'source' => 'Source',
        'device' => 'Biometric Device',
        'status' => 'Attendance Status',
        'notes' => 'Notes',
    ],

    'placeholders' => [
        'manual_no_device' => 'Manual / No Device',
    ],

    'units' => [
        'hour' => 'Hour',
        'hours' => 'Hours',
        'hour_short' => 'h',
        'minute' => 'min',
        'minutes' => 'mins',
    ],

    'statuses' => [
        'present'       => 'Present on Time',
        'present_short' => 'Present',
        'late'          => 'Late Arrival',
        'late_short'    => 'Late',
        'absent'        => 'Absent',
        'absent_short'  => 'Absent',
        'excused'       => 'Excused Absence',
        'excused_short' => 'Excused',
        'leave'         => 'On Approved Leave',
        'leave_short'   => 'Leave',
    ],

    'sources' => [
        'fingerprint' => 'Biometric Device',
        'manual'      => 'Manual Entry',
        'system'      => 'System (Auto)',
    ],

    'actions' => [
        'save' => 'Save Record',
        'update' => 'Update Record',
        'new_record' => 'New Record',
        'delete' => 'Delete',
        'check_absent' => 'Check & Record Absent',
        'checking_absent' => 'Checking...',
        'check_absent_tooltip' => 'Automatically check and mark absent employees for the branch',
        'simulate_biometric' => 'Simulate Biometric (Test)',
        'simulate_biometric_heading' => 'Test ZKTeco Biometric Push',
        'simulate_biometric_desc' => 'Simulates an incoming biometric push from a ZKTeco device to test the attendance pipeline.',
        'punch_type' => 'Punch Type',
        'punch_time' => 'Punch Time',
        'punch_types' => [
            'check_in' => 'Check-In',
            'check_out' => 'Check-Out',
        ],
        'export_csv' => 'Export as CSV',
    ],

    'notifications' => [
        'absent_check_success' => 'Absent employees checked and recorded successfully',
        'absent_check_failed' => 'Error checking absent employees: :error',
        'save_success' => 'Attendance record saved successfully',
        'update_success' => 'Attendance record updated successfully',
        'delete_success' => 'Attendance record deleted successfully',
        'delete_confirm' => 'Are you sure you want to permanently delete this record?',
        'edit_mode' => 'Edit mode activated',
    ],

    'validation' => [
        'employee_required' => 'Please select an employee',
        'date_required' => 'Date field is required',
    ],

    'table' => [
        'search_placeholder' => 'Quick search by employee name, number, or date (e.g. 2026-09-13)...',
        'total_records' => 'Total Records:',
        'no_records' => 'No matching attendance records found',
        'selected_record' => 'Selected Record #:id',
        'columns' => [
            'number' => '#',
            'employee' => 'Employee Name',
            'date' => 'Date',
            'check_in' => 'Check In',
            'check_out' => 'Check Out',
            'late_hours' => 'Late',
            'overtime_hours' => 'Overtime',
            'status' => 'Status',
        ],
    ],
];
