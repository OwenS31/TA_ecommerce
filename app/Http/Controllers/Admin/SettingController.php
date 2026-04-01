<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StoreSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function index(Request $request): View
    {
        $storeSetting = StoreSetting::query()->firstOrCreate([], [
            'store_name' => 'CV. Tri Jaya',
        ]);

        return view('admin.settings.index', [
            'admin' => $request->user(),
            'storeSetting' => $storeSetting,
        ]);
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'phone' => ['nullable', 'string', 'max:20'],
        ]);

        $user->update($validated);

        return back()->with('status', 'Profil admin berhasil diperbarui.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = $request->user();

        if (!Hash::check($validated['current_password'], $user->password)) {
            return back()->withErrors([
                'current_password' => 'Password saat ini tidak sesuai.',
            ]);
        }

        $user->update([
            'password' => $validated['password'],
        ]);

        return back()->with('status', 'Password berhasil diperbarui.');
    }

    public function updateStore(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'store_name' => ['required', 'string', 'max:255'],
            'store_address' => ['nullable', 'string', 'max:2000'],
            'store_whatsapp' => ['nullable', 'string', 'max:20'],
        ]);

        $storeSetting = StoreSetting::query()->firstOrCreate([], [
            'store_name' => 'CV. Tri Jaya',
        ]);
        $storeSetting->update($validated);

        return back()->with('status', 'Informasi toko berhasil diperbarui.');
    }
}
