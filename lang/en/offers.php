<?php

return [
    'single'  => 'Offer / Contract',
    'plural'  => 'Offers & Contracts',
    'currency' => 'SAR',
    'currency_symbol' => 'SAR',

    'sections' => [
        'offer_info'       => 'Basic Document Information',
        'offer_info_desc'  => 'Document type, company, customer, dates and subject',
        'work_scope'       => 'Scope of Work & Supply',
        'work_scope_desc'  => 'Define supply scope, labor, work description and duration',
        'items'            => 'Items, Specifications & Pricing',
        'items_desc'       => 'Add items, quantities, units, specifications and prices',
        'pricing_tax'      => 'Pricing & Tax Options',
        'terms_section'    => 'Document Content & Details (Pricing, Specs, Clauses)',
        'terms_desc'       => 'Compose and customize the pricing table, technical specifications, payment terms, and clauses',
        'responsible'      => 'Responsible Persons, Signatures & Stamp',
        'responsible_desc' => 'Executive manager, site supervisor, and official stamp settings',
        'notes_section'    => 'Additional Notes',
    ],

    'fields' => [
        'offer_type'          => 'Document / Form Type',
        'offer_number'        => 'Document Number',
        'company'             => 'Issuing Company (First Party)',
        'customer'            => 'Customer (Second Party)',
        'subject'             => 'Subject / Title',
        'description'         => 'Document Preamble',
        'work_scope'          => 'Work & Supply Scope',
        'supplied_materials'  => 'Supplied Materials Statement',
        'scope_description'   => 'Scope & Site Description',
        'pricing_type'        => 'Pricing Type',
        'is_tax_inclusive'    => 'Tax Inclusive',
        'tax_rate'            => 'Tax Rate (%)',
        'execution_duration'  => 'Execution Duration',
        'terms_and_conditions'=> 'Document / Contract Content',
        'offer_date'          => 'Issue Date',
        'valid_until'         => 'Valid Until',
        'status'              => 'Status',
        'manager'             => 'Executive Manager',
        'supervisor'          => 'Site Supervisor',
        'show_stamp'          => 'Show Official Stamp on Print',
        'created_by'          => 'Created By',
        'notes'               => 'Notes',

        // Items
        'sort_order'          => '#',
        'item_name'           => 'Item Name',
        'specifications'      => 'Specifications',
        'quantity'            => 'Quantity',
        'unit'                => 'Unit',
        'unit_price'          => 'Unit Price',
        'price'               => 'Total Price',
        'add_item'            => 'Add New Item',
        'total'               => 'Total',
        'subtotal'            => 'Subtotal',
        'tax_amount'          => 'VAT Amount',
        'grand_total'         => 'Grand Total',
    ],

    'types' => [
        'quotation'          => 'Quotation',
        'supply_offer'       => 'Supply Offer',
        'unified_contract'   => 'Unified Contracting Contract',
        'agreement_contract' => 'Agreement Contract',
        'sale_agreement'     => 'Sale Agreement Contract',
    ],

    'scopes' => [
        'materials_and_labor'        => 'Materials Supply with Labor',
        'labor_with_some_materials'  => 'Labor with Partial Materials Supply',
        'labor_only'                 => 'Labor Only',
    ],

    'pricing_types' => [
        'lump_sum'  => 'Lump Sum Total',
        'per_meter' => 'Per Square / Linear Meter',
        'per_unit'  => 'Per Unit / Piece',
    ],

    'statuses' => [
        'draft'     => 'Draft',
        'sent'      => 'Sent',
        'accepted'  => 'Accepted',
        'rejected'  => 'Rejected',
        'cancelled' => 'Cancelled',
    ],

    'actions' => [
        'create'          => 'Create New Document',
        'edit'            => 'Edit',
        'delete'          => 'Delete',
        'delete_selected' => 'Delete Selected',
        'print_preview'   => 'Print & Preview',
        'download_pdf'    => 'Download PDF',
        'reload_template' => 'Reset Contract Clauses to Default Template',
    ],

    'placeholders' => [
        'auto'                => 'Auto',
        'subject'             => 'e.g. Supply and installation of gypsum board for project...',
        'description'         => 'Introductory overview to appear before the works table...',
        'duration'            => 'e.g. 30 business days / 2 months',
        'supplied_materials'  => 'Write the materials to be supplied in detail...',
        'scope_description'   => 'Detailed description of works, site location and inspection...',
        'unit'                => 'm2 / m / pc',
        'specifications'      => 'Detailed technical specifications for the item...',
        'terms'               => "1- First Party Obligations: ...\n2- Second Party Obligations: ...\n3- Payment Schedule: ...",
    ],

    'stamp' => [
        'uploaded_notice' => '✓ The uploaded official stamp above will be included in the print file',
        'no_stamp_notice' => '⚠️ No official stamp uploaded for this company (an electronic placeholder will be shown)',
        'default_helper'  => 'Show official stamp in print and PDF documents',
    ],
];
