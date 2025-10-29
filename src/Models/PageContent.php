<?php

namespace Carone\Content\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;

class PageContent extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'page_contents';

    protected $fillable = [
        'name',
        'display_name',
        'value',
        'type',
        'locale',
        'version',
    ];

    protected $casts = [
        'value' => 'array',
        'version' => 'integer',
    ];

    protected $dates = [
        'deleted_at',
    ];

    /**
     * Scope to filter by type
     */
    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    /**
     * Scope to filter by locale
     */
    public function scopeOfLocale(Builder $query, string $locale): Builder
    {
        return $query->where('locale', $locale);
    }

    /**
     * Find by name or ID
     */
    public static function findByNameOrId($identifier): ?self
    {
        if (is_numeric($identifier)) {
            return static::find($identifier);
        }

        return static::where('name', $identifier)->first();
    }

    /**
     * Get a specific block from the page content
     */
    public function getBlock(string $blockId): ?array
    {
        if (!$this->value || !isset($this->value['blocks'])) {
            return null;
        }

        foreach ($this->value['blocks'] as $block) {
            if (isset($block['id']) && $block['id'] === $blockId) {
                return $block;
            }
        }

        return null;
    }

    /**
     * Get a specific value from a block using dot notation
     */
    public function getBlockValue(string $blockId, ?string $key = null): mixed
    {
        $block = $this->getBlock($blockId);
        
        if (!$block) {
            return null;
        }

        if ($key === null) {
            return $block['data'] ?? null;
        }

        return data_get($block['data'], $key);
    }

    /**
     * Increment version for optimistic concurrency control
     */
    public function incrementVersion(): void
    {
        $this->increment('version');
    }

    /**
     * Check if the version matches for optimistic concurrency
     */
    public function hasCorrectVersion(int $expectedVersion): bool
    {
        return $this->version === $expectedVersion;
    }
}