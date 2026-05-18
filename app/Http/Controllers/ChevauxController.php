<?php

namespace App\Http\Controllers;

use App\Models\Cheval;
use App\Models\Personnel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class ChevauxController extends Controller
{
    public function index()
    {
        $chevaux = Cheval::with('cavalier')->orderBy('numero_incorporation')->get()
            ->map(function ($cheval) {
                return [
                    'id'                      => $cheval->id,
                    'numero_incorporation'    => $cheval->numero_incorporation,
                    'statut'                  => $cheval->statut,
                    'disponibilite'           => $cheval->disponibilite,
                    'indisponibilite_motif'   => $cheval->indisponibilite_motif,
                    'indisponibilite_debut'   => $cheval->indisponibilite_debut?->toDateString(),
                    'indisponibilite_duree'   => $cheval->indisponibilite_duree,
                    'indisponibilite_unite'   => $cheval->indisponibilite_unite,
                    'cavalier'                => $cheval->cavalier ? [
                        'id'                   => $cheval->cavalier->id,
                        'name'                 => $cheval->cavalier->name,
                        'numero_incorporation' => $cheval->cavalier->numero_incorporation,
                        'role'                 => $cheval->cavalier->role,
                    ] : null,
                ];
            });

        $cavaliers = Personnel::orderBy('name')->get()->map(fn($p) => [
            'id'                   => $p->id,
            'name'                 => $p->name,
            'numero_incorporation' => $p->numero_incorporation,
            'grade'                => $p->grade,
        ]);

        return Inertia::render('Chevaux/Index', [
            'chevaux'   => $chevaux,
            'cavaliers' => $cavaliers,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'numero_incorporation' => 'required|string|max:50|unique:chevaux,numero_incorporation',
            'cavalier_id'          => 'nullable|exists:personnel,id',
            'statut'               => 'required|in:actif,malade,autre',
        ]);

        Cheval::create($data);

        return back();
    }

    public function update(Request $request, Cheval $cheval)
    {
        $data = $request->validate([
            'numero_incorporation'  => 'sometimes|string|max:50|unique:chevaux,numero_incorporation,' . $cheval->id,
            'cavalier_id'           => 'nullable|exists:personnel,id',
            'statut'                => 'sometimes|in:actif,malade,autre',
            'disponibilite'         => 'sometimes|in:disponible,indisponible',
            'indisponibilite_motif' => 'nullable|string|max:255',
            'indisponibilite_duree' => 'nullable|integer|min:1|max:365',
            'indisponibilite_unite' => 'nullable|in:days,months',
        ]);

        if (isset($data['disponibilite'])) {
            if ($data['disponibilite'] === 'indisponible') {
                $data['indisponibilite_debut'] = now()->toDateString();
            } else {
                $data['indisponibilite_motif'] = null;
                $data['indisponibilite_debut'] = null;
                $data['indisponibilite_duree'] = null;
                $data['indisponibilite_unite'] = null;
            }
        }

        $cheval->update($data);

        return back();
    }

    public function destroy(Cheval $cheval)
    {
        $cheval->delete();

        return back();
    }
}
