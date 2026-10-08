<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

class ShopSettingsController extends Controller
{
    /** Web-order settings: key => default. Read by WebOrderService and the shop API (store-policy). */
    public const WEB_ORDER_DEFAULTS = [
        'web_order_alert_emails'           => '',    // one per line
        'web_order_alert_sms'              => '',    // one per line
        'web_order_hold_days'              => 14,    // reminder sent this many days after "Ready for pickup"
        'web_order_storage_fee_after_days' => 30,
        'web_order_storage_fee_text'       => 'a storage fee',
        'web_order_forfeit_after_days'     => 90,
    ];

    public function index()
    {
        $notifyEmail = Setting::get('shop_quote_notify_email', '');
        $webOrder    = collect(self::WEB_ORDER_DEFAULTS)->map(fn ($default, $key) => Setting::get($key, $default))->all();

        return view('admin.settings.shop', compact('notifyEmail', 'webOrder'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'shop_quote_notify_email'          => ['nullable', 'email', 'max:255'],
            'web_order_alert_emails'           => ['nullable', 'string', 'max:2000'],
            'web_order_alert_sms'              => ['nullable', 'string', 'max:1000'],
            'web_order_hold_days'              => ['required', 'integer', 'min:1', 'max:365'],
            'web_order_storage_fee_after_days' => ['required', 'integer', 'min:1', 'max:3650'],
            'web_order_storage_fee_text'       => ['required', 'string', 'max:255'],
            'web_order_forfeit_after_days'     => ['required', 'integer', 'min:1', 'max:3650', 'gt:web_order_storage_fee_after_days'],
        ], [
            'web_order_forfeit_after_days.gt' => 'The forfeit day must be after storage fees start.',
        ]);

        // Validate each email / phone line individually so a typo is caught here, not when an order arrives.
        $emails = self::lines($request->input('web_order_alert_emails'));
        foreach ($emails as $email) {
            if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return back()->withInput()->withErrors(['web_order_alert_emails' => "“{$email}” isn’t a valid email address."]);
            }
        }
        $phones = self::lines($request->input('web_order_alert_sms'));
        foreach ($phones as $phone) {
            if (strlen(preg_replace('/\D/', '', $phone)) < 10) {
                return back()->withInput()->withErrors(['web_order_alert_sms' => "“{$phone}” isn’t a valid phone number."]);
            }
        }

        Setting::set('shop_quote_notify_email', $request->input('shop_quote_notify_email', ''));
        Setting::set('web_order_alert_emails', implode("\n", $emails));
        Setting::set('web_order_alert_sms', implode("\n", $phones));
        foreach (['web_order_hold_days', 'web_order_storage_fee_after_days', 'web_order_storage_fee_text', 'web_order_forfeit_after_days'] as $key) {
            Setting::set($key, $request->input($key));
        }

        return back()->with('success', 'Shop settings saved.');
    }

    /** "a@b.com, c@d.com\n…" → ['a@b.com', 'c@d.com'] */
    public static function lines(?string $value): array
    {
        return array_values(array_filter(array_map('trim', preg_split('/[\r\n,;]+/', (string) $value))));
    }
}
