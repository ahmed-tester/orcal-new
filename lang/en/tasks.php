<?php

return [
    'single' => 'Task',
    'plural' => 'Tasks Catalog',
    'assignment_single' => 'Task Assignment',
    'assignment_plural' => 'Task Assignments & Tracking',
    
    'sections' => [
        'info' => 'Task Information',
        'info_desc' => 'Basic task definition and attributes',
        'assignment_header' => 'Assignment & Assignee Details',
        'assignment_header_desc' => 'Assignee employee and execution timeframe',
        'tasks_list' => 'Tasks Breakdown & Weights',
        'tasks_list_desc' => 'Assign specific tasks, weights, scores, and execution statuses',
        'summary' => 'Evaluation & Weights Summary',
    ],

    'fields' => [
        'task_code' => 'Task Code / Ref',
        'name' => 'Task Name',
        'default_weight' => 'Default Weight',
        'description' => 'Description & Notes',
        'status' => 'Status',

        'transaction_number' => 'Transaction No.',
        'assignment_date' => 'Assignment Date',
        'employee' => 'Responsible Employee (Assignee)',
        'from_date' => 'From Date',
        'to_date' => 'To Date',
        'is_completed' => 'Overall Completed',
        'total_tasks' => 'Total Tasks Count',
        'total_weight' => 'Total Weight',
        'total_score' => 'Total Score',
        'overall_percentage' => 'Overall Score (%)',
        'notes' => 'General Notes',
        'created_by' => 'Created By',

        'task' => 'Task',
        'item_status' => 'Task Status',
        'weight' => 'Relative Weight (%)',
        'score' => 'Achieved Score',
        'score_percentage' => 'Score Percentage (%)',
        'due_date' => 'Due Date',
        'completed_at' => 'Completed At',
    ],

    'statuses' => [
        'pending' => 'Pending / Not Done',
        'in_progress' => 'In Progress / Partial',
        'completed' => 'Completed',
        'postponed' => 'Postponed / Deferred',
    ],

    'helpers' => [
        'weight_limit_hint' => 'Total maximum allowed weight is 100%',
        'calculated_percentage' => 'Calculated: (Score / Weight) * 100',
    ],

    'creator_default' => 'Management',

    'notifications' => [
        'new_assignment_title' => 'New Task Assignment',
        'new_assignment_body' => 'A new task assignment (:number) has been assigned to you by :creator.',
        'view_assignment' => 'View Assignment',
    ],
];
