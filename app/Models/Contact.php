<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\ContactRelations;


class Contact extends Model
{

    use ContactRelations;
    protected $guarded = ['id'];


}
