<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Venta;
use Illuminate\Support\Facades\Auth;
use App\Inventario_piso_venta;
use App\Detalle_venta;
use App\Dolar;
use DB;
use App\Inventory;
use App\Inventario;
use App\Precio;
use App\Sincronizacion;
use App\Piso_venta;
use App\Product;
use Carbon\Carbon;

class VentasController extends Controller
{
    //
    public function index()
    {
    	return view('ventas.index');
    }
	
	public function null()
    {
		$usuario = Auth::user()->piso_venta->id;
		$ventas = Venta::with(['detalle'])->where('piso_venta_id', $usuario)->where('status', 2)->get();
		$piso_venta = Piso_venta::where('user_id', $usuario)->get();
    	return view('ventas.null')->with('ventas', $ventas)->with('piso_venta', $piso_venta);
    }

    public function create()
    {

    	return view('ventas.create');
    }

    public function create_compra()
    {

    	return view('ventas.create_compra');
    }

    public function get_ventas(Request $request)
    {
    	$usuario = Auth::user()->piso_venta->id;

    	if ($request->fecha_i != 0 && $request->fecha_f != 0) {

    		$fecha_i = new Carbon($request->fecha_i);
    		$fecha_f = new Carbon($request->fecha_f);

    		$ventas = Venta::with(['detalle'])->where('piso_venta_id', $usuario)->where('status', 1)->whereDate('created_at','>=', $fecha_i)->whereDate('created_at','<=', $fecha_f)->orderBy('id', 'desc')->paginate(30);
    	}else{

    		$ventas = Venta::with(['detalle'])->where('piso_venta_id', $usuario)->where('status', 1)->orderBy('id', 'desc')->paginate(30);
    	}

        return response()->json($ventas);
    }


    public function get_datos_create()
    {
    	$usuario = Auth::user()->piso_venta->id;

    	$inventario = Inventario_piso_venta::with('inventario.precio')->where('cantidad', '>', 0.000)->where('piso_venta_id', $usuario)->get();

		$dolar = Dolar::orderby('id','DESC')->first();

		setlocale(LC_MONETARY, 'it_IT');

		//return response()->json(['select' => $dolar]);
		
		foreach ($inventario as $key => $value) {
			if ($value['inventario'] != NULL) {
				if (isset($value['inventario']['precio']['total_menor'])) {
					$price = $value['inventario']['precio']['total_menor'] * $dolar->price;
					$price = number_format($price, 2, ',', '.');
					$precio = $value['inventario']['name'].' - '.$price;
					$datos = ['value' => $value['inventario']['id'], 'text' => $precio];
					$inventarioSelect[] = $datos;
				} else {
					return response()->json(['select' => $value]);
				}
			}
		}

    	return response()->json(['select' => $inventarioSelect, 'inventario' => $inventario]);
    }

