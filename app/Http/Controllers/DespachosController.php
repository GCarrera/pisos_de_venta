<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Piso_venta;
use App\Despacho;
use Illuminate\Support\Facades\Auth;
use App\Inventario;
use App\Inventario_piso_venta;
use App\Inventory;
use App\Despacho_detalle;
use DB;
use App\Precio;
//use App\Inventory;

class DespachosController extends Controller
{
    //
    public function index()
    {

    	return view('despachos.index');
    }

    public function index_retiros()
    {

    	return view('retiros.index');
    }

    public function get_despachos()
    {
        $usuario = Auth::user()->piso_venta->id;

        $despachos = Despacho::with(['productos' => function($producto){
            $producto->select('product_name');
        }])->where('piso_venta_id', $usuario)->orderBy('id', 'desc')->paginate(20);

        return response()->json($despachos);
    }

    public function get_retiros()
    {
        $usuario = Auth::user()->piso_venta->id;

        $despachos = Despacho::with('productos')->where('piso_venta_id', $usuario)->where('type', 2)->orderBy('id', 'desc')->paginate(20);

        return response()->json($despachos);
    }

    public function get_retiros_web(Request $request)
    {
        $usuario = Auth::user()->piso_venta->id;
        $id = $request->id['id_extra'];

        try {
            DB::beginTransaction();
            $despachos = Despacho::with('productos')->where('piso_venta_id', $usuario)->where('type', 2)->where('id', '>', $id)->get();
    
            return response()->json($despachos);
            DB::commit();
        } catch (\Exception $e) {
            return response()->json($e);
        }

    }

    public function confirmar_despacho(Request $request)
    {
        try {
            DB::beginTransaction();

            $usuario = Auth::user()->piso_venta->id;

            $despacho = Despacho::with(['productos' => function($producto){
            }])->findOrFail($request->id);
            $despacho->confirmado = 1;
            $despacho->save();

            foreach ($despacho->productos as $valor) {
                //RESTAMOS DE INVENTORY DE PROMETHEUS
                $inventario = Inventory::findOrFail($valor->id);
                $inventario->total_qty_prod -= $valor->pivot->cantidad;
                $inventario->quantity = $inventario->total_qty_prod / $inventario->qty_per_unit;
                $inventario->save();
                //BUSCAMOS EL ID EN INVENTARIO
                $producto = Inventario::select('id')->where('inventory_id', $valor->pivot->inventory_id)->orderBy('id', 'desc')->first();

                if (isset($producto->id)) {
                    $inventario = Inventario_piso_venta::with('inventario')->where('piso_venta_id', $usuario)->where('inventario_id', $producto->id)->orderBy('id', 'desc')->first();

                    if ($inventario['id'] == null) {
                        $inventario = new Inventario_piso_venta();
                        $inventario['inventario_id'] = $producto->id;
                        $inventario['piso_venta_id'] = $usuario;
                        $inventario['cantidad'] = $valor->pivot->cantidad;
                        $inventario->save();
                    }else{
                        //SI ES UN DESPACHO O ES UN RETIRO
                        if($despacho->type == 1){


                                $inventario->cantidad += $valor->pivot->cantidad;

                        }else{

                                $inventario->cantidad -= $valor->pivot->cantidad;

                        }
                    }
                    $inventario->save();
                } else {
                    
                    $inventarioval = Inventory::where('id', $valor->pivot->inventory_id)->first();

                    if (isset($inventarioval->id)) {
                        $articulo = new Inventario();
                        $articulo->name = $inventarioval->name;
                        $articulo->unit_type_mayor = $inventarioval->unit_type_mayor;
                        $articulo->unit_type_menor = $inventarioval->unit_type_menor;
                        $articulo->inventory_id = $inventarioval->inventory_id;
                        $articulo->status = $inventarioval->status;
                        $articulo->piso_venta_id = $inventarioval->piso_venta_id;
                        $articulo->save();

                        $inventariopv = Inventario_piso_venta::select('id')->where('inventario_id', $articulo->id)->first();

                        if (isset($inventariopv->id)) {
                        $inventariopv->cantidad += $valor->pivot->cantidad;
                        $inventariopv->save();
                        } else {
                        $inventario = new Inventario_piso_venta();
                        $inventario->inventario_id = $articulo->id;
                        $inventario->piso_venta_id = $usuario;
                        $inventario->cantidad = $valor->pivot->cantidad;
                        $inventario->save();
                        }

                        $precioval = Product::where('inventory_id', $valor->pivot->inventory_id)->first();

                        //return response()->json($precioval);

                        if (isset($precioval->id)) {
                            //REGISTRAMOS LOS PRECIOS
                            $precio = new Precio();
                            $precio->costo = $precioval['cost'];
                            $precio->iva_porc = $precioval['iva_percent'];
                            $precio->iva_menor = $precioval['retail_iva_amount'];
                            $precio->sub_total_menor = $precioval['retail_total_price'] - $precioval['retail_iva_amount'];
                            $precio->total_menor = $precioval['retail_total_price'];
                            $precio->iva_mayor = $precioval['wholesale_iva_amount'];
                            $precio->sub_total_mayor = $precioval['wholesale_packet_price'];
                            $precio->total_mayor = $precio->sub_total_mayor + $precio->iva_mayor;
                            $precio->oferta = $precioval['oferta'];
                            $precio->inventario_id = $articulo->id;
                            $precio->save();
                        }

                    }

                }       

            }

            DB::commit();
            return response()->json($despacho);
            
        } catch (\Exception $e) {
            return response()->json($e);
        }
        
    }

