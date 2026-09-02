<?php

namespace App\Models;

use App\Enums\GateAction;
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
            'action_status' => GateAction::class,
            'created_at' => 'datetime',
        ];
    }

    /**
     * Relasi logis ke item berdasarkan EPC tag yang discan.
     */
    public function item()
    {
        return $this->belongsTo(Item::class, 'epc_tag_scanned', 'epc_tag')
            ->withTrashed();
    }
}
