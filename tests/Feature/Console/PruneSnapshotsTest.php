<?php

namespace Tests\Feature\Console;

use App\Enums\GateAction;
use App\Models\GateLog;
use App\Models\Loan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PruneSnapshotsTest extends TestCase
{
    use RefreshDatabase;

    public function test_snapshots_prune_deletes_old_files_and_clears_paths_while_preserving_recent_and_shared(): void
    {
        Storage::fake('public');

        $oldGatePath = 'uploads/photos/gate/old_violation.jpg';
        $sharedBurstPath = 'uploads/photos/gate/shared_burst.jpg';
        $recentGatePath = 'uploads/photos/gate/recent_violation.jpg';
        $oldLoanPath = 'uploads/photos/loans/old_loan.jpg';
        $recentLoanPath = 'uploads/photos/loans/recent_loan.jpg';

        foreach ([$oldGatePath, $sharedBurstPath, $recentGatePath, $oldLoanPath, $recentLoanPath] as $p) {
            Storage::disk('public')->put($p, "\xFF\xD8\xFF\xE0data");
        }

        // GateLog > 60 hari (harus terhapus)
        $oldGate = GateLog::factory()->create([
            'action_status' => GateAction::DENIED,
            'capture_image_path' => $oldGatePath,
            'created_at' => now()->subDays(75),
        ]);

        // Burst lama + log baru berbagi file: file fisik harus tetap aman karena masih dirujuk log baru!
        $oldSharedGate = GateLog::factory()->create([
            'action_status' => GateAction::DENIED,
            'capture_image_path' => $sharedBurstPath,
            'created_at' => now()->subDays(70),
        ]);
        $newSharedGate = GateLog::factory()->create([
            'action_status' => GateAction::DENIED,
            'capture_image_path' => $sharedBurstPath,
            'created_at' => now()->subDays(5),
        ]);

        // GateLog baru (< 60 hari): tetap utuh
        $recentGate = GateLog::factory()->create([
            'action_status' => GateAction::DENIED,
            'capture_image_path' => $recentGatePath,
            'created_at' => now()->subDays(10),
        ]);

        // Loan lama vs baru
        $oldLoan = Loan::factory()->create([
            'capture_image_path' => $oldLoanPath,
            'created_at' => now()->subDays(90),
        ]);
        $recentLoan = Loan::factory()->create([
            'capture_image_path' => $recentLoanPath,
            'created_at' => now()->subDays(15),
        ]);

        // Dry run tidak menghapus apa pun
        $this->artisan('snapshots:prune --days=60 --dry-run')
            ->assertExitCode(0);

        Storage::disk('public')->assertExists($oldGatePath);
        Storage::disk('public')->assertExists($oldLoanPath);
        $this->assertNotNull($oldGate->fresh()->capture_image_path);

        // Eksekusi pruning sesungguhnya
        $this->artisan('snapshots:prune --days=60')
            ->assertExitCode(0);

        Storage::disk('public')->assertMissing($oldGatePath);
        Storage::disk('public')->assertMissing($oldLoanPath);

        Storage::disk('public')->assertExists($sharedBurstPath);
        Storage::disk('public')->assertExists($recentGatePath);
        Storage::disk('public')->assertExists($recentLoanPath);

        $this->assertNull($oldGate->fresh()->capture_image_path);
        $this->assertNull($oldSharedGate->fresh()->capture_image_path);
        $this->assertNull($oldLoan->fresh()->capture_image_path);

        $this->assertSame($sharedBurstPath, $newSharedGate->fresh()->capture_image_path);
        $this->assertSame($recentGatePath, $recentGate->fresh()->capture_image_path);
        $this->assertSame($recentLoanPath, $recentLoan->fresh()->capture_image_path);
    }
}

