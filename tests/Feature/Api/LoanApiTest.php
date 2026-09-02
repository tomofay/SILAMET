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
}
