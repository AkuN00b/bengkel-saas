<?php

namespace App\Http\Controllers;

use App\Http\Requests\SelectWorkshopRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WorkshopSelectionController extends Controller
{
    public function index(Request $request): View
    {
        return view('workshops.select', [
            'workshops' => $request->user()->workshops()->orderBy('workshops.name')->orderBy('workshops.id')->paginate(20),
        ]);
    }

    public function store(SelectWorkshopRequest $request): RedirectResponse
    {
        // A global exists rule or route model binding would not prove membership.
        $workshop = $request->user()->workshops()->whereKey($request->validated('workshop_id'))->firstOrFail();
        $request->session()->put('active_workshop_id', $workshop->id);
        $request->session()->regenerate();

        return redirect()->route('dashboard');
    }
}
