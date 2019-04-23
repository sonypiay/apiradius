<?php

namespace App\DB;

use Illuminate\Database\Eloquent\Model;

class Radcheck extends Model
{
    public $timestamps = false;
	protected $table = 'radcheck';
	public $incrementing = true;
	protected $primaryKey = 'id';
}
