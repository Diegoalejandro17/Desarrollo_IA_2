<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class CaseAnalysis extends Model
{
    use SoftDeletes;

    protected $table = 'cases_analysis';

    protected $fillable = [
        'uuid',
        'legal_case_id',
        'status',
        'coordinator_result',
        'jurisprudence_result',
        'visual_analysis_result',
        'arguments_result',
        'legal_elements',
        'relevant_precedents',
        'defense_lines',
        'alternative_scenarios',
        'confidence_scores',
        'processing_time',
        'agent_execution_log',
        'executive_summary',
        'version',
        'previous_analysis_id',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'coordinator_result' => 'array',
        'jurisprudence_result' => 'array',
        'visual_analysis_result' => 'array',
        'arguments_result' => 'array',
        'legal_elements' => 'array',
        'relevant_precedents' => 'array',
        'defense_lines' => 'array',
        'alternative_scenarios' => 'array',
        'confidence_scores' => 'array',
        'agent_execution_log' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
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
            if (empty($model->started_at)) {
                $model->started_at = now();
            }
        });
    }

    // Relaciones
    public function legalCase(): BelongsTo
    {
        return $this->belongsTo(LegalCase::class, 'legal_case_id');
    }

    public function previousAnalysis(): BelongsTo
    {
        return $this->belongsTo(CaseAnalysis::class, 'previous_analysis_id');
    }

    // Scopes
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeProcessing($query)
    {
        return $query->where('status', 'processing');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeLatestVersion($query)
    {
        return $query->orderBy('version', 'desc');
    }

    // Métodos auxiliares
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isProcessing(): bool
    {
        return $this->status === 'processing';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function markAsProcessing(): void
    {
        $this->update([
            'status' => 'processing',
            'started_at' => now(),
        ]);
    }

    public function markAsCompleted(): void
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
            'processing_time' => now()->diffInSeconds($this->started_at),
        ]);
    }

    public function markAsFailed(string $reason = null): void
    {
        $log = $this->agent_execution_log ?? [];
        $log[] = [
            'timestamp' => now()->toIso8601String(),
            'event' => 'analysis_failed',
            'reason' => $reason,
        ];

        $this->update([
            'status' => 'failed',
            'completed_at' => now(),
            'agent_execution_log' => $log,
        ]);
    }

    public function addAgentLog(string $agent, string $event, array $data = []): void
    {
        $log = $this->agent_execution_log ?? [];
        $log[] = [
            'timestamp' => now()->toIso8601String(),
            'agent' => $agent,
            'event' => $event,
            'data' => $data,
        ];

        $this->update(['agent_execution_log' => $log]);
    }

    public function getProcessingTimeHumanAttribute(): string
    {
        if (!$this->processing_time) {
            return 'N/A';
        }

        $seconds = $this->processing_time;
        $minutes = floor($seconds / 60);
        $remainingSeconds = $seconds % 60;

        if ($minutes > 0) {
            return "{$minutes}m {$remainingSeconds}s";
        }

        return "{$seconds}s";
    }

    public function getAverageConfidenceScore(): ?float
    {
        if (!$this->confidence_scores || empty($this->confidence_scores)) {
            return null;
        }

        $scores = array_values($this->confidence_scores);
        return round(array_sum($scores) / count($scores), 2);
    }

    public function hasHighConfidence(): bool
    {
        $avgScore = $this->getAverageConfidenceScore();
        return $avgScore !== null && $avgScore >= 0.7;
    }
}
