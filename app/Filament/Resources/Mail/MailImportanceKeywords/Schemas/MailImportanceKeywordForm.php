<?php

namespace App\Filament\Resources\Mail\MailImportanceKeywords\Schemas;

use App\Services\CompanyFormService;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rules\Unique;

class MailImportanceKeywordForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('mail_inbox.models.importance_keyword.singular'))
                ->description(__('mail_inbox.rules.importance_keyword_description'))
                ->icon('heroicon-o-bookmark')
                ->columns(2)
                ->columnSpanFull()
                ->schema([
                    CompanyFormService::makeCompanySelect('company_id', __('mail_inbox.fields.company'), required: false)
                        ->helperText(__('mail_inbox.rules.company_helper')),
                    TextInput::make('keyword')
                        ->label(__('mail_inbox.fields.keyword'))
                        ->required()
                        ->unique(ignoreRecord: true, modifyRuleUsing: fn (Unique $rule, Get $get) => $rule->where('company_id', $get('company_id')))
                        ->maxLength(255)
                        ->placeholder(__('mail_inbox.rules.keyword_placeholder'))
                        ->helperText(__('mail_inbox.rules.importance_keyword_helper')),
                    TextInput::make('weight')
                        ->label(__('mail_inbox.fields.weight'))
                        ->numeric()
                        ->default(10)
                        ->minValue(1)
                        ->required()
                        ->helperText(__('mail_inbox.rules.weight_helper')),
                    Toggle::make('is_active')
                        ->label(__('mail_inbox.fields.is_active'))
                        ->default(true)
                        ->helperText(__('mail_inbox.rules.active_helper')),
                ]),
        ]);
    }
}
