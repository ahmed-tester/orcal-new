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

class RoscaFundPolicyPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-scale';

    protected static ?int $navigationSort = 9;

    protected string $view = 'filament.pages.rosca-fund-policy';

    public ?array $data = [];

    public static function canAccess(): bool
    {
        $user = auth()->user();
        return $user && ($user->isSuperAdminOrOwner() || $user->can('view_any_rosca_fund') || $user->can('view_rosca_fund_policy'));
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
        return __('navigation.rosca_fund_policy');
    }

    public function getTitle(): string|Htmlable
    {
        return Setting::get('rosca_policy_title', __('funds.policy_pages.rosca_title'));
    }

    public function mount(): void
    {
        $defaultContent = $this->getDefaultPolicyContent();

        $this->form->fill([
            'title' => Setting::get('rosca_policy_title', __('funds.policy_pages.rosca_title')),
            'subtitle' => Setting::get('rosca_policy_subtitle', __('funds.policy_pages.rosca_subtitle')),
            'content' => Setting::get('rosca_policy_content', $defaultContent),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        $user = auth()->user();
        $canEdit = $user && ($user->isSuperAdminOrOwner() || $user->can('manage_settings') || $user->can('update_setting'));

        return $schema
            ->components([
                Section::make(__('funds.policy_pages.rosca_section_title'))
                    ->description(__('funds.policy_pages.rosca_section_desc'))
                    ->icon('heroicon-o-scale')
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
                ->modalHeading(__('funds.policy_pages.actions.reset_rosca_heading'))
                ->modalDescription(__('funds.policy_pages.actions.reset_modal_desc'))
                ->action(function () {
                    $this->form->fill([
                        'title' => __('funds.policy_pages.rosca_title'),
                        'subtitle' => __('funds.policy_pages.rosca_subtitle'),
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

        Setting::set('rosca_policy_title', $formData['title'], 'funds', 'rosca_policy_title');
        Setting::set('rosca_policy_subtitle', $formData['subtitle'], 'funds', 'rosca_policy_subtitle');
        Setting::set('rosca_policy_content', $formData['content'], 'funds', 'rosca_policy_content', 'html');

        Notification::make()
            ->title(__('funds.policy_pages.notifications.rosca_saved'))
            ->success()
            ->send();
    }

    protected function getDefaultPolicyContent(): string
    {
        return <<<'HTML'
<h2>1. طبيعة الجمعية وشروط الاشتراك</h2>
<ul>
    <li>الجمعية هي نموذج ادخار دوري تعاوني بين مجموعة محددة من الأعضاء بالتوافق والتراضي.</li>
    <li>يحدد عدد المشتركين عدد أشهر دورة الجمعية وعدد الأقساط الشهرية المطلوبة.</li>
    <li>المبلغ النهائي المستلم يُحسب آلياً بحسب قيمة القسط ومجموع الأعضاء المشتركين.</li>
</ul>

<h2>2. آليات توزيع الأدوار والاستلام</h2>
<ul>
    <li>يتم تحديد ترتيب استلام المبلغ الكلي لكل عضو إما بالقرعة العلنية أو بالتوافق الأخوي أو بالترتيب المسبق.</li>
    <li>يستلم العضو كامل حصيلة الجمعية في شهره المحدد فور اكتمال سداد جميع الأعضاء لقسط ذلك الشهر.</li>
    <li>يُوثق استلام العضو بمحضر وسند استلام رسمي على المنظومة.</li>
</ul>

<h2>3. كشف السداد الشهري والالتزام</h2>
<ul>
    <li>يتم رصد دفعات الأعضاء شهرياً في كشف السداد الإلكتروني بحالات (تم السداد / جزئي / لم يتم).</li>
    <li>الالتزام بالسداد في بداية كل شهر واجب لضمان تسليم العضو المستحق في موعده دون تأخير.</li>
    <li>يُقفل سند العضو وسند الجمعية النهائي فور انتهاء آخر شهر في الدورة واستلام الجميع.</li>
</ul>

<h2>4. إسناد المهمة والصلاحيات والإشراف</h2>
<ul>
    <li>يمكن إسناد مهمة إدارة ومتابعة الجمعية لأحد الأعضاء المشتركين مع منحه صلاحيات الرصد والتأكيد.</li>
    <li>يتولى المشرف إرسال التنبيهات الدورية ومتابعة المتأخرين وتنسيق مواعيد تسليم الحصيلة.</li>
</ul>
HTML;
    }
}
