<?php

namespace App\Http\Controllers;

use App\Models\Personnel;
use App\Models\User;
use App\Notifications\AgentUnavailableNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Inertia\Inertia;

class PersonnelController extends Controller
{
    public function index()
    {
        // Transition inline : on_leave → leave_expired si date dépassée
        Personnel::where('availability', 'on_leave')
            ->whereNotNull('leave_start_date')
            ->get()
            ->filter(fn($p) => $this->leaveIsExpired($p))
            ->each(function ($p) {
                $p->update(['availability' => 'leave_expired']);
                activity('personnel')
                    ->performedOn($p)
                    ->withProperties(['old' => 'on_leave', 'new' => 'leave_expired'])
                    ->log("Congé de « {$p->name} » arrivé à échéance — retour à confirmer");
            });

        $personnel = Personnel::withCount('missions')
            ->with([
                'missions' => fn($q) => $q->where('status', 'in_progress')
                    ->select('missions.id', 'missions.title', 'missions.location', 'missions.priority'),
                'peloton',
                'groupe',
                'equipe',
                'user',
            ])
            ->get()
            ->map(fn($p) => [
                'id'                          => $p->id,
                'name'                        => $p->name,
                'numero_incorporation'        => $p->numero_incorporation,
                'grade'                       => $p->grade,
                'fonction'                    => $p->fonction,
                'avatar'                      => $p->avatar ?? $p->user?->avatar,
                'availability'                => $p->availability,
                'leave_start_date'            => $p->leave_start_date?->toDateString(),
                'leave_duration'              => $p->leave_duration,
                'leave_unit'                  => $p->leave_unit,
                'unavailability_reason'       => $p->unavailability_reason,
                'unavailability_start_date'   => $p->unavailability_start_date?->toDateString(),
                'unavailability_duration'     => $p->unavailability_duration,
                'unavailability_unit'         => $p->unavailability_unit,
                'missions_count'              => $p->missions_count,
                'active_mission'              => $p->missions->first(),
                'computed_status'             => $p->missions->isNotEmpty() ? 'deployed' : $p->availability,
                'peloton_id'                  => $p->peloton_id,
                'groupe_id'                   => $p->groupe_id,
                'equipe_id'                   => $p->equipe_id,
                'peloton'                     => $p->peloton,
                'groupe'                      => $p->groupe,
                'equipe'                      => $p->equipe,
                'has_account'                 => $p->user_id !== null,
            ]);

        $pelotons = \App\Models\Peloton::with(['groupes.equipes'])->get();

        return Inertia::render('Personnel/Index', [
            'personnel' => $personnel,
            'pelotons'  => $pelotons,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'                 => 'required|string|max:255',
            'numero_incorporation' => 'nullable|string|max:50|unique:personnel,numero_incorporation',
            'grade'                => 'nullable|string|max:100',
            'fonction'             => 'nullable|string|max:100',
            'phone_number'         => 'nullable|string|max:20',
            'peloton_id'           => 'nullable|exists:pelotons,id',
            'groupe_id'            => 'nullable|exists:groupes,id',
            'equipe_id'            => 'nullable|exists:equipes,id',
        ]);

        Personnel::create($data);

        return back()->with('success', "{$data['name']} a été ajouté au personnel.");
    }

    public function update(Request $request, Personnel $personnel)
    {
        $data = $request->validate([
            'name'                 => 'sometimes|string|max:255',
            'numero_incorporation' => "nullable|string|max:50|unique:personnel,numero_incorporation,{$personnel->id}",
            'grade'                => 'nullable|string|max:100',
            'fonction'             => 'nullable|string|max:100',
            'phone_number'         => 'nullable|string|max:20',
            'peloton_id'           => 'nullable|exists:pelotons,id',
            'groupe_id'            => 'nullable|exists:groupes,id',
            'equipe_id'            => 'nullable|exists:equipes,id',
        ]);

        $personnel->update($data);

        return back()->with('success', "{$personnel->name} mis à jour.");
    }

