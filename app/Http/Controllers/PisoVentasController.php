<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Piso_venta;
use App\Venta;
use App\Despacho;
use Carbon\Carbon;
use App\Inventario_piso_venta;
use App\Vaciar_caja;
use App\Dolar;
use App\Solicitud;
use Illuminate\Support\Facades\DB;

class PisoVentasController extends Controller
{
    //
    public function index()
    {
    	return view('admin.piso_ventas');
    }

    public function get_piso_ventas(){
    	$piso_ventas = Piso_venta::all();

    	return response()->json($piso_ventas);
    }

    public function solicitudes()
    {
      return view('solicitudes.index');
    }

    public function get_solicitudes(Request $request)
    {
      $usuario = Auth::user()->piso_venta->id;

      if ($request->search != null) {

          $inventario  = Solicitud::with('pisos')->where('piso_venta_id', $usuario)->where('nombre', 'like', '%'.$request->search.'%')->orderBy('nombre', 'desc')->paginate(20);
          return response()->json($inventario);

      } else {

        $inventario  = Solicitud::with('pisos')->where('piso_venta_id', $usuario)->orderBy('nombre', 'desc')->paginate(20);
        return response()->json($inventario);

      }

    }

    public function store_solicitud(Request $request)
    {
      $usuario = Auth::user()->piso_venta->id;

      $solicitud = new Solicitud();

  		$solicitud->nombre = $request->nombre;
  		$solicitud->telefono = $request->telefono;
  		$solicitud->producto = $request->producto;
  		$solicitud->piso_venta_id = $usuario;
  		$solicitud->save();

      return $solicitud;

    }

    public function last_solicitud(Request $request)
    {
      $usuario = Auth::user()->piso_venta->id;

      $id_extra = $request->idExtra;

      $solicitud = Solicitud::where('piso_venta_id', $usuario)->where('id', '>', $id_extra)->get();
      $count = $solicitud->count();
      if ($count != 0) {
        return $solicitud;
      } else {
        return 0;
      }

      //$ventas = Venta::with('detalle', 'detalle.precio')->where('piso_venta_id', $piso_venta)->where('id_extra', '>', $id)->get();

    }

    public function finish_solicitud(Request $request)
    {
      $usuario = Auth::user()->piso_venta->id;


      foreach ($request->data as $key => $value) {
        $id_extra = $value;
        //return $id_extra;

        //$Solicitud = Solicitud::find($id_extra);
        $Solicitud = DB::table('solicitudes')->where('id', $id_extra)->delete();
        //$Solicitud->delete();

      }

      return $Solicitud;

    }

    public function resumen()// funcion carga el resumen de ventas compras despachos de la vista home
    {
        $usuario = Auth::user()->piso_venta->id;
    	$date = Carbon::now();
    	//$mes = $date->month; //--> mes
    	//$mes = $date->weekOfYear; //--> semana

    	$ventas = Venta::where('piso_venta_id', $usuario)->where('type', 1)->whereBetween('created_at', [Carbon::parse('last monday')->startOfDay(), Carbon::parse('next sunday')->endOfDay(),])->count();
    	$compras = Venta::where('piso_venta_id', $usuario)->where('type', 2)->whereBetween('created_at', [Carbon::parse('last monday')->startOfDay(), Carbon::parse('next sunday')->endOfDay(),])->count();
    	$despachos = Despacho::where('piso_venta_id', $usuario)->where('type', 1)->whereBetween('created_at', [Carbon::parse('last monday')->startOfDay(), Carbon::parse('next sunday')->endOfDay(),])->count();
    	$retiros = Despacho::where('piso_venta_id', $usuario)->where('type', 2)->whereBetween('created_at', [Carbon::parse('last monday')->startOfDay(), Carbon::parse('next sunday')->endOfDay(),])->count();

      /*$ventas = Venta::where('piso_venta_id', $usuario)->where('type', 1)->whereMonth('created_at', $mes)->count();
    	$compras = Venta::where('piso_venta_id', $usuario)->where('type', 2)->whereMonth('created_at', $mes)->count();
    	$despachos = Despacho::where('piso_venta_id', $usuario)->where('type', 1)->whereMonth('created_at', $mes)->count();
    	$retiros = Despacho::where('piso_venta_id', $usuario)->where('type', 2)->whereMonth('created_at', $mes)->count();*/

    	return response()->json([
    							'ventas' => $ventas,
    							'compras' => $compras,
    							'despachos' => $despachos,
    							'retiros' => $retiros
    							]);
    }

