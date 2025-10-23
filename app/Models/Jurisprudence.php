<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Jurisprudence extends Model
{
    use SoftDeletes;

    protected $table = 'jurisprudence';

    protected $fillable = [
        'uuid',
        'case_number',
        'court',
        'jurisdiction',
        'decision_date',
        'case_title',
        'summary',
        'ruling',
        'legal_reasoning',
        'keywords',
        'articles_cited',
        'url',
        'full_text',
        'embedding',
        'relevance_level',
        'metadata',
    ];

    protected $casts = [
        'keywords' => 'array',
        'articles_cited' => 'array',
        'embedding' => 'array',
        'metadata' => 'array',
        'decision_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected $hidden = [
        'deleted_at',
        'embedding', // No exponer el vector de embeddings en las respuestas por defecto
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

    // Scopes
    public function scopeByCourt($query, $court)
    {
        return $query->where('court', 'like', '%' . $court . '%');
    }

    public function scopeByJurisdiction($query, $jurisdiction)
    {
        return $query->where('jurisdiction', $jurisdiction);
    }

    public function scopeRecent($query, $years = 5)
    {
        return $query->where('decision_date', '>=', now()->subYears($years));
    }

    public function scopeHighRelevance($query)
    {
        return $query->where('relevance_level', 'high');
    }

    public function scopeSearchByKeyword($query, $keyword)
    {
        return $query->whereJsonContains('keywords', $keyword);
    }

    public function scopeFullTextSearch($query, $searchTerm)
    {
        return $query->whereRaw(
            "MATCH(case_title, summary, legal_reasoning) AGAINST(? IN NATURAL LANGUAGE MODE)",
            [$searchTerm]
        );
    }

    // Métodos auxiliares
    public function hasEmbedding(): bool
    {
        return !empty($this->embedding);
    }

    public function isHighRelevance(): bool
    {
        return $this->relevance_level === 'high';
    }

    public function hasKeyword(string $keyword): bool
    {
        if (!$this->keywords) {
            return false;
        }

        return in_array(strtolower($keyword), array_map('strtolower', $this->keywords));
    }

    public function getArticlesCitedTextAttribute(): string
    {
        if (!$this->articles_cited || empty($this->articles_cited)) {
            return 'Ninguno';
        }

        return implode(', ', $this->articles_cited);
    }

    public function getKeywordsTextAttribute(): string
    {
        if (!$this->keywords || empty($this->keywords)) {
            return 'Sin palabras clave';
        }

        return implode(', ', $this->keywords);
    }

    /**
     * Calcular similitud coseno con otro vector de embedding
     * (Para búsqueda semántica)
     */
    public function cosineSimilarity(array $queryEmbedding): float
    {
        if (!$this->hasEmbedding() || empty($queryEmbedding)) {
            return 0.0;
        }

        $dotProduct = 0.0;
        $magnitudeA = 0.0;
        $magnitudeB = 0.0;

        $embeddingA = $this->embedding;
        $embeddingB = $queryEmbedding;

        $length = min(count($embeddingA), count($embeddingB));

        for ($i = 0; $i < $length; $i++) {
            $dotProduct += $embeddingA[$i] * $embeddingB[$i];
            $magnitudeA += $embeddingA[$i] ** 2;
            $magnitudeB += $embeddingB[$i] ** 2;
        }

        $magnitudeA = sqrt($magnitudeA);
        $magnitudeB = sqrt($magnitudeB);

        if ($magnitudeA == 0 || $magnitudeB == 0) {
            return 0.0;
        }

        return $dotProduct / ($magnitudeA * $magnitudeB);
    }
}
