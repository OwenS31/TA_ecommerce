<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserManagementController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));

        $users = User::query()
            ->where('role', User::ROLE_USER)
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('name', 'like', '%' . $search . '%')
                        ->orWhere('email', 'like', '%' . $search . '%');
                });
            })
            ->withCount('orders')
            ->latest('created_at')
            ->paginate(10)
            ->withQueryString();

        return view('admin.users.index', [
            'users' => $users,
            'search' => $search,
        ]);
    }

    public function show(User $user): View
    {
        abort_unless($user->role === User::ROLE_USER, 404);

        $orders = $user->orders()
            ->with('items')
            ->latest('order_date')
            ->paginate(10);

        return view('admin.users.show', [
            'user' => $user,
            'orders' => $orders,
        ]);
    }

    public function toggleActive(User $user): RedirectResponse
    {
        abort_unless($user->role === User::ROLE_USER, 404);

        $user->is_active = !$user->is_active;
        $user->save();

        $statusText = $user->is_active ? 'diaktifkan' : 'dinonaktifkan';

        return back()->with('status', "Akun pengguna berhasil {$statusText}.");
    }
}
