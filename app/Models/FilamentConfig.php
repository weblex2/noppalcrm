<?php
## DON'T TOUCH!
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FilamentConfig extends Model
{
    protected $guarded = ['id'];

    public static function getFiltersFor(string $resource): array
    {
        return self::query()
        ->where('type', 'filter')
        ->where('resource', $resource)
        ->get()
        ->groupBy('field') // Gruppiert nach 'status', 'region' etc.
        ->map(fn($group) => $group->pluck('value', 'key')->toArray()) // ['key' => 'value']
        ->toArray();
    }
}