    public function resumen_dia()// funcion carga el resumen de ventas compras despachos de la vista home
    {
        $usuario = Auth::user()->piso_venta->id;
    	$date = Carbon::now();
    	//$mes = $date->month; --> mes
    	$mes = $date->day; //--> semana

    	$ventas = Venta::where('piso_venta_id', $usuario)->where('type', 1)->whereDay('created_at', $mes)->count();
    	$compras = Venta::where('piso_venta_id', $usuario)->where('type', 2)->whereDay('created_at', $mes)->count();
    	$despachos = Despacho::where('piso_venta_id', $usuario)->where('type', 1)->whereDay('created_at', $mes)->count();
    	$retiros = Despacho::where('piso_venta_id', $usuario)->where('type', 2)->whereDay('created_at', $mes)->count();

    	return response()->json([
    							'ventas' => $ventas,
    							'compras' => $compras,
    							'despachos' => $despachos,
    							'retiros' => $retiros
    							]);
    }

    public function ventas_compras(Request $request)
    {
        $usuario = Auth::user()->piso_venta->id;

    	if ($request->fecha_i != 0 && $request->fecha_f != 0) {

    		$fecha_i = new Carbon($request->fecha_i);
    		$fecha_f = new Carbon($request->fecha_f);

    		$ventas = Venta::with('detalle')->where('piso_venta_id', $usuario)->whereDate('created_at','>=', $fecha_i)->whereDate('created_at','<=', $fecha_f)->orderBy('id', 'desc')->paginate(20);
    	}else{

	    	$date = Carbon::now();
	    	$mes = $date->month;

	    	$ventas = Venta::with('detalle')->where('piso_venta_id', $usuario)->whereMonth('created_at', $mes)->orderBy('id', 'desc')->paginate(20);
	    }

    	return response()->json($ventas);
    }

    public function despachos_retiros($id, Request $request)
    {
    	if ($request->fecha_i != 0 && $request->fecha_f != 0) {

    		$fecha_i = new Carbon($request->fecha_i);
    		$fecha_f = new Carbon($request->fecha_f);

    		$despachos = Despacho::with('productos')->where('piso_venta_id', $id)->whereDate('created_at','>=', $fecha_i)->whereDate('created_at','<=', $fecha_f)->orderBy('id', 'desc')->paginate(20);
    	}else{

	    	$date = Carbon::now();
	    	$mes = $date->month;

	    	$despachos = Despacho::with('productos')->where('piso_venta_id', $id)->whereMonth('created_at', $mes)->orderBy('id', 'desc')->paginate(20);
    	}
    	return response()->json($despachos);
    }

    public function productos_piso_venta($id)
    {

    	$productos = Inventario_piso_venta::with('inventario.precio')->where('piso_venta_id', $id)->orderBy('cantidad', 'desc')->paginate(20);

    	return response()->json($productos);
    }

    public function ultima_vaciada_caja_local($id)//WEB Y LOCAL
    {
        $caja = Vaciar_caja::where('id_extra', '>' ,$id)->get();

        return response()->json($caja);
    }
    public function establecer_dolar(Request $request)
  	{
      $precio = ($request->precio > 0) ? $request->precio : false;
      $precioo = ($request->precioo > 0) ? $request->precioo : false;

      if ($precio != false && $precioo != false) {
        $dolar = new Dolar();
  
        $dolar->price = $request->precio;
        $dolar->priceo = $request->precioo;
        $dolar->save();
        
        return redirect()->back()->with('success', 'Nuevo precio del dolar establecido.');
      } else {
        return redirect()->back()->with('errordolar', 'Por favor ingrese montos validos para el dolar.');
      }

  	}
}
