<?php

namespace App\Models;

use App\Traits\CompanyRelations;


use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    use CompanyRelations;
    protected $guarded = ['id'];
    //
}
