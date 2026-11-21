<?php

namespace App\Filament\Resources\Mail\MailTrustedEmails\Schemas;

use App\Services\CompanyFormService;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rules\Unique;

class MailTrustedEmailForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('mail_inbox.models.trusted_email.singular'))
                ->description(__('mail_inbox.rules.trusted_email_description'))
                ->icon('heroicon-o-shield-check')
                ->columns(2)
                ->columnSpanFull()
                ->schema([
                    CompanyFormService::makeCompanySelect('company_id', __('mail_inbox.fields.company'), required: false)
                        ->helperText(__('mail_inbox.rules.company_helper')),
                    TextInput::make('email')
                        ->label(__('mail_inbox.fields.email'))
                        ->email()
                        ->required()
                        ->unique(ignoreRecord: true, modifyRuleUsing: fn (Unique $rule, Get $get) => $rule->where('company_id', $get('company_id')))
                        ->maxLength(255)
                        ->placeholder(__('mail_inbox.rules.email_placeholder'))
                        ->helperText(__('mail_inbox.rules.email_helper')),
                    TextInput::make('organization_name')
                        ->label(__('mail_inbox.fields.organization_name'))
                        ->maxLength(255)
                        ->placeholder(__('mail_inbox.rules.organization_placeholder')),
                    Toggle::make('is_active')
                        ->label(__('mail_inbox.fields.is_active'))
                        ->default(true)
                        ->helperText(__('mail_inbox.rules.active_helper')),
                    Textarea::make('notes')
                        ->label(__('mail_inbox.fields.notes'))
                        ->rows(3)
                        ->columnSpanFull(),
                ]),
        ]);
    }
}
