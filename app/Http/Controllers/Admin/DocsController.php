<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

class DocsController extends Controller
{
    public function index()
    {
        return view('admin.documentation');
    }
}
