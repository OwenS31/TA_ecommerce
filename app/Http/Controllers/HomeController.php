<?php

namespace App\Http\Controllers;

class HomeController extends Controller
{
    /**
     * Show the user home / storefront page.
     */
    public function index()
    {
        return view('home');
    }
}
