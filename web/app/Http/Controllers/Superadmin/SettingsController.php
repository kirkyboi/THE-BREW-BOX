<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class SettingsController extends Controller
{
    private const FIELDS = [
        'site_name' => ['label' => 'Business name', 'default' => 'The Brew Box', 'type' => 'string'],
        'site_tagline' => ['label' => 'Tagline', 'default' => 'Brewed with care, served with love.', 'type' => 'string'],
        'business_email' => ['label' => 'Business email', 'default' => '', 'type' => 'string'],
        'business_phone' => ['label' => 'Business phone', 'default' => '', 'type' => 'string'],
        'business_address' => ['label' => 'Business address', 'default' => '', 'type' => 'string'],
        'currency' => ['label' => 'Currency', 'default' => 'PHP', 'type' => 'string'],
        'timezone' => ['label' => 'Timezone', 'default' => 'Asia/Manila', 'type' => 'string'],
        'tax_rate' => ['label' => 'Tax rate (%)', 'default' => '0', 'type' => 'number'],
        'service_charge_rate' => ['label' => 'Service charge (%)', 'default' => '0', 'type' => 'number'],
        'order_notifications' => ['label' => 'Order notifications', 'default' => '1', 'type' => 'boolean'],
        'reservation_notifications' => ['label' => 'Reservation notifications', 'default' => '1', 'type' => 'boolean'],
        'maintenance_mode' => ['label' => 'Maintenance mode', 'default' => '0', 'type' => 'boolean'],
    ];

    private function authorizeSuperadmin(): void
    {
        abort_unless(auth()->check() && auth()->user()->status === 'active' && DB::table('roles')->where('id', auth()->user()->role_id)->where('slug', 'superadmin')->exists(), 403);
    }

    public function index()
    {
        $this->authorizeSuperadmin();
        $settings = collect(self::FIELDS)->mapWithKeys(fn ($field, $key) => [$key => $field['default']])->all();
        $error = null;
        try {
            if (!Schema::hasTable('settings')) {
                $error = 'Settings table not found. Import the settings table or run your migration.';
            } else {
                foreach (DB::table('settings')->whereIn('key', array_keys(self::FIELDS))->pluck('value', 'key') as $key => $value) {
                    $settings[$key] = (string) ($value ?? '');
                }
            }
        } catch (\Throwable $e) {
            report($e);
            $error = 'Unable to load settings. Check storage/logs/laravel.log.';
        }
        return view('superadmin.settings.index', compact('settings', 'error'));
    }

    public function update(Request $request)
    {
        $this->authorizeSuperadmin();
        abort_unless(Schema::hasTable('settings'), 503, 'Settings table is missing.');
        $data = $request->validate([
            'site_name' => ['required', 'string', 'max:150'],
            'site_tagline' => ['nullable', 'string', 'max:250'],
            'business_email' => ['nullable', 'email', 'max:191'],
            'business_phone' => ['nullable', 'string', 'max:30'],
            'business_address' => ['nullable', 'string', 'max:500'],
            'currency' => ['required', Rule::in(['PHP', 'USD', 'EUR'])],
            'timezone' => ['required', 'timezone'],
            'tax_rate' => ['required', 'numeric', 'between:0,100'],
            'service_charge_rate' => ['required', 'numeric', 'between:0,100'],
            'order_notifications' => ['required', 'boolean'],
            'reservation_notifications' => ['required', 'boolean'],
            'maintenance_mode' => ['required', 'boolean'],
        ]);
        DB::transaction(function () use ($data, $request) {
            foreach (self::FIELDS as $key => $field) {
                $new = (string) ($data[$key] ?? '');
                $old = DB::table('settings')->where('key', $key)->first();
                if ($old && (string) $old->value === $new) continue;
                DB::table('settings')->updateOrInsert(['key' => $key], [
                    'value' => $new,
                    'type' => $field['type'],
                    'updated_by' => auth()->id(),
                    'updated_at' => now(),
                    'created_at' => $old?->created_at ?? now(),
                ]);
                if (Schema::hasTable('audit_logs')) {
                    DB::table('audit_logs')->insert([
                        'user_id' => auth()->id(), 'action' => 'updated', 'module' => 'settings',
                        'record_type' => 'setting', 'record_id' => null,
                        'old_values' => json_encode(['key' => $key, 'value' => $old?->value]),
                        'new_values' => json_encode(['key' => $key, 'value' => $new]),
                        'ip_address' => $request->ip(), 'user_agent' => substr((string) $request->userAgent(), 0, 1000),
                        'created_at' => now(),
                    ]);
                }
            }
        });
        return redirect()->route('superadmin.settings.index')->with('success', 'System settings saved successfully.');
    }
}
