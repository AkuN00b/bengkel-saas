<?php

namespace App\Http\Controllers;

use App\Actions\Mechanics;
use App\Http\Requests\MechanicRequest;
use App\Models\WorkshopMembership;
use Illuminate\Support\Facades\Gate;

class MechanicController extends Controller
{
    public function index(MechanicRequest $request)
    {
        $workshop = $request->attributes->get('activeWorkshop');
        Gate::authorize('viewAny', [WorkshopMembership::class, $workshop]);

        return view('mechanics.index', ['activeWorkshop' => $workshop,
            'mechanics' => WorkshopMembership::where('workshop_id', $workshop->id)->where('role', 'mechanic')->with('user')->orderBy('id')->paginate(20)]);
    }

    public function store(MechanicRequest $request, Mechanics $action)
    {
        $membership = $action->create($request->attributes->get('activeWorkshop'), $request->user(), $request->validated());

        return to_route('mechanics.index')->with('status', 'Akun mekanik dibuat. '.$action->sendSetup($membership->user));
    }

    public function activation(MechanicRequest $request, string $mechanic, Mechanics $action)
    {
        $this->change($request, $mechanic, $action, true);

        return to_route('mechanics.index')->with('status', 'Keaktifan akses mekanik diperbarui.');
    }

    public function resend(MechanicRequest $request, string $mechanic, Mechanics $action)
    {
        $membership = $this->change($request, $mechanic, $action, false);

        return to_route('mechanics.index')->with('status', $action->sendSetup($membership->user));
    }

    private function change(MechanicRequest $request, string $id, Mechanics $action, bool $activation): WorkshopMembership
    {
        abort_if(filter_var($id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) === false, 404);

        return $action->mutate($request->attributes->get('activeWorkshop'), $request->user(), (int) $id, $request->validated(), $activation);
    }
}
