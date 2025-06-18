<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Relations\BelongsTo;


trait ContactRelations
{
    public function company(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Company::class, 'company_id');
    }

    ##
}
