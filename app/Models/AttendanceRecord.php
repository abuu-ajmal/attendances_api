<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'employee_id',
        'type',
        'occurred_at',
        'server_received_at',
        'latitude',
        'longitude',
        'accuracy',
        'photo_path',
        'device_id',
        'sync_status',
        'remarks',
    ];

    protected $casts = [
        'occurred_at' => 'datetime',
        'server_received_at' => 'datetime',
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'accuracy' => 'decimal:2',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(
            Employee::class
        );
    }
}