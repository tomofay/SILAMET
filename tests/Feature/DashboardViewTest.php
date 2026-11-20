<?php

namespace Tests\Feature;

use App\Models\GateLog;
use App\Models\Item;
use App\Models\Loan;
use App\Models\MaintenanceTicket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardViewTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsStaff(): User
    {
        $user = User::factory()->kabeng()->create();

        return $user;
    }

    public function test_dashboard_page_renders_with_stats(): void
    {
        $user = $this->actingAsStaff();

        Item::factory()->available()->create();
        Item::factory()->borrowed()->create();
        Item::factory()->underMaintenance()->create();
        GateLog::factory()->create(['action_status' => 'denied']);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertOk()
            ->assertSee('Total Barang')
            ->assertSee('Sedang Dipinjam')
            ->assertSee('Dalam Perbaikan');
    }

    public function test_items_index_page_renders(): void
    {
        $user = $this->actingAsStaff();
        Item::factory()->count(3)->create();

        $this->actingAs($user)->get('/items')
            ->assertOk()
            ->assertSee('Daftar Kelompok Barang')
            ->assertSee('Tambah Kelompok Barang')
            ->assertSee('Cetak Semua Barcode')
            ->assertDontSee('Buku Stok (BHP)')
            ->assertDontSee('Tambah Barang');

        $this->actingAs($user)->get('/items?view=all')
            ->assertOk()
            ->assertSee('Tambah Barang')
            ->assertDontSee('Buku Stok (BHP)');
    }

    public function test_loans_index_page_renders(): void
    {
        $user = $this->actingAsStaff();
        Loan::factory()->count(2)->create();

        $response = $this->actingAs($user)->get('/loans');

        $response->assertOk();
    }

    public function test_maintenance_index_page_renders(): void
    {
        $user = $this->actingAsStaff();
        MaintenanceTicket::factory()->count(2)->create();

        $response = $this->actingAs($user)->get('/maintenance');

        $response->assertOk()
            ->assertSee('Buat Tiket');
    }

    public function test_student_cannot_access_dashboard(): void
    {
        $student = User::factory()->siswa()->create();

        $response = $this->actingAs($student)->get('/dashboard');

        $response->assertForbidden();
    }

    public function test_dashboard_page_renders_with_overdue_loans_without_errors(): void
    {
        $user = $this->actingAsStaff();
        $studentOnTime = User::factory()->siswa()->create(['name' => 'Siswa Tepat Waktu']);
        $itemOnTime = Item::factory()->borrowed()->create(['name' => 'PC Lab 10']);

        $onTimeLoan = Loan::factory()->create([
            'user_id' => $studentOnTime->id,
            'item_id' => $itemOnTime->id,
            'status' => \App\Enums\LoanStatus::AKTIF,
            'due_at' => now()->addDays(2),
            'borrowed_at' => now(),
        ]);

        $studentLate = User::factory()->siswa()->create(['name' => 'Siswa Terlambat']);
        $itemLate = Item::factory()->borrowed()->create(['name' => 'Router Terlambat']);

        $overdueLoan = Loan::factory()->create([
            'user_id' => $studentLate->id,
            'item_id' => $itemLate->id,
            'status' => \App\Enums\LoanStatus::AKTIF,
            'due_at' => now()->subHours(5),
            'borrowed_at' => now()->subDays(1),
        ]);

        $this->assertTrue($overdueLoan->isOverdue());
        $this->assertFalse($onTimeLoan->isOverdue());
        $this->assertStringContainsString('5 jam', $overdueLoan->formattedOverdueDuration());

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertOk()
            ->assertSee('Siswa Tepat Waktu')
            ->assertSee('PC Lab 10')
            ->assertSee('Siswa Terlambat')
            ->assertSee('Router Terlambat')
            ->assertSee('Terlambat')
            ->assertSee($overdueLoan->formattedOverdueDuration());

        // Pastikan activeLoans (Kotak 1) hanya berisi pinjaman yang belum terlambat
        $activeLoans = $response->viewData('activeLoans');
        $overdueLoans = $response->viewData('overdueLoans');

        $this->assertCount(1, $activeLoans);
        $this->assertTrue($activeLoans->contains('id', $onTimeLoan->id));
        $this->assertFalse($activeLoans->contains('id', $overdueLoan->id));

        $this->assertCount(1, $overdueLoans);
        $this->assertTrue($overdueLoans->contains('id', $overdueLoan->id));
    }
}
