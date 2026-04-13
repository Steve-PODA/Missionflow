<?php

namespace Tests\Feature;

use App\Models\Mission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class StartDueMissionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_due_missions_are_started_automatically(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 4, 13, 10, 0, 0));

        try {
            $dueMission = Mission::create([
                'title'        => 'Mission arrivée à terme',
                'briefing'     => null,
                'company'      => 'Missionflow',
                'date'         => '2026-04-13',
                'start_time'   => '09:59:00',
                'duration'     => 2,
                'location'     => 'Libreville',
                'priority'     => 'medium',
                'client_name'  => 'Client A',
                'client_email' => null,
                'client_phone' => '+24170000001',
                'status'       => 'pending',
            ]);

            $futureMission = Mission::create([
                'title'        => 'Mission future',
                'briefing'     => null,
                'company'      => 'Missionflow',
                'date'         => '2026-04-13',
                'start_time'   => '10:30:00',
                'duration'     => 2,
                'location'     => 'Port-Gentil',
                'priority'     => 'medium',
                'client_name'  => 'Client B',
                'client_email' => null,
                'client_phone' => '+24170000002',
                'status'       => 'pending',
            ]);

            Artisan::call('missions:start-due');

            $this->assertDatabaseHas('missions', [
                'id'     => $dueMission->id,
                'status' => 'in_progress',
            ]);

            $this->assertDatabaseHas('missions', [
                'id'     => $futureMission->id,
                'status' => 'pending',
            ]);
        } finally {
            Carbon::setTestNow();
        }
    }
}
