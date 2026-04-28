<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'communication_dispatch_id',
    'employee_id',
    'email',
    'name',
    'status',
    'error_message',
    'sent_at',
    'failed_at',
])]
class CommunicationDispatchRecipient extends Model
{
    use HasUuids;

    /**
     * @return BelongsTo<CommunicationDispatch, $this>
     */
    public function dispatch(): BelongsTo
    {
        return $this->belongsTo(CommunicationDispatch::class, 'communication_dispatch_id');
    }

    /**
     * @return BelongsTo<Employee, $this>
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function isSuccessful(): bool
    {
        return $this->status === 'sent';
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
            'failed_at' => 'datetime',
        ];
    }
}
