<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Folder extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'parent_id',
        'name',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Folder::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Folder::class, 'parent_id');
    }

    protected static function booted()
    {
        static::deleting(function (Folder $folder) {
            if ($folder->isForceDeleting()) {
                $folder->documents()->forceDelete();
                $folder->children()->get()->each->forceDelete();
            } else {
                $folder->documents()->delete();
                $folder->children()->get()->each->delete();
            }
        });

        static::restoring(function (Folder $folder) {
            $folder->documents()->withTrashed()->restore();
            $folder->children()->withTrashed()->get()->each->restore();
        });
    }
}
