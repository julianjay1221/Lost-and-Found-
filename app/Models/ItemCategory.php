<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

#[Fillable(['name', 'name_key'])]
class ItemCategory extends Model
{
    public static function findOrCreateByName(string $name): self
    {
        $name = trim($name);

        return self::firstOrCreate(
            ['name_key' => self::keyFor($name)],
            ['name' => $name]
        );
    }

    public static function keyFor(string $name): string
    {
        return Str::lower(trim($name));
    }
}
