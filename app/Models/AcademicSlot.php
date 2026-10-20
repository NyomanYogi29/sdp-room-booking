<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AcademicSlot extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'start_time',
        'end_time',
        'label',
        'order',
        'duration_minutes',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'order' => 'integer',
            'duration_minutes' => 'integer',
        ];
    }
}
