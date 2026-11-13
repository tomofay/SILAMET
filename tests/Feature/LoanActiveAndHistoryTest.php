<?php

namespace Tests\Feature;

use App\Enums\LoanDuration;
use App\Enums\LoanStatus;
use App\Models\Item;
use App\Models\Loan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoanActiveAndHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_loans_index_only_displays_ongoing_loans_and_excludes_completed_loans(): void
    {
        $teknisi = User::factory()->teknisi()->create();
        $student1 = User::factory()->siswa()->create(['name' => 'Budi Siswa Aktif', 'nis_nip' => '11223344']);
        $student2 = User::factory()->siswa()->create(['name' => 'Siti Siswa Selesai', 'nis_nip' => '55667788']);

        $itemAktif = Item::factory()->borrowed()->create(['name' => 'MikroTik RB750Gr3', 'item_code' => 'SLM-RTR-01']);
        $itemSelesai = Item::factory()->available()->create(['name' => 'Switch D-Link 24P', 'item_code' => 'SLM-SW-02']);

        // 1. Peminjaman Aktif
        $loanAktif = Loan::factory()->create([
            'user_id' => $student1->id,
            'item_id' => $itemAktif->id,
            'status' => LoanStatus::AKTIF,
            'usage_duration' => LoanDuration::SATU_HARI,
            'borrowed_at' => now()->subHours(2),
            'due_at' => now()->addHours(6),
        ]);

        // 2. Peminjaman Selesai (Histori)
        $loanSelesai = Loan::factory()->create([
            'user_id' => $student2->id,
            'item_id' => $itemSelesai->id,
            'status' => LoanStatus::SELESAI,
            'usage_duration' => LoanDuration::SATU_HARI,
            'borrowed_at' => now()->subDays(3),
            'due_at' => now()->subDays(2),
            'returned_at' => now()->subDays(2),
            'condition_in' => 'bagus',
        ]);

        // Kunjungi halaman Peminjaman Alat Aktif (loans.index)
        $response = $this->actingAs($teknisi)->get(route('loans.index'));

        $response->assertOk();
        $response->assertSee('Sirkulasi Peminjaman Alat Aktif');
        $response->assertSee('Budi Siswa Aktif');
        $response->assertSee('MikroTik RB750Gr3');
        $response->assertSee('SLM-RTR-01');

        // Pastikan peminjaman yang berstatus selesai TIDAK muncul di daftar peminjaman aktif
        $response->assertDontSee('Siti Siswa Selesai');
        $response->assertDontSee('Switch D-Link 24P');
        $response->assertDontSee('SLM-SW-02');
    }

    public function test_loans_history_displays_completed_loans_with_filters(): void
    {
        $teknisi = User::factory()->teknisi()->create();
        $student1 = User::factory()->siswa()->create(['name' => 'Budi Siswa Aktif']);
        $student2 = User::factory()->siswa()->create(['name' => 'Siti Siswa Selesai']);

        $itemAktif = Item::factory()->borrowed()->create(['name' => 'MikroTik RB750Gr3']);
        $itemSelesai = Item::factory()->available()->create(['name' => 'Switch D-Link 24P']);

        Loan::factory()->create([
            'user_id' => $student1->id,
            'item_id' => $itemAktif->id,
            'status' => LoanStatus::AKTIF,
        ]);

        Loan::factory()->create([
            'user_id' => $student2->id,
            'item_id' => $itemSelesai->id,
            'status' => LoanStatus::SELESAI,
            'returned_at' => now()->subDay(),
            'condition_in' => 'bagus',
        ]);

        // Kunjungi halaman Histori Peminjaman (loans.history)
        $response = $this->actingAs($teknisi)->get(route('loans.history'));

        $response->assertOk();
        $response->assertSee('Histori Peminjaman Alat');
        $response->assertSee('Siti Siswa Selesai');
        $response->assertSee('Switch D-Link 24P');
        $response->assertSee('Kondisi Bagus');

        // Peminjaman yang masih aktif tidak masuk di histori selesai
        $response->assertDontSee('Budi Siswa Aktif');
        $response->assertDontSee('MikroTik RB750Gr3');
    }

    public function test_student_cannot_access_loans_index_or_history(): void
    {
        $student = User::factory()->siswa()->create();

        $this->actingAs($student)
            ->get(route('loans.index'))
            ->assertForbidden();

        $this->actingAs($student)
            ->get(route('loans.history'))
            ->assertForbidden();
    }

    public function test_can_create_loan_with_custom_connector_quantity(): void
    {
        $teknisi = User::factory()->teknisi()->create();
        $student = User::factory()->siswa()->create(['phone' => '08123456789']);
        $connector = Item::factory()->available()->create([
            'name' => 'Konektor RJ45 Cat6',
            'type' => \App\Enums\ItemType::BAHAN_HABIS_PAKAI,
            'stock' => 100,
        ]);

        $response = $this->actingAs($teknisi)->post(route('loans.store'), [
            'user_id' => $student->id,
            'item_ids' => [$connector->id],
            'quantities' => [
                $connector->id => 15,
            ],
            'usage_duration' => LoanDuration::PULANG_SEKOLAH->value,
        ]);

        $response->assertRedirect(route('loans.index'));

        $this->assertDatabaseHas('loans', [
            'user_id' => $student->id,
            'item_id' => $connector->id,
            'quantity_borrowed' => 15,
            'status' => 'aktif',
        ]);

        $indexResponse = $this->actingAs($teknisi)->get(route('loans.index'));
        $indexResponse->assertOk()
            ->assertSee('15 pcs');
    }
}
