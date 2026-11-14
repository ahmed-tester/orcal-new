<?php

return [
    'title' => 'Company & Department Performance',
    'navigation' => 'Company & Department Performance',
    'subtitle' => 'Comprehensive operational monitoring of services, deficiencies, and administrative tasks across companies and departments.',

    // Cards
    'cards' => [
        'active_services' => 'Active Services',
        'active_services_hint' => 'Active transactions during the period by transaction date',
        'completed_services' => 'Completed Services',
        'completed_services_hint' => 'Operationally completed transactions in the period',
        'overdue_services' => 'Overdue Services',
        'overdue_services_hint' => 'Transactions past due date and before today',
        'on_time_rate' => 'On-Time Rate',
        'on_time_rate_hint' => 'Completed on time ÷ Completed with due date',
        'open_deficiencies' => 'Open Deficiencies',
        'open_deficiencies_hint' => 'Open transaction deficiencies logged in the period',
        'tasks' => 'Administrative Tasks',
        'tasks_completion_rate' => 'Task Completion',
        'tasks_completion_rate_hint' => 'Completed by period end ÷ Due in period',
        'no_data' => 'Insufficient Data',
    ],

    'overview' => [
        'navigation' => 'Performance dashboard sections',
        'operations' => 'Operations Overview',
        'workforce' => 'Attendance and Leave',
        'attendance_month' => 'Attendance Summary for :month',
        'leaves_year' => 'Leave Summary for :year',
        'attendance_details' => 'Open Attendance Summary',
        'leaves_details' => 'Open Leave Summary',
        'approved_leave_days' => 'Approved Leave Days',
        'period_note' => 'The report start date sets the attendance month and leave year. Leave status today uses the current date. Text search and department navigation apply to the operations overview.',
    ],

    // Filters & Notes
    'filters' => [
        'title' => 'Filter & Search Performance',
        'company' => 'Company',
        'branch' => 'Branch',
        'date_from' => 'From Date',
        'date_to' => 'To Date',
        'search' => 'Search...',
        'branch_note' => 'Services are filtered by current registered beneficiary branch. Transactions with undetermined branch do not appear when a branch is selected.',
        'all_companies' => 'All Authorized Companies',
        'all_branches' => 'All Branches',
        'reset' => 'Reset',
    ],

    // Tables & Navigation
    'levels' => [
        'companies' => 'Companies',
        'departments' => 'Departments of :company',
        'employees' => 'Employees of :department (:company)',
        'back_to_companies' => 'Back to Companies',
        'back_to_departments' => 'Back to Departments',
        'view_departments' => 'View Departments',
        'view_employees' => 'View Employees',
        'team_badge' => 'Your Team in Department',
        'unassigned_or_no_department' => 'Unassigned / No Department',
    ],

    // Columns
    'columns' => [
        'company' => 'Company',
        'department' => 'Department',
        'employee' => 'Employee',
        'job_title' => 'Job Title',
        'nationality' => 'Nationality',
        'saudi' => 'Saudi',
        'foreign' => 'Foreign',
        'active_services' => 'Active Services',
        'completed_services' => 'Completed Services',
        'overdue_services' => 'Overdue Services',
        'on_time_rate' => 'On Time',
        'open_deficiencies' => 'Deficiencies',
        'tasks_due' => 'Tasks Due',
        'tasks_completed' => 'Tasks Completed',
        'task_completion_rate' => 'Task Completion',
        'employees_count' => 'Employees',
        'departments_count' => 'Departments',
        'actions' => 'Actions',
        'view_profile' => 'Performance Profile',
        'view_statement' => 'Statement',
    ],

    // Statements / Kashf
    'kashf' => [
        'title' => 'Detailed Records Statement',
        'services_title' => 'Services Statement (:type)',
        'deficiencies_title' => 'Deficiencies Statement',
        'tasks_title' => 'Tasks Statement (:type)',
        'transaction_number' => 'Transaction #',
        'service' => 'Service',
        'beneficiary' => 'Beneficiary',
        'executor' => 'Executor',
        'status' => 'Status',
        'transaction_date' => 'Transaction Date',
        'due_date' => 'Due Date',
        'completion_date' => 'Completion Date',
        'pending_action' => 'Pending Action',
        'deficiency_type' => 'Deficiency Type',
        'responsible' => 'Responsible',
        'task_name' => 'Task Name',
        'assignment_number' => 'Assignment #',
        'weight' => 'Weight',
        'score' => 'Score',
        'empty' => 'No matching records.',
        'close' => 'Close',
    ],

    'types' => [
        'due' => 'Due',
        'active' => 'Active',
        'completed' => 'Completed',
        'overdue' => 'Overdue',
        'on_time' => 'On-Time',
        'all' => 'All',
        'postponed' => 'Postponed',
    ],

    // Empty States
    'empty' => [
        'hint' => 'Try adjusting search criteria or selecting another time period.',
        'no_departments' => 'No departments registered under this company matching current filter.',
        'no_employees' => 'No employees registered under this department matching current filter.',
    ],

    // Dynamic Fallbacks
    'fallback' => [
        'company' => 'Company #:id',
        'employee' => 'Employee #:id',
    ],

    // Supervisors View
    'supervisors' => [
        'title' => 'Supervisor & Beneficiary Evaluations',
        'departments_summary' => 'Summary of Departments Shown',
        'average_on_time_rate' => 'Average On-Time Completion',
        'average_task_completion_rate' => 'Average Assignment Completion',
        'completed_services_share' => 'Share of Completed Services',
        'completed_services_share_hint' => 'Completed out of active and completed',
        'enter_department' => 'View Department Employees',
        'view_details' => 'View Service Records',
        'department_lead' => 'Department Lead',
        'lead_keywords' => ['Manager', 'Supervisor', 'Lead', 'Head'],
    ],
];
