<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GateLog extends Model
{
    use HasFactory;

    protected $table = 'gate_logs';

    protected $fillable = [
        'epc_tag_scanned',
        'action_status',
        'capture_image_path',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }
}
