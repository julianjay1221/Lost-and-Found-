<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'type',
    'item_name',
    'category',
    'happened_at',
    'location',
    'description',
    'contact_name',
    'contact_email',
    'contact_phone',
    'photo_path',
    'status',
    'admin_notes',
    'ip_address',
    'is_spam',
    'reviewed_at',
    'blocked_at',
    'claimed_at',
    'claim_confirmed_at',
    'matched_report_id',
    'closed_at',
    'archived_at',
])]
class ItemReport extends Model
{
    /** @use HasFactory<\Database\Factories\ItemReportFactory> */
    use HasFactory;

    public const TYPE_LOST = 'lost';
    public const TYPE_FOUND = 'found';

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_FOUND = 'found';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_BLOCKED = 'blocked';
    public const STATUS_CLAIMED = 'claimed';
    public const STATUS_CLOSED = 'closed';
    public const STATUS_ARCHIVED = 'archived';

    public const DEFAULT_CATEGORIES = [
        'Accessories',
        'Bags',
        'Books',
        'Clothing',
        'Drinkware',
        'Electronics',
        'IDs & Documents',
        'Keys',
        'Personal Items',
        'School Supplies',
        'Sports Equipment',
        'Wallets & Money',
    ];

    protected function casts(): array
    {
        return [
            'happened_at' => 'datetime',
            'is_spam' => 'boolean',
            'reviewed_at' => 'datetime',
            'blocked_at' => 'datetime',
            'claimed_at' => 'datetime',
            'claim_confirmed_at' => 'datetime',
            'closed_at' => 'datetime',
            'archived_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function matchedReport(): BelongsTo
    {
        return $this->belongsTo(self::class, 'matched_report_id');
    }

    public function scopePublic(Builder $query): Builder
    {
        return $query->where(function (Builder $query) {
            $query
                ->where('status', self::STATUS_APPROVED)
                ->orWhere(function (Builder $query) {
                    $query
                        ->where('type', self::TYPE_FOUND)
                        ->where('status', self::STATUS_CLAIMED)
                        ->whereNotNull('claim_confirmed_at');
                });
        });
    }

    public function scopeActiveUnresolvedLost(Builder $query): Builder
    {
        return $query
            ->where('type', self::TYPE_LOST)
            ->where('status', self::STATUS_APPROVED);
    }

    public function scopeNotArchived(Builder $query): Builder
    {
        return $query->where('status', '!=', self::STATUS_ARCHIVED);
    }

    public function scopeClaimedOrClosed(Builder $query): Builder
    {
        return $query->whereIn('status', [self::STATUS_FOUND, self::STATUS_CLAIMED, self::STATUS_CLOSED, self::STATUS_ARCHIVED]);
    }

    public function canBeDeletedByStudent(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_APPROVED, self::STATUS_REJECTED], true);
    }

    public function oppositeType(): string
    {
        return $this->type === self::TYPE_LOST ? self::TYPE_FOUND : self::TYPE_LOST;
    }

    public function photoUrl(): ?string
    {
        return $this->photo_path ? asset($this->photo_path) : null;
    }
}
