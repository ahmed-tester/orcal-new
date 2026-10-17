<?php

namespace App\Filament\Resources\Faqs\Pages;

use App\Filament\Resources\Faqs\FaqResource;
use App\Models\Faq;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;

class ListFaqs extends Page
{
    protected static string $resource = FaqResource::class;

    /** Livewire reactive search term */
    public string $search = '';

    public function getView(): string
    {
        return 'filament.resources.faqs.list-faqs';
    }

    public function getTitle(): string
    {
        return __('faq.plural');
    }

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('create')
                ->label(__('faq.actions.create'))
                ->icon('heroicon-o-plus-circle')
                ->url(fn (): string => FaqResource::getUrl('create'))
                ->visible(fn () => auth()->user()?->can('create_faq')),
        ];
    }

    public function getFaqs()
    {
        return Faq::query()
            ->when(trim($this->search) !== '', function ($q) {
                $term = '%' . trim($this->search) . '%';
                $q->where('question', 'like', $term)
                  ->orWhere('answer', 'like', $term);
            })
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }

    public function deleteFaq(int $id): void
    {
        $faq = Faq::findOrFail($id);

        if (auth()->user() && ! auth()->user()->can('delete_faq')) {
            Notification::make()
                ->title(__('faq.notifications.unauthorized') ?? 'غير مصرح لك بهذا الإجراء')
                ->danger()
                ->send();
            return;
        }

        $faq->delete();

        Notification::make()
            ->title(__('faq.notifications.deleted'))
            ->success()
            ->send();
    }
}
