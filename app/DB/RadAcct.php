<?php

namespace App\DB;

use Illuminate\Database\Eloquent\Model;

class RadAcct extends Model
{
  public $timestamps = false;
  protected $table = 'radacct';
  protected $primaryKey = 'radacctid';
}
