<?php

namespace App\Http\Controllers\Catalog;

use App\Http\Controllers\Controller;
use App\Models\Room;
use App\Models\Ward;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WardController extends Controller
{
    public function index(): View
    {
        $wards = Ward::withCount('rooms')->orderBy('name')->get();

        return view('catalog.wards.index', compact('wards'));
    }

    // ── Wards ────────────────────────────────────────────────────────────────

    public function store(Request $request): RedirectResponse
    {
        $request->validate(['name' => ['required', 'string', 'max:100', 'unique:wards,name']]);

        Ward::create(['name' => $request->name]);

        alert()->success(__('Created'), __('Ward added successfully.'));

        return back();
    }

    public function update(Request $request, Ward $ward): RedirectResponse
    {
        $request->validate(['name' => ['required', 'string', 'max:100', "unique:wards,name,{$ward->id}"]]);

        $ward->update(['name' => $request->name]);

        alert()->success(__('Updated'), __('Ward updated successfully.'));

        return back();
    }

    public function destroy(Ward $ward): RedirectResponse
    {
        $ward->delete();

        alert()->success(__('Deleted'), __('Ward and its rooms removed.'));

        return back();
    }

    // ── Rooms ────────────────────────────────────────────────────────────────

    public function storeRoom(Request $request, Ward $ward): RedirectResponse
    {
        $request->validate(['name' => ['required', 'string', 'max:50']]);

        $ward->rooms()->create(['name' => $request->name]);

        return back();
    }

    public function destroyRoom(Ward $ward, Room $room): RedirectResponse
    {
        $room->delete();

        return back();
    }
}
