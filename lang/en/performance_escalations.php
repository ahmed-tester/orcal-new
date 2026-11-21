<?php

return [
    'model_label' => 'Performance Escalation',
    'model_plural_label' => 'Performance Escalations',
    'navigation' => 'Performance Escalations',
    'subject_missing' => 'Subject unavailable',

    'subject_types' => [
        'department' => 'Department',
        'employee' => 'Employee',
        'branch' => 'Branch',
        'supervisor' => 'Supervisor',
    ],

    'rules' => [
        'department_operational' => 'Department operational score',
    ],

    'statuses' => [
        'open' => 'Open',
        'closed' => 'Closed',
    ],

    'closure_reasons' => [
        'recovered' => 'Recovered',
        'manual' => 'Manual close',
    ],

    'fields' => [
        'company' => 'Company',
        'subject_type' => 'Subject type',
        'subject' => 'Subject',
        'status' => 'Status',
        'score_at_open' => 'Score at open',
        'threshold' => 'Threshold',
        'rule_key' => 'Escalation rule',
        'rules_version' => 'Calculation rules version',
        'opened_at' => 'Opened at',
        'management_action' => 'Management action',
        'management_action_by_user_id' => 'Action owner',
        'management_action_at' => 'Action time',
        'closure_reason' => 'Closure reason',
        'close_reason' => 'Closure notes',
        'close_score' => 'Score at close',
        'closed_at' => 'Closed at',
        'closed_by_user_id' => 'Closed by',
        'notified_at' => 'Notified at',
    ],

    'sections' => [
        'case' => 'Case details',
        'closure' => 'Closure details',
    ],

    'actions' => [
        'record_action' => 'Record management action',
        'record_action_heading' => 'Record management action',
        'record_action_description' => 'Records the taken action while the case stays open for follow-up.',
    ],

    'notifications' => [
        'opened_title' => 'New performance escalation',
        'opened_body' => 'A performance escalation was opened for :subject in :company with a score of :score below the threshold :threshold. Management action is required.',
        'action_recorded' => 'The management action was recorded on the escalation case.',
    ],

    'commands' => [
        'opened' => 'Opened cases: :count',
        'closed' => 'Cases closed by recovery: :count',
        'notified' => 'Notifications sent: :count',
        'failed_notifications' => ':count notification(s) failed and will retry on the next run.',
        'summary' => 'Checked :departments department(s) inside :companies company/companies.',
    ],
];
