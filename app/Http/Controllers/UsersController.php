<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Piso_venta;
use App\Sincronizacion;
use App\Vaciar_caja;
use App\Dolar;
use DB;

class UsersController extends Controller
{
    //
    public function get_id()//ID PISO DE VENTA
    {

    	$usuario = Auth::user()->piso_venta->id;

    	return response()->json($usuario);
    }

    public function get_piso_venta()
    {
      try {
        DB::beginTransaction();
        $usuario = Auth::user()->piso_venta->id;

        try {
          DB::beginTransaction();
          $piso_venta = Piso_venta::where('user_id', $usuario)->first();

          DB::commit();
        } catch (Exception $e) {
          DB::rollback();
          return response()->json($e);
        }

        try {
          DB::beginTransaction();
          $sincronizacion = Sincronizacion::where('piso_venta_id', $usuario)->orderBy('id', 'desc')->first();

          DB::commit();
        } catch (Exception $e) {
          DB::rollback();
          return response()->json($e);
        }

        try {
          DB::beginTransaction();
          $caja = Vaciar_caja::where('piso_venta_id', $usuario)->orderBy('id', 'desc')->first();

          DB::commit();
        } catch (Exception $e) {
          DB::rollback();
          return response()->json($e);
        }

        DB::commit();

        return response()->json(['piso_venta' => $piso_venta, 'sincronizacion' => $sincronizacion, 'caja' => $caja]);

      } catch (Exception $e) {
        DB::rollback();
        return response()->json($e);
      }

    }

    public function get_dolar()
    {
      $dolar = Dolar::orderby('id','DESC')->first();
      $datadolar = $dolar['price'];
      $datadolaro = $dolar['priceo'];
      return response()->json(['dolar' => $datadolar, 'dolaro' => $datadolaro]);
    }

    public function vaciar_caja()
    {
        try{

            DB::beginTransaction();

            $usuario = Auth::user()->piso_venta->id;

            $piso_venta = Piso_venta::findOrFail($usuario);

            $vaciar_caja = new Vaciar_caja();
            $vaciar_caja->piso_venta_id = $usuario;
            $vaciar_caja->monto = $piso_venta->dinero;
            $vaciar_caja->ganancia = $piso_venta->gain;
            $vaciar_caja->save();
            $vaciar_caja->id_extra = $vaciar_caja->id;
            $vaciar_caja->save();

            $piso_venta->dinero = 0;
            $piso_venta->gain = 0;
            $piso_venta->save();

            DB::commit();

        }catch(Exception $e){

            DB::rollback();
            return response()->json($e);
        }

        $caja = Vaciar_caja::where('piso_venta_id', $usuario)->orderBy('id', 'desc')->first();

        return response()->json(['piso_venta' => $piso_venta, 'caja' => $caja]);
    }

}