    public function destroy(Personnel $personnel)
    {
        $name = $personnel->name;
        $personnel->delete();

        return back()->with('success', "{$name} supprimé du personnel.");
    }

    public function updateAvailability(Request $request, Personnel $personnel)
    {
        $request->validate([
            'availability'            => 'required|in:available,on_leave,unavailable',
            'leave_duration'          => 'required_if:availability,on_leave|nullable|integer|min:1|max:365',
            'leave_unit'              => 'required_if:availability,on_leave|nullable|in:days,months',
            'unavailability_reason'   => 'nullable|string|max:255',
            'unavailability_duration' => 'required_if:availability,unavailable|nullable|integer|min:1|max:365',
            'unavailability_unit'     => 'required_if:availability,unavailable|nullable|in:days,months',
        ]);

        $oldAvailability = $personnel->availability;
        $updateData = ['availability' => $request->availability];

        if ($request->availability === 'on_leave') {
            $updateData['leave_start_date']      = now()->toDateString();
            $updateData['leave_duration']        = $request->leave_duration;
            $updateData['leave_unit']            = $request->leave_unit;
            $updateData['unavailability_reason'] = null;
        } elseif ($request->availability === 'unavailable') {
            $updateData['unavailability_reason']     = $request->unavailability_reason;
            $updateData['unavailability_start_date'] = now()->toDateString();
            $updateData['unavailability_duration']   = $request->unavailability_duration;
            $updateData['unavailability_unit']       = $request->unavailability_unit;
            $updateData['leave_start_date']          = null;
            $updateData['leave_duration']            = null;
            $updateData['leave_unit']                = null;
        } else {
            $updateData['leave_start_date']          = null;
            $updateData['leave_duration']            = null;
            $updateData['leave_unit']                = null;
            $updateData['unavailability_reason']     = null;
            $updateData['unavailability_start_date'] = null;
            $updateData['unavailability_duration']   = null;
            $updateData['unavailability_unit']       = null;
        }

        $personnel->update($updateData);

        if (in_array($request->availability, ['on_leave', 'unavailable'])) {
            $adminsManagers = User::role(['admin', 'manager'])->where('id', '!=', auth()->id())->get();
            Notification::send($adminsManagers, new AgentUnavailableNotification($personnel, $request->availability));
        }

        $labels = ['available' => 'Disponible', 'on_leave' => 'En congé', 'unavailable' => 'Indisponible'];
        activity('personnel')
            ->causedBy(auth()->user())
            ->performedOn($personnel)
            ->withProperties(['old' => $oldAvailability, 'new' => $request->availability])
            ->log("Disponibilité de « {$personnel->name} » : {$labels[$oldAvailability]} → {$labels[$request->availability]}");

        return back()->with('success', 'Disponibilité de ' . $personnel->name . ' mise à jour.');
    }

    public function confirmReturn(Personnel $personnel)
    {
        $personnel->update([
            'availability'               => 'available',
            'leave_start_date'           => null,
            'leave_duration'             => null,
            'leave_unit'                 => null,
            'unavailability_reason'      => null,
            'unavailability_start_date'  => null,
            'unavailability_duration'    => null,
            'unavailability_unit'        => null,
        ]);

        activity('personnel')
            ->causedBy(auth()->user())
            ->performedOn($personnel)
            ->withProperties(['old' => 'leave_expired', 'new' => 'available'])
            ->log("Retour de service de « {$personnel->name} » confirmé");

        return back()->with('success', $personnel->name . ' est de retour en service.');
    }

    private function leaveIsExpired(Personnel $p): bool
    {
        if (!$p->leave_start_date || !$p->leave_duration || !$p->leave_unit) return false;
        $end = $p->leave_start_date->copy();
        if ($p->leave_unit === 'months') {
            $end->addMonths($p->leave_duration);
        } else {
            $end->addDays($p->leave_duration);
        }
        return now()->greaterThan($end);
    }
}