    public function store(Request $request)
    {
    	$usuario = Auth::user()->piso_venta->id;

    	try{
    		   // return $request;
			DB::beginTransaction();
	    	$venta = new Venta();
	        $venta->piso_venta_id = $usuario;
	        $venta->type = 1; // 1 ES VENTA
          if ($request->venta['divisa'] === true) {
            $venta->pago = 1; // 1 ES DIVISA
          } else {
            $venta->pago = 2; // 2 ES BSS
          }

	        $venta->sub_total = $request->venta['sub_total'];
	        $venta->iva = $request->venta['iva'];
	        $venta->total = $request->venta['total'];

	        $venta->save();

	        $venta->id_extra = $venta->id;
	        $venta->save();

          $ganancia = 0;

	        foreach ($request->productos as $producto) {
				//CALCULO DE GANANCIA
				$idinventario = $producto['id']; //590
				$idinventory = Inventario::where('id', $idinventario)->select('inventory_id', 'name')->first();
				$idinventariosql = $idinventory->inventory_id; //950
				//return response()->json([$idinventario, $idinventariosql]);
				$prueba = Product::where('inventory_id', $idinventariosql)->select('retail_margin_gain')->first();
				if (isset($prueba->retail_margin_gain)) {
					$porcentajeganancia = $prueba->retail_margin_gain;
					$ganancia += ($producto['total']*$porcentajeganancia)/100;
				}
	        	//REGISTRAMOS EL PRODUCTO EN LOS DETALLES DE LA VENTA
	            $detalles = new Detalle_venta();
	            $detalles->venta_id = $venta->id;
	            $detalles->cantidad = $producto['cantidad'];
	            $detalles->inventario_id = $producto['id'];
	            $detalles->sub_total = $producto['sub_total'];
		          $detalles->iva = $producto['iva'];
		          $detalles->total = $producto['total'];
	            $detalles->save();
	            //settype($producto['cantidad'],"integer");

	            //RESTAMOS DEL STOCK
	            $inventario = Inventario_piso_venta::where('piso_venta_id', $usuario)->where('inventario_id', $producto['id'])->orderBy('id', 'desc')->first();

	            $resta = $inventario->cantidad -= $producto['cantidad'];

	            // $data= ['resta' =>$resta, 'cantidad inventario' => $inventario->cantidad, 'cantidad de producto'=> $producto['cantidad']];
	            //VALICACION POR SI NO HAY SUFICIENTES PRODUCTOS
	            if ($resta < 0.000) {

	            	return response()->json(['errors' => 'no hay suficientes productos en el inventario']);
	            	DB::rollback();

	            }

				DB::table('logs')->insert(
                    ['accion' => 'Vender '.$producto['cantidad'].' productos - quedan '.$inventario->cantidad, 'usuario' => auth()->user()->email, 'producto' => $idinventory->name, 'created_at' => Carbon::now() ]
                  );

	            $inventario->save();
	        }

	        //SUMAMOS EN EL DINERO GENERADO EN EL PISO DE VENTA
	        $piso_venta = Piso_venta::where('user_id', $usuario)->first();
          if ($request->venta['divisa'] === true) {
            $venta_total = $request->venta['total']-($request->venta['total']*0.03);
            $ganancia_total = $ganancia-($ganancia*0.03);
            $piso_venta->dinero += $venta_total;
            $piso_venta->gain += $ganancia_total;
			$venta->gain = $ganancia_total;
	        $venta->save();
          } else {
            $piso_venta->dinero += $venta->total;
            $piso_venta->gain += $ganancia;
			$venta->gain = $ganancia;
	        $venta->save();
          }
	        $piso_venta->save();

	        DB::commit();

	        $venta = Venta::with('detalle')->findOrFail($venta->id);

          //$dataganancia['ganancia'] = $ganancia;

	        return response()->json($venta);

		}catch(Exception $e){

			DB::rollback();
			return response()->json($e);
		}
    }

