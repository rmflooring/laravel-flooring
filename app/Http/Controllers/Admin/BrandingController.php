<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class BrandingController extends Controller
{
    public function show()
    {
        return view('admin.settings.branding', [
            'company_name'    => Setting::get('branding_company_name', 'RM Flooring'),
            'tagline'         => Setting::get('branding_tagline', 'rmflooring.ca'),
            'address'         => Setting::get('branding_address', ''),
            'city'            => Setting::get('branding_city', ''),
            'province'        => Setting::get('branding_province', ''),
            'postal'          => Setting::get('branding_postal', ''),
            'phone'           => Setting::get('branding_phone', ''),
            'email'           => Setting::get('branding_email', ''),
            'website'         => Setting::get('branding_website', ''),
            'logo_path'       => Setting::get('branding_logo_path', ''),
        ]);
    }

    public function update(Request $request)
    {
        $request->validate([
            'company_name' => ['required', 'string', 'max:120'],
            'tagline'      => ['nullable', 'string', 'max:120'],
            'address'      => ['nullable', 'string', 'max:255'],
            'city'         => ['nullable', 'string', 'max:100'],
            'province'     => ['nullable', 'string', 'max:100'],
            'postal'       => ['nullable', 'string', 'max:20'],
            'phone'        => ['nullable', 'string', 'max:50'],
            'email'        => ['nullable', 'email', 'max:120'],
            'website'      => ['nullable', 'string', 'max:120'],
        ]);

        Setting::set('branding_company_name', $request->input('company_name'));
        Setting::set('branding_tagline',      $request->input('tagline', ''));
        Setting::set('branding_address',      $request->input('address', ''));
        Setting::set('branding_city',         $request->input('city', ''));
        Setting::set('branding_province',     $request->input('province', ''));
        Setting::set('branding_postal',       $request->input('postal', ''));
        Setting::set('branding_phone',        $request->input('phone', ''));
        Setting::set('branding_email',        $request->input('email', ''));
        Setting::set('branding_website',      $request->input('website', ''));

        return back()->with('success', 'Branding settings saved.');
    }

    public function uploadLogo(Request $request)
    {
        $request->validate([
            'logo' => ['required', 'image', 'mimes:png,jpg,jpeg,svg,webp', 'max:2048'],
        ]);

        // Remove old logo
        $existing = Setting::get('branding_logo_path', '');
        if ($existing && Storage::disk('public')->exists($existing)) {
            Storage::disk('public')->delete($existing);
        }

        $path = $request->file('logo')->store('branding', 'public');
        Setting::set('branding_logo_path', $path);

        return back()->with('success', 'Logo uploaded.');
    }

    public function removeLogo()
    {
        $existing = Setting::get('branding_logo_path', '');
        if ($existing && Storage::disk('public')->exists($existing)) {
            Storage::disk('public')->delete($existing);
        }

        Setting::set('branding_logo_path', '');

        return back()->with('success', 'Logo removed.');
    }
}
