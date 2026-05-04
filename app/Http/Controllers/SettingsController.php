<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function index(): View
    {
        $settings = [
            'local_med_discount'    => Setting::getValue('local_med_discount', 15),
            'imported_med_discount' => Setting::getValue('imported_med_discount', 7),
            'hospital_name'         => Setting::getValue('hospital_name', config('app.name')),
            'hospital_logo'         => Setting::getValue('hospital_logo'),
            'icu_beds'              => Setting::getValue('icu_beds', 6),
            'hospital_address'      => Setting::getValue('hospital_address'),
            'hospital_phones'       => Setting::getValue('hospital_phones'),
            'hospital_po_box'       => Setting::getValue('hospital_po_box'),
            'hospital_commercial_reg' => Setting::getValue('hospital_commercial_reg'),
            'invoice_footer_note'   => Setting::getValue('invoice_footer_note'),
            'invoice_prepared_by'   => Setting::getValue('invoice_prepared_by', 'أعدّه'),
            'invoice_approved_by'   => Setting::getValue('invoice_approved_by', 'مدير المستشفى'),
        ];

        return view('settings.index', compact('settings'));
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'local_med_discount'      => ['required', 'numeric', 'min:0', 'max:100'],
            'imported_med_discount'   => ['required', 'numeric', 'min:0', 'max:100'],
            'hospital_name'           => ['required', 'string', 'max:255'],
            'hospital_address'        => ['nullable', 'string', 'max:500'],
            'hospital_phones'         => ['nullable', 'string', 'max:255'],
            'hospital_po_box'         => ['nullable', 'string', 'max:100'],
            'hospital_commercial_reg' => ['nullable', 'string', 'max:100'],
            'invoice_footer_note'     => ['nullable', 'string', 'max:500'],
            'invoice_prepared_by'     => ['nullable', 'string', 'max:100'],
            'invoice_approved_by'     => ['nullable', 'string', 'max:100'],
            'icu_beds'                => ['nullable', 'integer', 'min:1', 'max:1000'],
            'hospital_logo'           => ['nullable', 'image', 'max:2048'],
        ]);

        foreach ([
            'local_med_discount', 'imported_med_discount',
            'hospital_name', 'hospital_address', 'hospital_phones',
            'hospital_po_box', 'hospital_commercial_reg',
            'invoice_footer_note', 'invoice_prepared_by', 'invoice_approved_by', 'icu_beds',
        ] as $key) {
            Setting::setValue($key, $data[$key] ?? '');
        }

        if ($request->hasFile('hospital_logo')) {
            $old = Setting::getValue('hospital_logo');
            if ($old && Storage::disk('public')->exists($old)) {
                Storage::disk('public')->delete($old);
            }
            $path = $request->file('hospital_logo')->store('logos', 'public');
            Setting::setValue('hospital_logo', $path);
        }

        alert()->success(__('Updated'), __('Settings saved successfully.'));
        return redirect()->route('settings.index');
    }
}
