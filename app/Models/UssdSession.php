<?php

namespace App\Models;

use Database\Factories\UssdSessionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $session_id
 * @property string $phone_number
 * @property int $current_step
 * @property ?array $data
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
#[Fillable([
    'session_id',
    'phone_number',
    'current_step',
    'data',
])]
class UssdSession extends Model
{
    /** @use HasFactory<UssdSessionFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'current_step' => 'integer',
            'data' => 'array',
        ];
    }
}