    public function negar_despacho(Request $request)
    {
      $usuario = Auth::user()->piso_venta->id;

        $despacho = Despacho::with(['productos' => function($articulo){

        }])->findOrFail($request->id);
        $despacho->confirmado = 2;
        $despacho->save();

        /*foreach ($despacho->productos as $valor) {
            //BUSCAMOS EL ID EN INVENTARIO
            $producto = Inventario::select('id')->where('inventory_id', $valor->pivot->inventory_id)->orderBy('id', 'desc')->first();

            $inventario = Inventario_piso_venta::with('inventario')->where('piso_venta_id', $usuario)->where('inventario_id', $producto->id)->orderBy('id', 'desc')->first();

            if ($inventario['id'] == null) {
                $inventario = new Inventario_piso_venta();
                $inventario['inventario_id'] = $producto->id;
                $inventario['piso_venta_id'] = $usuario;
                $inventario['cantidad'] = 0.00;
                $inventario->save();
            }else{
                //SI ES UN DESPACHO O ES UN RETIRO
                if($despacho->type == 1){


                        $inventario->cantidad = 0.00;

                }else{

                        $inventario->cantidad = 0.00;

                }
            }
            $inventario->save();


        }*/
        /*
        foreach ($despacho->productos as $valor) {

            $producto = Inventario_piso_venta::whereHas('inventario', function($q){
                    $q->where('inventory_id', $valor->pivot->inventory_id);
                })->orderBy('id', 'desc')->first();

            $inventario = Inventario_piso_venta::with('inventario')->where('piso_venta_id', $usuario)->where('inventario_id', $producto->id)->orderBy('id', 'desc')->first();

            if ($inventario->id == null) {
                $inventario = new Inventario_piso_venta();
                $inventario->inventario_id = $producto->id;
                $inventario->piso_venta_id = $usuario
                $inventario->cantidad = $valor->pivot->cantidad;
                $inventario->save();
            }else{
                //SI ES UN DESPACHO O ES UN RETIRO
                if($despacho->type == 1){


                        $inventario->cantidad += $valor->pivot->cantidad;

                }else{

                        $inventario->cantidad -= $valor->pivot->cantidad;

                }
            }
            $inventario->save();

        }
        */

        return response()->json($despacho);
    }
    //A PARTIR DE AQUI ES EL BOYON REFRESCAR
    public function ultimo_despacho()
    {
        $usuario = Auth::user()->piso_venta->id;

        //$despacho = Despacho::select('id_extra')->where('piso_venta_id', $usuario)->where('id_extra', '!=', 'NULL')->orderBy('id', 'desc')->first();
        $despacho = Despacho::select('id_extra')->where('piso_venta_id', $usuario)->where('id_extra', '!=', 'NULL')->where('type', '!=', '2')->orderBy('id', 'desc')->first();

        return response()->json($despacho);
    }

    public function get_despachos_web(Request $request)//DEL LADO DE LA WEB
    {

        $despachos = Despacho::with('productos', 'productos.product')->where('piso_venta_id', $request->piso_venta_id)->where('id_extra', '>', $request->ultimo_despacho)->get();

        return response()->json($despachos);

    }

