<?php

namespace App\Models;

use App\Concerns\BelongsToCurrentCompany;
use App\Concerns\LogsAudit;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'company_id',
    'communication_template_id',
    'dispatched_by_user_id',
    'channel',
    'subject',
    'body',
    'recipient_count',
    'success_count',
    'failed_count',
    'dispatched_at',
])]
class CommunicationDispatch extends Model
{
    use BelongsToCurrentCompany, HasUuids, LogsAudit;

    /**
     * @return BelongsTo<CommunicationTemplate, $this>
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(CommunicationTemplate::class, 'communication_template_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function dispatchedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dispatched_by_user_id');
    }

    /**
     * @return HasMany<CommunicationDispatchRecipient, $this>
     */
    public function recipients(): HasMany
    {
        return $this->hasMany(CommunicationDispatchRecipient::class);
    }

    public function auditLabel(): string
    {
        return ($this->subject ?: $this->channel).' an '.$this->recipient_count.' Empfänger';
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'dispatched_at' => 'datetime',
        ];
    }
}
