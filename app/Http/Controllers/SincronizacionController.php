<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Sincronizacion;
use DB;

class SincronizacionController extends Controller
{
    //
	public function ultimo($id)
	{
		$sincronizacion = Sincronizacion::where('piso_venta_id', $id)->orderBy('id', 'desc')->first();

		return response()->json($sincronizacion);
	}

  public function store(Request $request)
    {
			//return response()->json('fuck');
			try {
				DB::beginTransaction();
				$sincronizacion = new Sincronizacion();
				$sincronizacion->piso_venta_id = $request->id;
				$sincronizacion->save();

				DB::commit();

				return response()->json(true);

			} catch (Exception $e) {
				DB::rollback();
				return response()->json($e);
			}


    	//return response()->json($sincronizacion);
    }
}
