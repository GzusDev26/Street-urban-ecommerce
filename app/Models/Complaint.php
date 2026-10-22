<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Complaint extends Model
{
    protected $fillable = [
        'complaint_number',
        'claimant_name',
        'claimant_address',
        'claimant_national_id',
        'claimant_email',
        'claimant_phone',
        'purchased_item',
        'claimed_amount',
        'claim_type',
        'claim_details',
        'claim_request',
        'privacy_accepted_at',
        'status',
        'submitted_at',
        'response_due_at',
    ];

    protected function casts(): array
    {
        return [
            'claimed_amount' => 'decimal:2',
            'privacy_accepted_at' => 'datetime',
            'submitted_at' => 'datetime',
            'response_due_at' => 'datetime',
        ];
    }
}
