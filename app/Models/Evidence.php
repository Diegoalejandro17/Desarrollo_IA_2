<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Evidence extends Model
{
    use SoftDeletes;

    protected $table = 'evidence';

    protected $fillable = [
        'uuid',
        'legal_case_id',
        'title',
        'description',
        'type',
        'file_path',
        'file_url',
        'mime_type',
        'file_size',
        'analysis_result',
        'is_analyzed',
        'analyzed_at',
        'metadata',
    ];

    protected $casts = [
        'analysis_result' => 'array',
        'metadata' => 'array',
        'is_analyzed' => 'boolean',
        'analyzed_at' => 'datetime',
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
    public function legalCase(): BelongsTo
    {
        return $this->belongsTo(LegalCase::class, 'legal_case_id');
    }

    // Scopes
    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeAnalyzed($query)
    {
        return $query->where('is_analyzed', true);
    }

    public function scopePendingAnalysis($query)
    {
        return $query->where('is_analyzed', false);
    }

    public function scopeVisual($query)
    {
        return $query->whereIn('type', ['image', 'video']);
    }

    // Métodos auxiliares
    public function isVisual(): bool
    {
        return in_array($this->type, ['image', 'video']);
    }

    public function isDocument(): bool
    {
        return $this->type === 'document';
    }

    public function needsAnalysis(): bool
    {
        return !$this->is_analyzed && $this->isVisual();
    }

    public function markAsAnalyzed(array $result): void
    {
        $this->update([
            'is_analyzed' => true,
            'analyzed_at' => now(),
            'analysis_result' => $result,
        ]);
    }

    public function getFileSizeHumanAttribute(): string
    {
        if (!$this->file_size) {
            return 'N/A';
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $size = $this->file_size;
        $unit = 0;

        while ($size >= 1024 && $unit < count($units) - 1) {
            $size /= 1024;
            $unit++;
        }

        return round($size, 2) . ' ' . $units[$unit];
    }
}
