<?php

namespace App\Models;

use App\Enums\LoanStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Loan extends Model
{
    use HasFactory;

    protected $table = 'loans';

    protected $fillable = [
        'user_id',
        'item_id',
        'status',
        'condition_in',
        'borrowed_at',
        'returned_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => LoanStatus::class,
            'borrowed_at' => 'datetime',
            'returned_at' => 'datetime',
        ];
    }

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }
}