    public function registrar_despacho_piso_venta (Request $request)
    {
        try{

            DB::beginTransaction();

            foreach ($request->despachos as $despacho){
                //REGISTRAMOS EL DESPACHO
                $registro = new Despacho();
                $registro->id_extra = $despacho['id_extra'];
                $registro->piso_venta_id = $despacho['piso_venta_id'];
                $registro->type = $despacho['type'];
                $registro->confirmado = $despacho['confirmado'];
                $registro->created_at = $despacho['created_at'];
                $registro->save();

                foreach ($despacho['productos'] as $producto) {
                  $inventarioval = Inventory::select('id')->where('id', $producto['pivot']['inventory_id'])->exists();
                  //return response()->json($inventarioval);
                  if ($inventarioval) {
                    // SI EXITE EL PRODUCTO EN INVENTARIO
                    //REGISTRAMOS LOS PRODUCTOS
                    $detalles = new Despacho_detalle();
                    $detalles->despacho_id = $registro->id;
                    $detalles->cantidad = $producto['pivot']['cantidad'];
                    $detalles->inventory_id = $producto['pivot']['inventory_id'];
                    $detalles->save();

                    //SUMAMOS AL STOCK
                    $inventario = Inventario_piso_venta::whereHas('inventario', function($q)use($producto){
                        $q->where('inventory_id', $producto['pivot']['inventory_id']);
                    })->where('piso_venta_id', $despacho['piso_venta_id'])->orderBy('id', 'desc')->first();
                    //SI NO ENCUENTRA EL PRODUCTO LO REGISTRA
                    if ($inventario['id'] == null) {
                        $articulo = new Inventario();
                        $articulo->name = $producto['product_name'];
                        $articulo->unit_type_mayor = $producto['unit_type'];
                        $articulo->unit_type_menor = $producto['unit_type_menor'];
                        $articulo->inventory_id = $producto['pivot']['inventory_id'];
                        $articulo->status = $producto['status'];
                        $articulo->piso_venta_id = $registro->piso_venta_id;
                        $articulo->save();
                        //$articulo->id_extra = $articulo->id;
                        //$articulo->save();
                        //REGISTRAMOS LOS PRECIOS
                        $precio = new Precio();
                        $precio->costo = $producto['product']['cost'];
                        $precio->iva_porc = $producto['product']['iva_percent'];
                        $precio->iva_menor = $producto['product']['retail_iva_amount'];
                        $precio->sub_total_menor = $producto['product']['retail_total_price'] - $producto['product']['retail_iva_amount'];
                        $precio->total_menor = $producto['product']['retail_total_price'];
                        $precio->iva_mayor = $producto['product']['wholesale_iva_amount'] * $producto['qty_per_unit'];
                        $precio->sub_total_mayor = $producto['product']['wholesale_packet_price'];
                        $precio->total_mayor = $precio->sub_total_mayor + $precio->iva_mayor;
                        $precio->oferta = $producto['product']['oferta'];
                        $precio->inventario_id = $articulo->id;
                        $precio->save();
                        //$precio->costo =
                        //REGISTRA LA CANTIDAD EN EL INVENTARIO DEL PISO DE VENTA
                        /*
                        $inventario = new Inventario_piso_venta();
                        $inventario->inventario_id = $articulo->id;
                        $inventario->piso_venta_id = $despacho['piso_venta_id'];
                        $inventario->cantidad = $producto['pivot']['cantidad'];
                        $inventario->save();
                        */
                    }else{
                        //SI ES UN DESPACHO O UN RETIRO
                        /*if($registro->type == 1){
                            $inventario->cantidad += $producto['pivot']['cantidad'];
                            $inventario->save();
                        }else{
                            $inventario->cantidad -= $producto['pivot']['cantidad'];
                            $inventario->save();
                        }
                        */
                    }
                  } else {
                    // SI NO EXISTE EL PRODUCTO EN INVENTARIO
                    // ENVIAR ERROR DE SINCRONIZAR INVENTARIO PRIMERO
                    $error = "Productos nuevos en Despachos. Por Favor Sincronice Inventario Primero";
                    return response()->json($error);

                  }


                  //return response()->json($inventarioval);


                }
            }

            DB::commit();

            return response()->json(true);

        }catch(Exception $e){

            DB::rollback();
            return response()->json($e);
        }



        return response()->json(true);
    }

    public function get_despachos_sin_confirmacion($id)//DEL LADO DE LA WEB
    {
        $despachos = Despacho::select('id_extra')->where('piso_venta_id', $id)->where('confirmado', null)->get();

        return response()->json($despachos);
    }

    public function get_despachos_confirmados(Request $request)//RECIBE EL RESULTADO DEL METODO ANTERIOR
    {
        $despachos = [];

        foreach ($request->despachos as $valor) {

            $despachos[] = Despacho::with('productos')->where('id_extra', $valor['id_extra'])->where('type', 1)->first();
        }


        return response()->json($despachos);
    }

