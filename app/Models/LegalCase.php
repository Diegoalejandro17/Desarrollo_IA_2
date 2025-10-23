<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class LegalCase extends Model
{
    use SoftDeletes;

    protected $table = 'legal_cases';

    protected $fillable = [
        'uuid',
        'title',
        'description',
        'case_type',
        'status',
        'parties',
        'incident_date',
        'facts',
        'metadata',
        'user_id',
    ];

    protected $casts = [
        'parties' => 'array',
        'metadata' => 'array',
        'incident_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected $hidden = [
        'deleted_at',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    // Relaciones
    public function evidence(): HasMany
    {
        return $this->hasMany(Evidence::class, 'legal_case_id');
    }

    public function analyses(): HasMany
    {
        return $this->hasMany(CaseAnalysis::class, 'legal_case_id');
    }

    public function latestAnalysis()
    {
        return $this->hasOne(CaseAnalysis::class, 'legal_case_id')
            ->where('status', 'completed')
            ->latest('version');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('case_type', $type);
    }

    public function scopeRecent($query, $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    // Métodos auxiliares
    public function isAnalyzed(): bool
    {
        return $this->status === 'analyzed';
    }

    public function isAnalyzing(): bool
    {
        return $this->status === 'analyzing';
    }

    public function canBeAnalyzed(): bool
    {
        return in_array($this->status, ['draft', 'analyzed']);
    }
}
