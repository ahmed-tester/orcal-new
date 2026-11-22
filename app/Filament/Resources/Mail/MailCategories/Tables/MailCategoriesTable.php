<?php

namespace App\Filament\Resources\Mail\MailCategories\Tables;

use App\Models\Mail\MailCategory;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Gate;

class MailCategoriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->columns([
                TextColumn::make('sort_order')
                    ->label(__('mail_inbox.fields.sort_order'))
                    ->sortable(),
                TextColumn::make('slug')
                    ->label(__('mail_inbox.fields.category_slug'))
                    ->badge()
                    ->color(fn (MailCategory $record): string => $record->color ?: 'gray')
                    ->searchable(),
                TextColumn::make('name_ar')
                    ->label(__('mail_inbox.fields.name_ar'))
                    ->searchable(),
                TextColumn::make('name_en')
                    ->label(__('mail_inbox.fields.name_en'))
                    ->searchable(),
                IconColumn::make('is_excluded')
                    ->label(__('mail_inbox.fields.is_excluded_category'))
                    ->boolean(),
                IconColumn::make('is_active')
                    ->label(__('mail_inbox.fields.is_active'))
                    ->boolean(),
                IconColumn::make('is_system')
                    ->label(__('mail_inbox.fields.is_system'))
                    ->boolean(),
                TextColumn::make('messages_count')
                    ->counts('messages')
                    ->label(__('mail_inbox.fields.messages_count')),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label(__('mail_inbox.fields.is_active')),
                TernaryFilter::make('is_excluded')
                    ->label(__('mail_inbox.fields.is_excluded_category')),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->visible(fn (MailCategory $record): bool => Gate::allows('delete', $record)),
            ]);
    }
}
