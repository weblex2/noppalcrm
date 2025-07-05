<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;


trait CompanyRelations
{

    public function houses(): HasMany
    {
        return $this->hasMany(\App\Models\House::class, 'company_id');
    }


	##
}
