<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

class DocsController extends Controller
{
    public function index()
    {
        $apiKey = \App\Models\Setting::get('adm_api_secret_key', env('PROPERTYKOI_API_KEY', 'propertykoi_guides_sec_2026'));

        return view('admin.documentation', compact('apiKey'));
    }
}
