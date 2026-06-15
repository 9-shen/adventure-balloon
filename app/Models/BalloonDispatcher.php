<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TracksDeletedBy;

class BalloonDispatcher extends Model
{
    use HasFactory, SoftDeletes, TracksDeletedBy;

    public static bool $isRestoringLinked = false;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'is_active',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    // ─── Relationships ────────────────────────────────────────────────────────

    public function user(): HasOne
    {
        return $this->hasOne(User::class, 'balloon_dispatcher_id');
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    public function hasPortalAccount(): bool
    {
        return $this->user()->exists();
    }

    // ─── Model Observer ───────────────────────────────────────────────────────

    protected static function booted(): void
    {
        static::created(function (BalloonDispatcher $dispatcher) {
            if ($dispatcher->email) {
                $rawPassword = '1234567890';

                $user = User::firstOrCreate(
                    ['email' => $dispatcher->email],
                    [
                        'name'                  => $dispatcher->name,
                        'password'              => \Illuminate\Support\Facades\Hash::make($rawPassword),
                        'phone'                 => $dispatcher->phone,
                        'is_active'             => $dispatcher->is_active,
                        'balloon_dispatcher_id' => $dispatcher->id,
                    ]
                );

                // If the user already existed, ensure the FK is linked
                if (! $user->wasRecentlyCreated) {
                    $user->forceFill(['balloon_dispatcher_id' => $dispatcher->id])->saveQuietly();
                }

                // Sync to exactly 'balloon_dispatcher' role
                $user->syncRoles(['balloon_dispatcher']);

                try {
                    \Illuminate\Support\Facades\Notification::route('mail', $dispatcher->email)
                        ->notify(new \App\Notifications\BalloonDispatcherAccountCreatedNotification(
                            $dispatcher->name,
                            $dispatcher->email,
                            $rawPassword
                        ));
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error('Failed to notify balloon dispatcher: ' . $e->getMessage());
                }
            }
        });

        static::updated(function (BalloonDispatcher $dispatcher) {
            if ($dispatcher->isDirty(['name', 'email', 'phone', 'is_active'])) {
                if ($user = $dispatcher->user) {
                    $user->fill($dispatcher->only(['name', 'email', 'phone', 'is_active']))->saveQuietly();
                }
            }
        });

        static::deleting(function (BalloonDispatcher $dispatcher) {
            if ($dispatcher->isForceDeleting()) {
                $dispatcher->user()->forceDelete();
            } else {
                if ($dispatcher->email && !str_contains($dispatcher->email, '_deleted_')) {
                    $dispatcher->email = $dispatcher->email . '_deleted_' . time();
                    $dispatcher->saveQuietly();
                }
                $dispatcher->user()->delete();
            }
        });

        static::restoring(function (BalloonDispatcher $dispatcher) {
            if (static::$isRestoringLinked) {
                return;
            }
            static::$isRestoringLinked = true;

            try {
                if ($dispatcher->email && str_contains($dispatcher->email, '_deleted_')) {
                    $originalEmail = explode('_deleted_', $dispatcher->email)[0];
                    if (static::where('email', $originalEmail)->exists()) {
                        throw new \Exception("Cannot restore balloon dispatcher: The email '{$originalEmail}' is already taken by another active dispatcher.");
                    }
                    $dispatcher->email = $originalEmail;
                    $dispatcher->saveQuietly();
                }
                User::onlyTrashed()->where('balloon_dispatcher_id', $dispatcher->id)->first()?->restore();
            } finally {
                static::$isRestoringLinked = false;
            }
        });
    }
}
