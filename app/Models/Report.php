<?php

namespace App\Models;

use Database\Factories\ReportFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $reference_id
 * @property string $phone_number
 * @property string $incident_type
 * @property string $location
 * @property ?float $latitude
 * @property ?float $longitude
 * @property ?string $description
 * @property string $status
 * @property ?string $rejection_reason
 * @property ?int $verified_by
 * @property float $reward_amount
 * @property bool $reward_sent
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
#[Fillable([
    'reference_id',
    'phone_number',
    'incident_type',
    'location',
    'latitude',
    'longitude',
    'description',
    'status',
    'rejection_reason',
    'verified_by',
    'reward_amount',
    'reward_sent',
])]
class Report extends Model
{
    /** @use HasFactory<ReportFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:8',
            'longitude' => 'decimal:8',
            'reward_amount' => 'decimal:2',
            'reward_sent' => 'boolean',
        ];
    }

    /**
     * The admin who verified this report.
     */
    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    /**
     * Rangers who were alerted about this report.
     */
    public function rangers(): BelongsToMany
    {
        return $this->belongsToMany(Ranger::class, 'report_ranger')
            ->withPivot(['alerted_at', 'sms_status', 'sms_message_id']);
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isVerified(): bool
    {
        return $this->status === 'verified';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }
}
