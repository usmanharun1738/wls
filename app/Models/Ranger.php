<?php

namespace App\Models;

use Database\Factories\RangerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string $phone_number
 * @property ?string $email
 * @property ?string $base_location
 * @property ?float $latitude
 * @property ?float $longitude
 * @property bool $is_active
 * @property string $pin
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
#[Fillable([
    'name',
    'phone_number',
    'email',
    'base_location',
    'latitude',
    'longitude',
    'is_active',
    'pin',
])]
class Ranger extends Model
{
    /** @use HasFactory<RangerFactory> */
    use HasFactory;

    /**
     * Reports this ranger was alerted about.
     */
    public function reports(): BelongsToMany
    {
        return $this->belongsToMany(Report::class, 'report_ranger')
            ->withPivot(['alerted_at', 'sms_status', 'sms_message_id']);
    }

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:8',
            'longitude' => 'decimal:8',
            'is_active' => 'boolean',
        ];
    }
}
