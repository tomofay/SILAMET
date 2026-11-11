<?php

namespace Tests\Feature;

use App\Enums\LoanDuration;
use App\Enums\LoanStatus;
use App\Models\Item;
use App\Models\Loan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LoanPhotoViewTest extends TestCase
{
    use RefreshDatabase;

    public function test_kiosk_loan_persists_photo_path_and_displays_on_loan_show_page(): void
    {
        Storage::fake('public');

        $student = User::factory()->siswa()->create(['phone' => '081234567890']);
        $token = $student->createToken('kiosk')->plainTextToken;
        $teknisi = User::factory()->teknisi()->create();

        $item1 = Item::factory()->available()->create(['name' => 'MikroTik RB750Gr3']);
        $item2 = Item::factory()->available()->create(['name' => 'Tang Krimping Pro']);

        $jpegBinary = "\xFF\xD8\xFF\xE0\x00\x10JFIFloanface";
        $base64Photo = 'data:image/jpeg;base64,' . base64_encode($jpegBinary);

        $apiRes = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/loans', [
                'user_id' => $student->id,
                'item_ids' => [$item1->id, $item2->id],
                'usage_duration' => LoanDuration::SATU_HARI->value,
                'photo' => $base64Photo,
            ])
            ->assertOk();

        $savedPath = $apiRes->json('data.photo_path');
        $this->assertNotEmpty($savedPath);
        Storage::disk('public')->assertExists($savedPath);

        $loans = Loan::orderBy('id')->get();
        $this->assertCount(2, $loans);
        $this->assertSame($savedPath, $loans[0]->capture_image_path);
        $this->assertSame($savedPath, $loans[1]->capture_image_path);

        $photoRoute = route('loans.photo', $loans[0]->id);

        $this->actingAs($teknisi)
            ->get(route('loans.show', $loans[0]->id))
            ->assertOk()
            ->assertSee('Foto Verifikasi Peminjam (Kiosk)')
            ->assertSee($photoRoute);

        $photoResponse = $this->actingAs($teknisi)->get($photoRoute);
        $photoResponse->assertOk()->assertHeader('Content-Type', 'image/jpeg');
        $this->assertSame($jpegBinary, $photoResponse->getContent());
    }

    public function test_loan_photo_route_blocks_student_and_returns_404_when_missing(): void
    {
        Storage::fake('public');

        $student = User::factory()->siswa()->create();
        $teknisi = User::factory()->teknisi()->create();

        $loanNoPhoto = Loan::factory()->create([
            'status' => LoanStatus::AKTIF,
            'capture_image_path' => null,
        ]);

        $this->actingAs($student)
            ->get(route('loans.photo', $loanNoPhoto->id))
            ->assertForbidden();

        $this->actingAs($teknisi)
            ->get(route('loans.photo', $loanNoPhoto->id))
            ->assertNotFound();
    }
}