    public function store_compra(Request $request)
    {
    	$usuario = Auth::user()->piso_venta->id;
    	try{

			DB::beginTransaction();
	    	$venta = new Venta();
	        $venta->piso_venta_id = $usuario;
	        $venta->type = 2; // 1 ES VENTA 2 ES COMPRA
	        $venta->sub_total = 0;
	        $venta->iva = 0;
	        $venta->total = 0;

	        $venta->save();

	        $venta->id_extra = $venta->id;
	        $venta->save();

	        foreach ($request->productos as $producto) {

	        	if ($producto['id'] == 0) {

		        	//REGISTRAMOS EL PRODUCTO
		        	 $articulo = new Inventario();
	                $articulo->name = $producto['nombre'];
	                //$articulo->unit_type_mayor = $producto['unit_type'];
	                $articulo->unit_type_menor = $producto['unidad'];
	                //$articulo->inventory_id = $producto['pivot']['inventory_id'];
	                $articulo->status = 1;
	                $articulo->piso_venta_id = $usuario;
	                $articulo->save();

	                $articulo->id_extra = $articulo->id;
	                $articulo->save();
	                //REGISTRAMOS LOS PRECIOS
	                $precio = new Precio();
	                $precio->costo = 0;
	                $precio->iva_porc = 0;
	                $precio->iva_menor = 0;
	                $precio->sub_total_menor = 0;
	                $precio->total_menor = 0;
	                $precio->inventario_id = $articulo->id;
	                $precio->save();
                }else{

                	$articulo = Inventario::findOrFail($producto['id']);
                }

	        	//REGISTRAMOS EL PRODUCTO EN LOS DETALLES DE LA VENTA
	            $detalles = new Detalle_venta();
	            $detalles->venta_id = $venta->id;
	            $detalles->cantidad = $producto['cantidad'];
	            $detalles->inventario_id = $articulo->id;
	            $detalles->sub_total = 0;
		        $detalles->iva = 0;
		        $detalles->total = 0;
	            $detalles->save();

	            //SUMAMOS EL STOCK
	            $inventario = Inventario_piso_venta::where('inventario_id', $articulo->id)->where('piso_venta_id', $usuario)->orderBy('id', 'desc')->first();

	            if ($inventario['id'] != null) {
	            	$inventario->cantidad += $producto['cantidad'];
	            	$inventario->save();
	            }else{

		            $inventario = new Inventario_piso_venta();
		            $inventario->inventario_id = $articulo->id;
		            $inventario->piso_venta_id = $usuario;
		            $inventario->cantidad = $producto['cantidad'];
		            $inventario->save();
	        	}
	            //CODIGO VIEJO
	            /*
	            DB::beginTransaction();
		    	$venta = new Venta();
		        $venta->piso_venta_id = $usuario;
		        $venta->type = 2; // 1 ES VENTA 2 ES COMPRA
		        $venta->sub_total = $request->venta['sub_total'];
		        $venta->iva = $request->venta['iva'];
		        $venta->total = $request->venta['total'];

		        $venta->save();

		        $venta->id_extra = $venta->id;
		        $venta->save();

		        foreach ($request->productos as $producto) {
		        	//REGISTRAMOS EL PRODUCTO
		        	 $articulo = new Inventario();
	                $articulo->name = $producto['nombre'];
	                //$articulo->unit_type_mayor = $producto['unit_type'];
	                $articulo->unit_type_menor = $producto['unidad'];
	                //$articulo->inventory_id = $producto['pivot']['inventory_id'];
	                $articulo->status = 1;
	                $articulo->piso_venta_id = $usuario;
	                $articulo->save();

	                $articulo->id_extra = $articulo->id;
	                $articulo->save();
	                //REGISTRAMOS LOS PRECIOS
	                $precio = new Precio();
	                $precio->costo = $producto['costo'];
	                $precio->iva_porc = $producto['iva_porc'];
	                $precio->iva_menor = $producto['iva_unitario'];
	                $precio->sub_total_menor = $producto['sub_total_unitario'];
	                $precio->total_menor = $producto['total_unitario'];
	                $precio->inventario_id = $articulo->id;
	                $precio->save();


		        	//REGISTRAMOS EL PRODUCTO EN LOS DETALLES DE LA VENTA
		            $detalles = new Detalle_venta();
		            $detalles->venta_id = $venta->id;
		            $detalles->cantidad = $producto['cantidad'];
		            $detalles->inventario_id = $articulo->id;
		            $detalles->sub_total = $producto['sub_total'];
			        $detalles->iva = $producto['iva'];
			        $detalles->total = $producto['total'];
		            $detalles->save();

		            //SUMAMOS EL STOCK
		            $inventario = new Inventario_piso_venta();
		            $inventario->inventario_id = $articulo->id;
		            $inventario->piso_venta_id = $usuario;
		            $inventario->cantidad = $producto['cantidad'];
		            $inventario->save();
		        */
	        }

	        DB::commit();

	        $venta = Venta::with('detalle')->findOrFail($venta->id);

	        return response()->json($venta);

		}catch(Exception $e){

			DB::rollback();
			return response()->json($e);
		}
    }

