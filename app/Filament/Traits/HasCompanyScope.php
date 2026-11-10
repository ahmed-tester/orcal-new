<?php

namespace App\Filament\Traits;

use App\Services\CompanyAccessScopeService;
use Illuminate\Database\Eloquent\Builder;

trait HasCompanyScope
{
    public static function getEloquentQuery(): Builder
    {
        return CompanyAccessScopeService::apply(parent::getEloquentQuery());
    }
}
