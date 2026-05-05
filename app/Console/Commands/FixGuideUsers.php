<?php

namespace App\Console\Commands;

use App\Models\Guide;
use App\Models\User;
use Illuminate\Console\Command;

class FixGuideUsers extends Command
{
    protected $signature   = 'booklix:fix-guide-users {--dry-run : Show what would change without applying}';
    protected $description = 'Repair guide portal users: link guide_id, strip stale roles, sync to guide-only role.';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        $this->info($dryRun ? '🔍 DRY RUN — no changes will be saved.' : '🔧 Fixing guide portal users...');

        $guides = Guide::withTrashed()->whereNotNull('email')->with('user')->get();

        $fixed   = 0;
        $skipped = 0;

        foreach ($guides as $guide) {
            // Find the matching user by email (they may not be linked yet)
            $user = User::withTrashed()->where('email', $guide->email)->first();

            if (! $user) {
                $this->line("  ⚠️  Guide #{$guide->id} ({$guide->email}) has no user account — skipping.");
                $skipped++;
                continue;
            }

            $changes = [];

            // 1. Fix missing guide_id link
            if ($user->guide_id !== $guide->id) {
                $changes[] = "guide_id: {$user->guide_id} → {$guide->id}";
                if (! $dryRun) {
                    $user->forceFill(['guide_id' => $guide->id])->saveQuietly();
                }
            }

            // 2. Fix stale partner_id (should not be set on guide users)
            if ($user->partner_id !== null) {
                $changes[] = "partner_id: {$user->partner_id} → null";
                if (! $dryRun) {
                    $user->forceFill(['partner_id' => null])->saveQuietly();
                }
            }

            // 3. Sync roles to guide-only
            $currentRoles = $user->getRoleNames()->toArray();
            if ($currentRoles !== ['guide']) {
                $changes[] = 'roles: [' . implode(', ', $currentRoles) . '] → [guide]';
                if (! $dryRun) {
                    $user->syncRoles(['guide']);
                }
            }

            if (empty($changes)) {
                $this->line("  ✅  Guide #{$guide->id} ({$guide->email}) — already correct.");
            } else {
                $this->warn("  🔨  Guide #{$guide->id} ({$guide->email}):");
                foreach ($changes as $change) {
                    $this->line("       • {$change}");
                }
                $fixed++;
            }
        }

        $this->newLine();
        $this->info("Done. Fixed: {$fixed} | Skipped (no user): {$skipped}");

        if ($dryRun && $fixed > 0) {
            $this->warn('Run without --dry-run to apply changes.');
        }

        return self::SUCCESS;
    }
}
