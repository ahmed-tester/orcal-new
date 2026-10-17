<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Illuminate\Contracts\Support\Htmlable;

class LoanFundPolicyPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static ?int $navigationSort = 8;

    protected string $view = 'filament.pages.loan-fund-policy';

    public ?array $data = [];

    public static function canAccess(): bool
    {
        $user = auth()->user();
        return $user && ($user->isSuperAdminOrOwner() || $user->can('view_any_loan_fund') || $user->can('view_loan_fund_policy'));
    }

    public function getMaxContentWidth(): Width|string|null
    {
        return Width::Full;
    }

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.funds_group');
    }

    public static function getNavigationLabel(): string
    {
        return __('navigation.loan_fund_policy');
    }

    public function getTitle(): string|Htmlable
    {
        return Setting::get('salifni_policy_title', __('funds.policy_pages.salifni_title'));
    }

    public function mount(): void
    {
        $defaultContent = $this->getDefaultPolicyContent();

        $this->form->fill([
            'title' => Setting::get('salifni_policy_title', __('funds.policy_pages.salifni_title')),
            'subtitle' => Setting::get('salifni_policy_subtitle', __('funds.policy_pages.salifni_subtitle')),
            'content' => Setting::get('salifni_policy_content', $defaultContent),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        $user = auth()->user();
        $canEdit = $user && ($user->isSuperAdminOrOwner() || $user->can('manage_settings') || $user->can('update_setting'));

        return $schema
            ->components([
                Section::make(__('funds.policy_pages.salifni_section_title'))
                    ->description(__('funds.policy_pages.salifni_section_desc'))
                    ->icon('heroicon-o-document-text')
                    ->schema([
                        TextInput::make('title')
                            ->label(__('funds.policy_pages.field_title'))
                            ->required()
                            ->disabled(!$canEdit),

                        TextInput::make('subtitle')
                            ->label(__('funds.policy_pages.field_subtitle'))
                            ->required()
                            ->disabled(!$canEdit),

                        RichEditor::make('content')
                            ->label(__('funds.policy_pages.field_content'))
                            ->placeholder(__('funds.policy_pages.field_content_placeholder'))
                            ->required()
                            ->disabled(!$canEdit)
                            ->columnSpanFull(),
                    ]),
            ])
            ->statePath('data');
    }

    public function getFormActions(): array
    {
        $user = auth()->user();
        $canEdit = $user && ($user->isSuperAdminOrOwner() || $user->can('manage_settings') || $user->can('update_setting'));

        if (!$canEdit) {
            return [];
        }

        return [
            Action::make('save')
                ->label(__('funds.policy_pages.actions.save'))
                ->icon('heroicon-m-check')
                ->color('primary')
                ->submit('savePolicy')
                ->keyBindings(['mod+s']),

            Action::make('reset_default')
                ->label(__('funds.policy_pages.actions.reset_default'))
                ->icon('heroicon-m-arrow-path')
                ->color('gray')
                ->requiresConfirmation()
                ->modalHeading(__('funds.policy_pages.actions.reset_salifni_heading'))
                ->modalDescription(__('funds.policy_pages.actions.reset_modal_desc'))
                ->action(function () {
                    $this->form->fill([
                        'title' => __('funds.policy_pages.salifni_title'),
                        'subtitle' => __('funds.policy_pages.salifni_subtitle'),
                        'content' => $this->getDefaultPolicyContent(),
                    ]);

                    Notification::make()
                        ->title(__('funds.policy_pages.notifications.reset_info'))
                        ->info()
                        ->send();
                }),
        ];
    }

    public function savePolicy(): void
    {
        $formData = $this->form->getState();

        Setting::set('salifni_policy_title', $formData['title'], 'funds', 'salifni_policy_title');
        Setting::set('salifni_policy_subtitle', $formData['subtitle'], 'funds', 'salifni_policy_subtitle');
        Setting::set('salifni_policy_content', $formData['content'], 'funds', 'salifni_policy_content', 'html');

        Notification::make()
            ->title(__('funds.policy_pages.notifications.salifni_saved'))
            ->success()
            ->send();
    }

    protected function getDefaultPolicyContent(): string
    {
        return <<<'HTML'
<h2>1. هدف الصندوق وشروط العضوية</h2>
<ul>
    <li>صندوق سلفني هو صندوق ادخاري تكافلي غير ربحي، يقوم على الاشتراك بمبالغ متساوية أو محددة مسبقاً.</li>
    <li>العضوية اختيارية ومفتوحة للموظفين في المنشأة وكذلك للأعضاء الخارجيين (الأسرة، الأصدقاء، المعارف).</li>
    <li>يلتزم العضو بسداد الأقساط الدورية المتفق عليها في مواعيد استحقاقها المحددة دون تأخير.</li>
</ul>

<h2>2. آلية وضوابط منح السلف</h2>
<ul>
    <li>تُمنح السلف حصراً للأعضاء المشتركين والمنتظمين في سداد اشتراكات الصندوق.</li>
    <li>يحدد أمناء الصندوق الحد الأقصى لمبلغ السلفة وفترة السداد بحسب الملاءة المالية للصندوق.</li>
    <li>يُحسب القسط الشهري بقسمة مبلغ السلفة على عدد الأقساط المعتمدة.</li>
</ul>

<h2>3. تحصيل الأقساط وإغلاق السندات</h2>
<ul>
    <li>يتم توثيق كل دفعة بسند قبض رسمي يتضمن القسط المقرر والمبلغ المحصل والمتبقي آلياً.</li>
    <li>يُقفل سند السلفة تلقائياً بمجرد سداد كامل المبلغ وتصفير المتبقي.</li>
    <li>لا يحق للعضو التقدم بطلب سلفة جديدة قبل إغلاق السلفة القائمة وسدادها بالكامل.</li>
</ul>

<h2>4. صلاحيات وحوكمة أمناء الصندوق</h2>
<ul>
    <li>يتولى أمين الصندوق متابعة كشوفات التحصيل والتأكد من مطابقة السجلات.</li>
    <li>يتم إشعار الأعضاء بمواعيد الأقساط الدورية عبر نظام التنبيهات.</li>
    <li>يُعرض التقرير المالي للصندوق دورياً على كافة المشتركين بشفافية تامة.</li>
</ul>
HTML;
    }
}