    public function actualizar_confirmaciones(Request $request)//DEL LADO DE LA WEB
    {
        foreach ($request->despachos as $valor) {

            $despacho = Despacho::where('id_extra', $valor['id_extra'])->where('piso_venta_id', $request->piso_venta_id)->first();
            $despacho->confirmado = $valor['confirmado'];
            $despacho->save();
        }

        return response()->json("actualizado con exito");
    }

    public function index_almacen()
    {

    	return view('despachos.index_almacen');
    }

    public function get_datos_create()
    {
        $piso_ventas = Piso_venta::all();

        $productos = Inventory::with('product')->where('status', 1)->get();;

        return response()->json(["piso_ventas" => $piso_ventas, "productos" => $productos]);
    }

    public function get_despachos_almacen()
    {

        $despachos = Despacho::with(['productos' => function($producto){
            $producto->select('product_name');
        }, 'piso_venta'])->orderBy('id', 'desc')->paginate(20);

        return response()->json($despachos);
    }

    public function store(Request $request)
    {
        try{

            DB::beginTransaction();

            $despacho = new Despacho();
            $despacho->piso_venta_id = $request->piso_venta;
            $despacho->type = 1;
            $despacho->save();

            $despacho->id_extra = $despacho->id;
            $despacho->save();

            foreach ($request->productos as $producto) {
                $detalles = new Despacho_detalle();
                $detalles->despacho_id = $despacho->id;
                $detalles->cantidad = $producto['cantidad'];
                $detalles->inventory_id = $producto['id'];
                $detalles->save();

                //REGISTRAR EN INVENTARIO Y PRECIO FALTA
                //SUMAMOS AL STOCK
                $inventario = Inventario_piso_venta::whereHas('inventario', function($q)use($producto){
                    $q->where('inventory_id', $producto['id']);
                })->where('piso_venta_id', $despacho['piso_venta_id'])->orderBy('id', 'desc')->first();
                //SI NO ENCUENTRA EL PRODUCTO LO REGISTRA
                if ($inventario['id'] == null) {
                    $articulo = new Inventario();
                    $articulo->name = $producto['modelo']['product_name'];
                    $articulo->unit_type_mayor = $producto['modelo']['unit_type'];
                    $articulo->unit_type_menor = $producto['modelo']['unit_type_menor'];
                    $articulo->inventory_id = $producto['id'];
                    $articulo->status = $producto['modelo']['status'];
                    $articulo->piso_venta_id = $request->piso_venta;
                    $articulo->save();
                    //REGISTRAMOS LOS PRECIOS
                    $precio = new Precio();
                    $precio->costo = $producto['modelo']['product']['cost'];
                    $precio->iva_porc = $producto['modelo']['product']['iva_percent'];
                    $precio->iva_menor = $producto['modelo']['product']['retail_iva_amount'];
                    $precio->sub_total_menor = $producto['modelo']['product']['retail_total_price'] - $producto['modelo']['product']['retail_iva_amount'];
                    $precio->total_menor = $producto['modelo']['product']['retail_total_price'];
                    $precio->iva_mayor = $producto['modelo']['product']['wholesale_iva_amount'] * $producto['modelo']['qty_per_unit'];
                    $precio->sub_total_mayor = $producto['modelo']['product']['wholesale_packet_price'];
                    $precio->total_mayor = $precio->sub_total_mayor + $precio->iva_mayor;
                    $precio->oferta = $producto['modelo']['product']['oferta'];
                    $precio->inventario_id = $articulo->id;
                    $precio->save();
                    //$precio->costo =
                    //REGISTRA LA CANTIDAD EN EL INVENTARIO DEL PISO DE VENTA
                    $inventario = new Inventario_piso_venta();
                    $inventario->inventario_id = $articulo->id;
                    $inventario->piso_venta_id = $despacho['piso_venta_id'];
                    $inventario->cantidad = $producto['cantidad'];
                    $inventario->save();
                }else{
                    //SI ES UN DESPACHO O UN RETIRO
                    if($despacho->type == 1){
                        $inventario->cantidad += $producto['cantidad'];
                        $inventario->save();
                    }else{
                        $inventario->cantidad -= $producto['cantidad'];
                        $inventario->save();
                    }
                }
            }

            $despacho = Despacho::with(['productos' => function($producto){
                $producto->select('product_name');
            }, 'piso_venta'])->findOrFail($despacho->id);

            DB::commit();

            return response()->json($despacho);

        }catch(Exception $e){

            DB::rollback();
            return response()->json($e);
        }
    }

