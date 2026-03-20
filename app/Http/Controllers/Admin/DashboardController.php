<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\User;

class DashboardController extends Controller
{
    public function index()
    {
        $totalProducts = Product::where('is_active', true)->count();
        $totalCustomers = User::where('role', User::ROLE_USER)->count();

        return view('admin.dashboard', compact(
            'totalProducts',
            'totalCustomers',
        ));
    }
}
