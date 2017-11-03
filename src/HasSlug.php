<?php

namespace Spatie\Tags;

use Illuminate\Database\Eloquent\Model;

trait HasSlug
{
    public static function bootHasSlug()
    {
        static::saving(function (Model $model) {
            $model->slug = static::generateSlug($model->name);
        });
    }

    protected static function generateSlug($name): string
    {
        $slugger = config('tags.slugger');

        $slugger = $slugger ?: '\Illuminate\Support\Str::slug';

        return call_user_func($slugger, $name);
    }
}
