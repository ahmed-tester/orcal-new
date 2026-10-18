<!-- Section Header -->
<div class="doc-section-head">
    <div class="doc-section-title-group">
        <span class="doc-section-tag">{{ __('documentation.letters.tag') }}</span>
        <h2 class="doc-section-title">{{ __('documentation.letters.title') }}</h2>
    </div>

    <div class="doc-actions-group">
        <a href="{{ \App\Filament\Resources\OfficialLetters\OfficialLetterResource::getUrl('create') }}" class="doc-btn doc-btn-primary">
            <span class="doc-btn-icon">+</span>
            <span>{{ __('documentation.letters.create_btn') }}</span>
        </a>
        <a href="{{ \App\Filament\Resources\OfficialLetters\OfficialLetterResource::getUrl('index') }}" class="doc-btn doc-btn-secondary">
            <span>{{ __('documentation.letters.list_btn') }}</span>
        </a>
    </div>
</div>

<!-- 1. General Concept -->
<div class="doc-concept-card">
    <div class="doc-concept-header">
        <span class="doc-concept-icon">💡</span>
        <span class="doc-concept-badge">{{ __('documentation.letters.concept_label') }}</span>
    </div>
    <p class="doc-concept-text">
        {{ __('documentation.letters.concept_text') }}
    </p>
</div>

<!-- 2. Step-by-Step Operating Workflow -->
<div class="doc-workflow-section">
    <div class="doc-workflow-header-wrap">
        <h3 class="doc-workflow-heading">
            <span class="doc-heading-icon">📋</span>
            <span>{{ __('documentation.letters.workflow_label') }}</span>
        </h3>
        <span class="doc-workflow-subtag">دليل إرشادي تفاعلي من 4 خطوات</span>
    </div>

    <div class="doc-steps-grid">
        <!-- Step 1 -->
        <div class="doc-step-card">
            <div class="doc-step-header">
                <span class="doc-step-num">{{ __('documentation.letters.step_1_num') }}</span>
                <h4>{{ __('documentation.letters.step_1_title') }}</h4>
            </div>
            <p>{{ __('documentation.letters.step_1_desc') }}</p>
        </div>

        <!-- Step 2 -->
        <div class="doc-step-card">
            <div class="doc-step-header">
                <span class="doc-step-num">{{ __('documentation.letters.step_2_num') }}</span>
                <h4>{{ __('documentation.letters.step_2_title') }}</h4>
            </div>
            <p>{{ __('documentation.letters.step_2_desc') }}</p>
        </div>

        <!-- Step 3 -->
        <div class="doc-step-card">
            <div class="doc-step-header">
                <span class="doc-step-num">{{ __('documentation.letters.step_3_num') }}</span>
                <h4>{{ __('documentation.letters.step_3_title') }}</h4>
            </div>
            <p>{{ __('documentation.letters.step_3_desc') }}</p>
        </div>

        <!-- Step 4 -->
        <div class="doc-step-card">
            <div class="doc-step-header">
                <span class="doc-step-num">{{ __('documentation.letters.step_4_num') }}</span>
                <h4>{{ __('documentation.letters.step_4_title') }}</h4>
            </div>
            <p>{{ __('documentation.letters.step_4_desc') }}</p>
        </div>
    </div>
</div>

<!-- 3. Key Administrative Benefits -->
<div class="doc-advantages-box">
    <div class="doc-advantages-header">
        <span class="doc-adv-title-icon">✨</span>
        <h4 class="doc-advantages-title">{{ __('documentation.letters.advantages_label') }}</h4>
    </div>

    <div class="doc-advantages-grid">
        <div class="doc-adv-card">
            <div class="doc-adv-badge-icon">🏢</div>
            <div class="doc-adv-content">
                <strong>{{ __('documentation.letters.adv_1_title') }}</strong>
                <p>{{ __('documentation.letters.adv_1_desc') }}</p>
            </div>
        </div>

        <div class="doc-adv-card">
            <div class="doc-adv-badge-icon">🔢</div>
            <div class="doc-adv-content">
                <strong>{{ __('documentation.letters.adv_2_title') }}</strong>
                <p>{{ __('documentation.letters.adv_2_desc') }}</p>
            </div>
        </div>

        <div class="doc-adv-card">
            <div class="doc-adv-badge-icon">📝</div>
            <div class="doc-adv-content">
                <strong>{{ __('documentation.letters.adv_3_title') }}</strong>
                <p>{{ __('documentation.letters.adv_3_desc') }}</p>
            </div>
        </div>

        <div class="doc-adv-card">
            <div class="doc-adv-badge-icon">📄</div>
            <div class="doc-adv-content">
                <strong>{{ __('documentation.letters.adv_4_title') }}</strong>
                <p>{{ __('documentation.letters.adv_4_desc') }}</p>
            </div>
        </div>
    </div>
</div>
