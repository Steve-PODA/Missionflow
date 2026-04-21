<?php

namespace App\Http\Controllers;

use App\Models\WhatsAppLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;

class WhatsAppController extends Controller
{
    public function index()
    {
        $logs = WhatsAppLog::with(['user', 'mission'])
            ->latest()
            ->limit(100)
            ->get()
            ->map(fn($log) => [
                'id'           => $log->id,
                'user_name'    => $log->user?->name ?? '—',
                'mission_title'=> $log->mission?->title ?? '—',
                'phone_number' => $this->maskPhone($log->phone_number),
                'template'     => $log->template,
                'status'       => $log->status,
                'trigger'      => $log->trigger,
                'error'        => $log->error,
                'sent_at'      => $log->created_at->format('d/m/Y H:i'),
            ]);

        $stats = [
            'total'  => WhatsAppLog::count(),
            'sent'   => WhatsAppLog::where('status', 'sent')->count(),
            'failed' => WhatsAppLog::where('status', 'failed')->count(),
        ];

        return Inertia::render('WhatsApp/Index', [
            'logs'  => $logs,
            'stats' => $stats,
        ]);
    }

    private function maskPhone(string $phone): string
    {
        $clean = preg_replace('/\D/', '', $phone);
        return substr($clean, 0, -4) ? substr($clean, 0, -4) . 'XXXX' : 'XXXX';
    }

    public function triggerReminders()
    {
        Artisan::call('missions:remind', ['--trigger' => 'manual']);
        return Redirect::back()->with('success', 'Rappels J-1 envoyés.');
    }

    public function triggerDayAlerts()
    {
        Artisan::call('missions:alert-day', ['--trigger' => 'manual']);
        return Redirect::back()->with('success', 'Alertes Jour J envoyées.');
    }
}
