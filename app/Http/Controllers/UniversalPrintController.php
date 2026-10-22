<?php

namespace App\Http\Controllers;

use App\Services\UniversalPrintService;
use Illuminate\Http\Request;

class UniversalPrintController extends Controller
{
    /**
     * Unified print action for dynamic printable modules.
     */
    public function print(Request $request, string $type, $id)
    {
        return UniversalPrintService::render($type, $id, $request->all());
    }
}
