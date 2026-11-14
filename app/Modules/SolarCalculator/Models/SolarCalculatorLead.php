<?php

namespace App\Modules\SolarCalculator\Models;

use App\Models\CrmContact;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A quotation request. `inputs`, `results`, `quote` and `exchange_rates`
 * are snapshots taken when the request was submitted, so a later formula
 * or rate change never rewrites a quote the customer already received.
 */
class SolarCalculatorLead extends Model
{
    use SoftDeletes;

    public const STATUSES = [
        'new' => 'جدید',
        'contacted' => 'تماس گرفته شد',
        'site_visit' => 'بازدید فنی',
        'won' => 'قرارداد بسته شد',
        'lost' => 'منصرف شد',
    ];

    protected $table = 'solar_calculator_leads';

    protected $fillable = [
        'public_token', 'quote_number', 'user_id', 'crm_contact_id', 'name', 'mobile', 'email',
        'region_key', 'system_type', 'inputs', 'results', 'quote', 'exchange_rates', 'total_price',
        'status', 'customer_note', 'admin_note', 'ip_hash', 'quote_viewed_at',
    ];

    protected $casts = [
        'inputs' => 'array',
        'results' => 'array',
        'quote' => 'array',
        'exchange_rates' => 'array',
        'total_price' => 'float',
        'quote_viewed_at' => 'datetime',
    ];

    protected $hidden = ['public_token', 'ip_hash'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function crmContact(): BelongsTo
    {
        return $this->belongsTo(CrmContact::class, 'crm_contact_id');
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function getRouteKeyName(): string
    {
        return 'id';
    }
}
