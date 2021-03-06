<?php

namespace Fgilio\Tags;

use Spatie\EloquentSortable\Sortable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Spatie\EloquentSortable\SortableTrait;
use Illuminate\Database\Eloquent\Collection as DbCollection;

class Tag extends Model implements Sortable
{
    use SortableTrait, HasSlug;

    public $guarded = [];

    public function scopeWithType(Builder $query, string $type = null): Builder
    {
        if (is_null($type)) {
            return $query;
        }

        return $query->where('type', $type)->ordered();
    }

    public function scopeContaining(Builder $query, string $name, $locale = null): Builder
    {
        $locale = $locale ?? app()->getLocale();

        $locale = '"'.$locale.'"';

        return $query->whereRaw("LOWER(JSON_EXTRACT(name, '$.".$locale."')) like ?", ['"%'.mb_strtolower($name).'%"']);
    }

    /**
     * @param array|\ArrayAccess $values
     * @param string|null        $type
     *
     * @return \Fgilio\Tags\Tag|static
     */
    public static function findOrCreate($values, string $type = null)
    {
        $tags = collect($values)->map(function ($value) use ($type) {
            if ($value instanceof Tag) {
                return $value;
            }

            return static::findOrCreateFromString($value, $type);
        });

        return is_string($values) ? $tags->first() : $tags;
    }

    public static function getWithType(string $type): DbCollection
    {
        return static::withType($type)->ordered()->get();
    }

    public static function findFromString(string $name, string $type = null)
    {
        return static::query()
            ->where('slug', static::generateSlug($name))
            ->where('type', $type)
            ->first();
    }

    public static function findFromStringOfAnyType(string $name)
    {
        return static::query()
            ->where('name', $name)
            ->first();
    }

    public static function findFromLikeString(string $name, string $type = null)
    {
        $slug = static::generateSlug($name);

        return static::query()
            ->where('slug', 'like', "%{$slug}%")
            ->tap(function ($query) use ($type) {
                if (is_null($type)) return $query;

                return $query->where('type', $type);
            })
            ->get();
    }

    protected static function findOrCreateFromString(string $name, string $type = null): self
    {
        $tag = static::findFromString($name, $type);

        if (! $tag) {
            $tag = static::create([
                'name' => $name,
                'type' => $type,
            ]);
        }

        return $tag;
    }
}