    public function negar_venta(Request $request)
    {
    	try{

			DB::beginTransaction();

	    	$usuario = Auth::user()->piso_venta->id;

	    	$venta = Venta::with('detalle')->findOrFail($request->id);
			$sincronizacion = Sincronizacion::where('piso_venta_id', $usuario)->orderBy('id', 'desc')->first();

			if ($sincronizacion->updated_at > $venta->created_at) {
				return response()->json(false);
			}

	    	$venta->status = 2;
	    	$venta->save();

	    	$piso_venta = Piso_venta::where('user_id', $usuario)->orderBy('id', 'desc')->first();
	    	//RESTAMOS O SUMAMOS DEL DINERO QUE POSEE EL PISO DE VENTA
	    	if ($venta->type == 1) {
				
				$piso_venta->dinero -= $venta->total;
				$piso_venta->gain -= $venta->gain;
				$piso_venta->save();
				
	    	}

	    	foreach($venta->detalle as $producto){

	    		$inventario = Inventario_piso_venta::where('piso_venta_id', $usuario)->where('inventario_id', $producto->id)->orderBy('id', 'desc')->first();

		    	if ($venta->type == 1) {

		    		$inventario->cantidad += $producto->pivot->cantidad;

		    	}

		    	$inventario->save();

		    }

		    DB::commit();
			
	    	return response()->json($venta);

	    }catch(Exception $e){

			DB::rollback();
			return response()->json($e);
		}
    }

    //APARTIR DE AQUI ES EL REFRESCAR
    public function get_piso_venta_id()
    {
    	$usuario = Auth::user()->piso_venta->id;

    	return response()->json($usuario);
    }

    public function ultima_venta($piso_venta)//DEL LADO DE LA WEB
    {
    	//OBTENEMOS LA ULTIMA VENTA QUE TIENE LA WEB Y LE MANDAMOS EL ID_EXTRA
    	$venta = Venta::select('id_extra')->where('piso_venta_id', $piso_venta)->orderBy('id', 'desc')->first();

    	return response()->json($venta);
    }

    public function ventas_sin_registrar($piso_venta, $id)
    {
      try {

        DB::beginTransaction();

        //$ventas = Venta::with('detalle', 'detalle.precio')->where('piso_venta_id', $piso_venta)->where('id_extra', '>', $id)->get();        
        $ventas = Venta::with('detalle', 'detalle.precio')->where('piso_venta_id', $piso_venta)->where('created_at', '>', $id)->where('status', 1)->limit(1555)->get();        
        //$ventas = Venta::with('detalle', 'detalle.precio')->where('piso_venta_id', $piso_venta)->where('created_at', '>', '2021-10-05 16:08:12')->where('created_at', '<', '2021-10-08 12:30:12')->where('status', 1)->limit(1555)->get();        

        DB::commit();

        return response()->json($ventas);

      } catch (Exception $e) {

        DB::rollback();
  			return response()->json($e);

      }

    }

