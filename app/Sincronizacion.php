<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Sincronizacion extends Model
{
    //
    protected $fillable = [
      'piso_venta_id'
    ];


    public function piso_venta()
    {
    	return $this->belongsTo('App\Piso_venta');
    }
}
