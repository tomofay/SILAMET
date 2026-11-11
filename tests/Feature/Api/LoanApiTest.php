<?php

namespace Tests\Feature\Api;

use App\Enums\ItemStatus;
use App\Enums\LoanStatus;
use App\Models\Item;
use App\Models\Loan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoanApiTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsStudent(): array
    {
        $student = User::factory()->create(['role' => 'siswa']);
        $token = $student->createToken('kiosk-test')->plainTextToken;

        return [$student, $token];
    }

    public function test_student_can_borrow_available_item(): void
    {
        [$student, $token] = $this->actingAsStudent();
        $item = Item::factory()->available()->create();

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/loans', [
                'user_id' => $student->id,
                'item_id' => $item->id,
                'usage_duration' => '1_hari',
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Peminjaman berhasil dicatat.');

        // Item harus terkunci menjadi dipinjam
        $this->assertSame('dipinjam', Item::find($item->id)->status->value);

        // Loan tercatat aktif
        $this->assertDatabaseHas('loans', [
            'user_id' => $student->id,
            'item_id' => $item->id,
            'status' => 'aktif',
            'usage_duration' => '1_hari',
        ]);
    }

    public function test_cannot_borrow_unavailable_item(): void
    {
        [$student, $token] = $this->actingAsStudent();
        $item = Item::factory()->borrowed()->create();

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/loans', [
                'user_id' => $student->id,
                'item_id' => $item->id,
                'usage_duration' => '1_hari',
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false);

        $this->assertDatabaseCount('loans', 0);
    }

    public function test_cannot_borrow_item_with_active_loan(): void
    {
        [$student, $token] = $this->actingAsStudent();
        $item = Item::factory()->create(['status' => 'dipinjam']);
        Loan::factory()->create([
            'user_id' => $student->id,
            'item_id' => $item->id,
            'status' => 'aktif',
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/loans', [
                'user_id' => $student->id,
                'item_id' => $item->id,
                'usage_duration' => '1_hari',
            ]);

        $response->assertStatus(422);
    }

    public function test_loan_requires_authentication(): void
    {
        $item = Item::factory()->create();

        $response = $this->postJson('/api/v1/loans', [
            'item_id' => $item->id,
        ]);

        $response->assertStatus(401);
    }

    public function test_return_in_good_condition_restores_item(): void
    {
        [$student, $token] = $this->actingAsStudent();
        $item = Item::factory()->create(['status' => 'dipinjam']);
        $loan = Loan::factory()->create([
            'user_id' => $student->id,
            'item_id' => $item->id,
            'status' => 'aktif',
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson("/api/v1/loans/{$loan->id}/return", [
                'condition_in' => 'bagus',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.item_status', 'tersedia');

        $this->assertDatabaseHas('loans', [
            'id' => $loan->id,
            'status' => 'selesai',
            'condition_in' => 'bagus',
        ]);
        $this->assertNotNull(Loan::find($loan->id)->returned_at);
    }

    public function test_return_in_damaged_condition_creates_ticket_and_locks_item(): void
    {
        [$student, $token] = $this->actingAsStudent();
        $item = Item::factory()->create(['status' => 'dipinjam']);
        $loan = Loan::factory()->create([
            'user_id' => $student->id,
            'item_id' => $item->id,
            'status' => 'aktif',
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson("/api/v1/loans/{$loan->id}/return", [
                'condition_in' => 'rusak',
                'issue_description' => 'Kabel LAN putus dan port longgar',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.item_status', 'under_maintenance');

        $this->assertDatabaseHas('maintenance_tickets', [
            'item_id' => $item->id,
            'status' => 'open',
        ]);
        $this->assertDatabaseHas('loans', [
            'id' => $loan->id,
            'status' => 'selesai',
            'condition_in' => 'rusak',
        ]);
    }

    public function test_cannot_return_already_returned_loan(): void
    {
        [$student, $token] = $this->actingAsStudent();
        $item = Item::factory()->create(['status' => 'tersedia']);
        $loan = Loan::factory()->returned()->create([
            'user_id' => $student->id,
            'item_id' => $item->id,
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson("/api/v1/loans/{$loan->id}/return", [
                'condition_in' => 'bagus',
            ]);

        $response->assertStatus(422);
    }

    public function test_return_requires_valid_condition(): void
    {
        [$student, $token] = $this->actingAsStudent();
        $item = Item::factory()->create(['status' => 'dipinjam']);
        $loan = Loan::factory()->create([
            'user_id' => $student->id,
            'item_id' => $item->id,
            'status' => 'aktif',
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson("/api/v1/loans/{$loan->id}/return", [
                'condition_in' => 'invalid',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('condition_in');
    }

    public function test_borrow_requires_valid_usage_duration(): void
    {
        [$student, $token] = $this->actingAsStudent();
        $item = Item::factory()->available()->create();

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/loans', [
                'user_id' => $student->id,
                'item_id' => $item->id,
                'usage_duration' => 'sebulan',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('usage_duration');

        $this->assertDatabaseCount('loans', 0);
    }

    public function test_borrow_with_duration_sets_due_at(): void
    {
        [$student, $token] = $this->actingAsStudent();
        $item = Item::factory()->available()->create();

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/loans', [
                'user_id' => $student->id,
                'item_id' => $item->id,
                'usage_duration' => '2_hari',
            ]);

        $response->assertOk();

        $loan = Loan::first();
        $this->assertNotNull($loan->due_at);
        $this->assertGreaterThan(now(), $loan->due_at);
        $this->assertSame('2_hari', $loan->usage_duration->value);
    }

    public function test_borrow_rejected_when_student_has_no_phone(): void
    {
        $student = User::factory()->create([
            'role' => 'siswa',
            'phone' => null,
        ]);
        $token = $student->createToken('kiosk-test')->plainTextToken;
        $item = Item::factory()->available()->create();

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/loans', [
                'user_id' => $student->id,
                'item_id' => $item->id,
                'usage_duration' => '1_hari',
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('message', 'Nomor WhatsApp siswa belum terdaftar. Hubungi admin untuk melengkapi data.');

        $this->assertDatabaseCount('loans', 0);
    }

    public function test_get_active_loans_by_nis_returns_borrowed_items(): void
    {
        $student = User::factory()->create(['nis_nip' => '12345678', 'role' => 'siswa']);
        $item1 = Item::factory()->create(['status' => 'dipinjam', 'item_code' => 'KOD-001']);
        $item2 = Item::factory()->create(['status' => 'dipinjam', 'item_code' => 'KOD-002']);

        Loan::factory()->create([
            'user_id' => $student->id,
            'item_id' => $item1->id,
            'status' => 'aktif',
        ]);
        Loan::factory()->create([
            'user_id' => $student->id,
            'item_id' => $item2->id,
            'status' => 'aktif',
        ]);

        $response = $this->getJson('/api/v1/loans/active-by-nis?nis=12345678');

        $response->assertOk()
            ->assertJsonPath('data.user.nis_nip', '12345678')
            ->assertJsonCount(2, 'data.active_loans');
    }

    public function test_return_kiosk_multi_items_success_only_by_borrower(): void
    {
        $student = User::factory()->create(['nis_nip' => '11223344', 'role' => 'siswa']);
        $item1 = Item::factory()->create(['status' => 'dipinjam', 'item_code' => 'KOD-AAA']);
        $item2 = Item::factory()->create(['status' => 'dipinjam', 'item_code' => 'KOD-BBB']);

        $loan1 = Loan::factory()->create([
            'user_id' => $student->id,
            'item_id' => $item1->id,
            'status' => 'aktif',
        ]);
        $loan2 = Loan::factory()->create([
            'user_id' => $student->id,
            'item_id' => $item2->id,
            'status' => 'aktif',
        ]);

        $response = $this->postJson('/api/v1/loans/return-kiosk', [
            'nis' => '11223344',
            'items' => [
                ['item_code' => 'KOD-AAA', 'condition_in' => 'bagus'],
                ['item_code' => 'KOD-BBB', 'condition_in' => 'rusak', 'issue_description' => 'Port LAN rusak'],
            ],
        ]);

        $response->assertOk()
            ->assertJsonPath('data.total_returned', 2);

        $this->assertEquals('tersedia', $item1->fresh()->status->value);
        $this->assertEquals('under_maintenance', $item2->fresh()->status->value);
        $this->assertEquals('selesai', $loan1->fresh()->status->value);
        $this->assertEquals('selesai', $loan2->fresh()->status->value);

        $this->assertDatabaseHas('maintenance_tickets', [
            'item_id' => $item2->id,
            'reported_by' => $student->id,
        ]);
    }

    public function test_return_kiosk_rejected_if_not_borrowed_by_same_student(): void
    {
        $borrower = User::factory()->create(['nis_nip' => 'STUDENT-ORIG', 'name' => 'Budi']);
        $intruder = User::factory()->create(['nis_nip' => 'STUDENT-OTHER', 'name' => 'Andi']);
        $item = Item::factory()->create(['status' => 'dipinjam', 'item_code' => 'KOD-SECRET']);

        Loan::factory()->create([
            'user_id' => $borrower->id,
            'item_id' => $item->id,
            'status' => 'aktif',
        ]);

        $response = $this->postJson('/api/v1/loans/return-kiosk', [
            'nis' => 'STUDENT-OTHER',
            'items' => [
                ['item_code' => 'KOD-SECRET', 'condition_in' => 'bagus'],
            ],
        ]);

        $response->assertStatus(403);
        $this->assertEquals('dipinjam', $item->fresh()->status->value);
    }

    public function test_return_kiosk_damaged_item_requires_issue_description(): void
    {
        $borrower = User::factory()->create(['nis_nip' => 'STUDENT-DAMAGE-1']);
        $item = Item::factory()->create(['status' => 'dipinjam', 'item_code' => 'KOD-BROKEN-1']);

        Loan::factory()->create([
            'user_id' => $borrower->id,
            'item_id' => $item->id,
            'status' => 'aktif',
        ]);

        // Kirim kondisi rusak TANPA catatan kerusakan
        $response = $this->postJson('/api/v1/loans/return-kiosk', [
            'nis' => 'STUDENT-DAMAGE-1',
            'items' => [
                ['item_code' => 'KOD-BROKEN-1', 'condition_in' => 'rusak', 'issue_description' => ''],
            ],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['items.0.issue_description']);
    }

    public function test_return_kiosk_damaged_item_with_description_succeeds(): void
    {
        $borrower = User::factory()->create(['nis_nip' => 'STUDENT-DAMAGE-2']);
        $item = Item::factory()->create(['status' => 'dipinjam', 'item_code' => 'KOD-BROKEN-2']);

        Loan::factory()->create([
            'user_id' => $borrower->id,
            'item_id' => $item->id,
            'status' => 'aktif',
        ]);

        // Kirim kondisi rusak DENGAN catatan kerusakan
        $response = $this->postJson('/api/v1/loans/return-kiosk', [
            'nis' => 'STUDENT-DAMAGE-2',
            'items' => [
                [
                    'item_code' => 'KOD-BROKEN-2', 
                    'condition_in' => 'rusak', 
                    'issue_description' => 'Port LAN longgar dan lampu indikator padam'
                ],
            ],
        ]);

        $response->assertOk();
        $this->assertEquals('under_maintenance', $item->fresh()->status->value);
        $this->assertDatabaseHas('maintenance_tickets', [
            'item_id' => $item->id,
            'issue_description' => 'Port LAN longgar dan lampu indikator padam',
        ]);
    }
}
