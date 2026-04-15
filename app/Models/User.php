<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Notifications\Notifiable;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property int|null $plan_id
 * @property int $storage_used_bytes
 * @property \App\Models\Plan|null $subscription
 * @property \Illuminate\Database\Eloquent\Collection|\App\Models\Document[] $documents
 */
#[Fillable(['name', 'email', 'password', 'plan_id', 'storage_used_bytes'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Plan::class, 'plan_id');
    }

    public function hasAvailableStorage(int $bytes): bool
    {
        if (! $this->plan_id) {
            return false;
        }

        return ($this->storage_used_bytes + $bytes) <= ($this->subscription->storage_limit_bytes ?? 0);
    }

    public function addStorageUsage(int $bytes): void
    {
        $this->increment('storage_used_bytes', $bytes);
    }

    public function reduceStorageUsage(int $bytes): void
    {
        if ($this->storage_used_bytes >= $bytes) {
            $this->decrement('storage_used_bytes', $bytes);
        } else {
            $this->update(['storage_used_bytes' => 0]);
        }
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }
}
