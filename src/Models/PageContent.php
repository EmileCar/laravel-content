<?php

namespace Carone\Content\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PageContent extends Model
{
    use HasFactory;

    protected $fillable = ['page_id', 'element_id', 'locale', 'type', 'value'];

    /**
     * Get the table associated with the model.
     */
    public function getTable(): string
    {
        return config('content.table_name', 'page_contents');
    }

    /**
     * Get the default locale from config
     */
    public static function getDefaultLocale(): string
    {
        return config('content.locale.default', config('app.locale', 'en'));
    }

    /**
     * Scope to filter by locale
     */
    public function scopeForLocale($query, ?string $locale = null)
    {
        $locale = $locale ?? self::getDefaultLocale();
        return $query->where('locale', $locale);
    }

    /**
     * Scope to get content with fallback to default locale
     */
    public function scopeWithFallback($query, string $page_id, string $element_id, ?string $locale = null)
    {
        $locale = $locale ?? self::getDefaultLocale();
        $defaultLocale = self::getDefaultLocale();

        // First try to get content in requested locale
        $content = $query->where('page_id', $page_id)
            ->where('element_id', $element_id)
            ->where('locale', $locale)
            ->first();

        // If not found and locale is different from default, try default locale
        if (!$content && $locale !== $defaultLocale) {
            $content = static::where('page_id', $page_id)
                ->where('element_id', $element_id)
                ->where('locale', $defaultLocale)
                ->first();
        }

        return $content;
    }
}
