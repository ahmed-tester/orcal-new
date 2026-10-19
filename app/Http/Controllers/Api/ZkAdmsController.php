<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ZkAdmsController extends Controller
{
    public function handshake(Request $request)
    {
        return response("OK\n", 200)->header('Content-Type', 'text/plain');
    }

    public function receiveData(Request $request)
    {
        return response("OK\n", 200)->header('Content-Type', 'text/plain');
    }

    public function getRequest(Request $request)
    {
        return response("OK\n", 200)->header('Content-Type', 'text/plain');
    }

    public function deviceCommand(Request $request)
    {
        return response("OK\n", 200)->header('Content-Type', 'text/plain');
    }

    public function registry(Request $request)
    {
        return response("OK\n", 200)->header('Content-Type', 'text/plain');
    }
}