    public function registrar_ventas(Request $request) //WEB
    {

    	try{

			DB::beginTransaction();
			foreach ($request->ventas as $valor) {

		    	$venta = new Venta();
		        $venta->piso_venta_id = $request->piso_venta_id;
		        $venta->type = $valor['type'];
		        $venta->sub_total = $valor['sub_total'];
		        $venta->iva = $valor['iva'];
		        $venta->total = $valor['total'];
		        $venta->id_extra = $valor['id_extra'];
		        $venta->save();

		        foreach ($valor['detalle'] as $producto) {
		        	//REGISTRAMOS EL PRODUCTO EN LOS DETALLES DE LA VENTA
		            $detalles = new Detalle_venta();
		            $detalles->venta_id = $venta->id;
		            $detalles->cantidad = $producto['pivot']['cantidad'];
		            //BUSCAMOS EL ID QUE TIENE INVENTARIO EN LA WEB CON EL PRODUCTO
		            if ($producto['inventory_id'] !== null) {
		           		$articulo = Inventario::select('id')->where('inventory_id', $producto['inventory_id'])->orderBy('id', 'desc')->first();
		            	$detalles->inventario_id = $articulo->id;
		            }else{

		            	$articulo = new Inventario();
                        $articulo->name = $producto['name'];
                        $articulo->unit_type_mayor = $producto['unit_type_mayor'];
                        $articulo->unit_type_menor = $producto['unit_type_menor'];
                        $articulo->piso_venta_id = $request->piso_venta_id;
                        $articulo->id_extra = $producto['id_extra'];
                        $articulo->save();
                        $detalles->inventario_id = $articulo->id;
		            }

		            $detalles->sub_total = $producto['pivot']['sub_total'];
			        $detalles->iva = $producto['pivot']['iva'];
			        $detalles->total = $producto['pivot']['total'];
		            $detalles->save();

		            //RESTAMOS DEL STOCK
		            $inventario = Inventario_piso_venta::where('piso_venta_id', $request->piso_venta_id)->where('inventario_id', $articulo->id)->whereHas('inventario', function($q){
		            	//$q->where('inventory_id', $producto['inventory_id']);
		            })->orderBy('id', 'desc')->first();
		            //SI NO ENCUENTRA EL PRODUCTO QUE LO REGISTRE

		            if ($inventario->id == null) {
                        //REGISTRA LA CANTIDAD EN EL INVENTARIO DEL PISO DE VENTA
                        $inventario = new Inventario_piso_venta();
                        $inventario->inventario_id = $articulo->id;
                        $inventario->piso_venta_id = $request->piso_venta_id;
                        $inventario->cantidad = $producto['pivot']['cantidad'];
                        $inventario->save();
                    }else{

                    //SI ES UNA VENTA O UNA COMPRA
	                    if ($venta->type == 1) {

	                    	$inventario->cantidad -= $producto['pivot']['cantidad'];
	                    }else if ($venta->type == 2){
	                    	$inventario->cantidad += $producto['pivot']['cantidad'];
	                    }
		            }
		            //
		            //VALICACION POR SI NO HAY SUFICIENTES PRODUCTOS
		            /*
		            if ($resta < 0) {

		            	return response()->json(['errors' => 'no hay suficientes productos en el inventario']);
		            	DB::rollback();

		            }
		            */

		            $inventario->save();
		        }
			}

	        DB::commit();

	        return response()->json(true);

		}catch(Exception $e){

			DB::rollback();
			return response()->json($e);
		}
    }

    public function get_ventas_anuladas()
    {
    	$usuario = Auth::user()->piso_venta->id;

    	$ventas = Venta::where('anulado', 0)->where('piso_venta_id', $usuario)->get();

    	return response()->json($ventas);
    }

    public function actualizar_anulados(Request $request)//WEB
    {
    	foreach ($request->ventas as $venta) {

    		$web = Venta::where('id_extra', $venta['id_extra'])->where('piso_venta_id', $request->piso_venta)->orderBy('id', 'desc')->first();
    		$web->anulado = 1;
    		$web->save();

    		foreach($web->detalle as $producto){

	    		$inventario = Inventario_piso_venta::where('piso_venta_id', $request->piso_venta)->where('inventario_id', $producto->id)->orderBy('id', 'desc')->first();

		    	if ($web->type == 1) {

		    	$inventario->cantidad += $producto->pivot->cantidad;

		    	}else if($web->type == 2){

		    	$inventario->cantidad -= $producto->pivot->cantidad;

		    	}

		    	$inventario->save();

		    }
    	}

    	return response()->json(true);
    }

    public function actualizar_anulados_local()
    {
    	$usuario = Auth::user()->piso_venta->id;

    	$ventas = Venta::with('detalle')->where('anulado', 0)->where('piso_venta_id', $usuario)->get();

    	foreach ($ventas as $venta) {

    		$venta->anulado = 1;
    		$venta->save();
    	}

    	return response()->json(true);
    }
}
