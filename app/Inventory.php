<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Inventory extends Model
{
  use SoftDeletes;
    //
    public function product()
    {
        return $this->hasOne('App\Product', 'inventory_id', 'id');
    }
}