    public function store_retiro(Request $request)
    {
        try{

            DB::beginTransaction();

            $despacho = new Despacho();
            $despacho->piso_venta_id = $request->piso_venta;
            $despacho->type = 2;
            $despacho->confirmado = 1;
            $despacho->save();

            //$despacho->id_extra = 'NULL';
            //$despacho->id_extra = $despacho->id;
            $despacho->save();

            foreach ($request->productos as $producto) {
                $detalles = new Despacho_detalle();
                $detalles->despacho_id = $despacho->id;
                $detalles->cantidad = $producto['cantidad'];
                $detalles->inventory_id = $producto['modelo']['inventario']['inventory_id'];
                $detalles->save();

                //SUMAMOS AL STOCK
                $inventario = Inventario_piso_venta::whereHas('inventario', function($q)use($producto){
                    $q->where('inventory_id', $producto['modelo']['inventario']['inventory_id']);
                })->where('piso_venta_id', $request->piso_venta)->orderBy('id', 'desc')->first();
                //SI NO ENCUENTRA EL PRODUCTO LO REGISTRA
                if ($inventario['id'] == null) {
                    $articulo = new Inventario();
                    $articulo->name = $producto['modelo']['inventario']['name'];
                    $articulo->unit_type_mayor = $producto['modelo']['inventario']['unit_type_mayor'];
                    $articulo->unit_type_menor = $producto['modelo']['inventario']['unit_type_menor'];
                    $articulo->inventory_id = $producto['modelo']['inventario']['inventory_id'];
                    $articulo->status = $producto['modelo']['inventario']['status'];
                    $articulo->piso_venta_id = $request->piso_venta;
                    $articulo->save();
                    //REGISTRAMOS LOS PRECIOS
                    $precio = new Precio();
                    $precio->costo = $producto['modelo']['inventario']['precio']['costo'];
                    $precio->iva_porc = $producto['modelo']['inventario']['precio']['iva_porc'];
                    $precio->iva_menor = $producto['modelo']['inventario']['precio']['iva_menor'];
                    $precio->sub_total_menor = $producto['modelo']['inventario']['precio']['total_menor'] - $producto['modelo']['inventario']['precio']['iva_menor'];
                    $precio->total_menor = $producto['modelo']['inventario']['precio']['total_menor'];
                    $precio->iva_mayor = $producto['modelo']['inventario']['precio']['iva_mayor'];//* $producto['modelo']['qty_per_unit']
                    $precio->sub_total_mayor = $producto['modelo']['inventario']['precio']['sub_total_mayor'];
                    $precio->total_mayor = $precio->sub_total_mayor + $precio->iva_mayor;
                    $precio->oferta = $producto['modelo']['inventario']['precio']['oferta'];
                    $precio->inventario_id = $articulo->id;
                    $precio->save();
                    //$precio->costo =
                    //REGISTRA LA CANTIDAD EN EL INVENTARIO DEL PISO DE VENTA
                    $inventario = new Inventario_piso_venta();
                    $inventario->inventario_id = $articulo->id;
                    $inventario->piso_venta_id = $despacho['piso_venta_id'];
                    $inventario->cantidad = $producto['cantidad'];
                    $inventario->save();
                }else{
                    //SI ES UN DESPACHO O UN RETIRO
                    if($despacho->type == 1){
                        $inventario->cantidad += $producto['cantidad'];
                        $inventario->save();
                    }else{
                        $inventario->cantidad -= $producto['cantidad'];
                        $inventario->save();
                    }
                }
            }

            /*$despacho = Despacho::with(['productos' => function($producto){
                $producto->select('product_name');
            }, 'piso_venta'])->findOrFail($despacho->id);*/

            DB::commit();

            return response()->json($despacho->id);

        }catch(Exception $e){

            DB::rollback();
            return response()->json($e);
        }
    }

    public function id_extra_retiro(Request $request)
    {
      $idextra = $request->despacho;
      $idlocal = $request->local;
      $despacho = Despacho::find($idlocal);
      $despacho->id_extra = $idextra;
      $despacho->save();

      return 'true';
    }

    public function get_datos_inventario_piso_venta($id)
    {
        $piso_ventas = Piso_venta::with('inventario')->findOrFail($id);
        $productos = Inventario::with('piso_venta')->whereHas('piso_venta', function($q)use($id){
            $q->where('piso_venta_id', $id);
            $q->where('cantidad', '>', 0);
        })->get();
        return response()->json($productos);
    }
}
