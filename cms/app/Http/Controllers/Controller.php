<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

abstract class Controller
{
    /**
     * Tentukan tampilan daftar ("card" atau "list") dan ingat pilihannya per halaman di session.
     */
    protected function resolveViewMode(Request $request, string $page, string $default = 'card'): string
    {
        $sessionKey = "view_mode.{$page}";

        $viewMode = in_array($request->query('view'), ['card', 'list'], true)
            ? $request->query('view')
            : $request->session()->get($sessionKey, $default);

        $request->session()->put($sessionKey, $viewMode);

        return $viewMode;
    }
}
