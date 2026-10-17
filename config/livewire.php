<?php

return [

    /*
    |---------------------------------------------------------------------------
    | Component Locations
    |---------------------------------------------------------------------------
    |
    | This value sets the root directories that'll be used to resolve view-based
    | components like single and multi-file components. The make command will
    | use the first directory in this array to add new component files to.
    |
    */

    'component_locations' => [
        resource_path('views/components'),
        resource_path('views/livewire'),
    ],

    /*
    |---------------------------------------------------------------------------
    | Component Namespaces
    |---------------------------------------------------------------------------
    |
    | This value sets default namespaces that will be used to resolve view-based
    | components like single-file and multi-file components. These folders'll
    | also be referenced when creating new components via the make command.
    |
    */

    'component_namespaces' => [
        'layouts' => resource_path('views/layouts'),
        'pages' => resource_path('views/pages'),
    ],

    /*
    |---------------------------------------------------------------------------
    | Page Layout
    |---------------------------------------------------------------------------
    | The view that will be used as the layout when rendering a single component as
    | an entire page via `Route::livewire('/post/create', 'pages::create-post')`.
    | In this case, the content of pages::create-post will render into $slot.
    |
    */

    'component_layout' => 'layouts::app',

    /*
    |---------------------------------------------------------------------------
    | Lazy Loading Placeholder
    |---------------------------------------------------------------------------
    | Livewire allows you to lazy load components that would otherwise slow down
    | the initial page load. Every component can have a custom placeholder or
    | you can define the default placeholder view for all components below.
    |
    */

    'component_placeholder' => null,

    /*
    |---------------------------------------------------------------------------
    | Make Command
    |---------------------------------------------------------------------------
    | This value determines the default configuration for the artisan make command
    |
    */

    'make_command' => [
        'type' => 'sfc',
        'emoji' => true,
        'with' => [
            'js' => false,
            'css' => false,
            'test' => false,
        ],
    ],

    /*
    |---------------------------------------------------------------------------
    | Class Namespace
    |---------------------------------------------------------------------------
    |
    */

    'class_namespace' => 'App\\Livewire',

    /*
    |---------------------------------------------------------------------------
    | Class Path
    |---------------------------------------------------------------------------
    |
    */

    'class_path' => app_path('Livewire'),

    /*
    |---------------------------------------------------------------------------
    | View Path
    |---------------------------------------------------------------------------
    |
    */

    'view_path' => resource_path('views/livewire'),

    /*
    |---------------------------------------------------------------------------
    | Temporary File Uploads
    |---------------------------------------------------------------------------
    |
    */

    'temporary_file_upload' => [
        'disk' => env('LIVEWIRE_TEMPORARY_FILE_UPLOAD_DISK'),
        'rules' => null,
        'directory' => null,
        'middleware' => null,
        'preview_mimes' => [
            'png', 'gif', 'bmp', 'svg', 'wav', 'mp4',
            'mov', 'avi', 'wmv', 'mp3', 'm4a',
            'jpg', 'jpeg', 'mpga', 'webp', 'wma',
        ],
        'max_upload_time' => 5,
        'cleanup' => true,
    ],

    /*
    |---------------------------------------------------------------------------
    | Render On Redirect
    |---------------------------------------------------------------------------
    |
    */

    'render_on_redirect' => false,

    /*
    |---------------------------------------------------------------------------
    | Eloquent Model Binding
    |---------------------------------------------------------------------------
    |
    */

    'legacy_model_binding' => false,

    /*
    |---------------------------------------------------------------------------
    | Auto-inject Frontend Assets
    |---------------------------------------------------------------------------
    |
    */

    'inject_assets' => true,

    /*
    |---------------------------------------------------------------------------
    | Navigate (SPA mode)
    |---------------------------------------------------------------------------
    |
    */

    'navigate' => [
        'show_progress_bar' => true,
        'progress_bar_color' => '#2299dd',
    ],

    /*
    |---------------------------------------------------------------------------
    | HTML Morph Markers
    |---------------------------------------------------------------------------
    |
    */

    'inject_morph_markers' => true,

    /*
    |---------------------------------------------------------------------------
    | Smart Wire Keys
    |---------------------------------------------------------------------------
    |
    */

    'smart_wire_keys' => true,

    /*
    |---------------------------------------------------------------------------
    | Pagination Theme
    |---------------------------------------------------------------------------
    |
    */

    'pagination_theme' => 'tailwind',

    /*
    |---------------------------------------------------------------------------
    | Release Token
    |---------------------------------------------------------------------------
    |
    */

    'release_token' => 'a',

    /*
    |---------------------------------------------------------------------------
    | CSP Safe
    |---------------------------------------------------------------------------
    |
    */

    'csp_safe' => false,

    /*
    |---------------------------------------------------------------------------
    | Payload Guards
    |---------------------------------------------------------------------------
    |
    | These settings protect against malicious or oversized payloads that could
    | cause denial of service. The default values should feel reasonable for
    | most web applications. Each can be set to null to disable the limit.
    |
    */

    'payload' => [
        'max_size' => 10 * 1024 * 1024,  // 10MB - expanded payload size
        'max_nesting_depth' => 100,       // Support deep JSON trees from Rich Editors
        'max_calls' => 100,
        'max_components' => 300,
    ],
];
