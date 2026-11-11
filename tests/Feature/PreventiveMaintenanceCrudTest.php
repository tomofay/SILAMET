<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\PreventiveSchedule;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PreventiveMaintenanceCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_teknisi_can_manage_preventive_schedules_and_mark_serviced(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-10 10:00:00', 'Asia/Jakarta'));

        $teknisi = User::factory()->teknisi()->create();
        $item = Item::factory()->create(['name' => 'MikroTik RB951Ui']);

        $this->actingAs($teknisi)
            ->get(route('maintenance.schedules.index'))
            ->assertOk()
            ->assertSee('Penjadwalan Perawatan Preventif Berkala');

        $this->actingAs($teknisi)
            ->post(route('maintenance.schedules.store'), [
                'item_id' => $item->id,
                'title' => 'Kalibrasi & Pembersihan Fan',
                'interval_months' => 3,
                'last_serviced_at' => '2026-08-10',
                'status' => 'scheduled',
                'notes' => 'Periksa PSU dan port.',
            ])
            ->assertRedirect(route('maintenance.schedules.index'));

        $schedule = PreventiveSchedule::firstOrFail();
        $this->assertSame('2026-11-10', $schedule->next_due_date->format('Y-m-d'));

        // Tandai sudah diservis hari ini (10 Nov 2026) -> otomatis geser +3 bulan ke 10 Feb 2027
        $schedule->update(['last_notified_stage' => 'h0']);

        $this->actingAs($teknisi)
            ->post(route('maintenance.schedules.mark-serviced', $schedule->id))
            ->assertRedirect(route('maintenance.schedules.index'));

        $schedule->refresh();
        $this->assertSame('2026-11-10', $schedule->last_serviced_at->format('Y-m-d'));
        $this->assertSame('2027-02-10', $schedule->next_due_date->format('Y-m-d'));
        $this->assertNull($schedule->last_notified_stage);

        Carbon::setTestNow();
    }

    public function test_student_cannot_access_preventive_schedules(): void
    {
        $student = User::factory()->siswa()->create();

        $this->actingAs($student)
            ->get(route('maintenance.schedules.index'))
            ->assertForbidden();
    }
}

