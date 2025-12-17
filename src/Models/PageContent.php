<?php

namespace Carone\Content\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PageContent extends Model
{
    use HasFactory;

    protected $fillable = ['page_id', 'element_id', 'type', 'value'];

    /**
     * Get the table associated with the model.
     */
    public function getTable(): string
    {
        return config('content.table_name', 'page_contents');
    }
}