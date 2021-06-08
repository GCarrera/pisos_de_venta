<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Inventario;
use App\Inventario_piso_venta;
use App\Piso_venta;
use App\Venta;
use App\Detalle_venta;
use App\Despacho;
use Illuminate\Support\Facades\Auth;
use App\Inventory;
use App\Despacho_detalle;
use App\Product;
use App\Precio;
use App\Dolar;
use DB;

class InventarioController extends Controller
{
    //
    public function index()
    {

    	return view('inventario.index');
    }

    public function get_inventario(Request $request)
    {
        $usuario = Auth::user()->piso_venta->id;

        //$inventario = Inventario_piso_venta::with(['inventario' => function($inventario){
        //    $inventario->where('name', 'quo');
        //}])->where('piso_venta_id', $usuario)->whereHas('inventario')->get();

        $inventario  = Inventario_piso_venta::with('inventario.precio')->where('piso_venta_id', $usuario)->whereHas('inventario', function($q)use($request){
           // $q->where('name', 'quo');
            if ($request->search != null) {

                $q->where('name', 'like', '%'.$request->search.'%');
            }

        })->orderBy('cantidad', 'desc')->paginate(20);
        return response()->json($inventario);
    }

    public function auditoria(Request $request)
    {

      try{

          DB::beginTransaction();
          foreach ($request->productosauditoria as $value) {

            $inventory_id = $value['inventario']['inventory_id'];
            $cantidadnueva = $value['cantidad'];
            $nombrenuevo = $value['inventario']['name'];

            $producto = Inventario::select('id')->where('inventory_id', $inventory_id)->orderBy('id', 'desc')->first();

            if (isset($producto->id)) {
              $idunventario = $producto['id'];
              $invdeletes = Inventario::where('inventory_id', $inventory_id)->where('id', "!=", $idunventario)->get();
              foreach ($invdeletes as $value) {
                $preciodelete = Precio::where('inventario_id', $value['id'])->first();
                if (isset($preciodelete['id'])) {
                  $preciodelete->delete();
                }
              }
              $producto = Inventario::where('inventory_id', $inventory_id)->where('id', '!=', $idunventario)->forceDelete();

              $inventarioexist = Inventario_piso_venta::where('piso_venta_id', $request->idpisoventa)->where('inventario_id', $idunventario)->first();
              //return $inventarioexist;
              if (isset($inventarioexist->id)) {
                $inventarioexist->cantidad = $cantidadnueva;
                $inventarioexist->save();

                $productoupdate = Inventario::where('inventory_id', $inventory_id)->update(['name' => $nombrenuevo]);

              } else {
                $inventario = new Inventario_piso_venta();
                $inventario->inventario_id = $idunventario;
                $inventario->piso_venta_id = $request->idpisoventa;
                $inventario->cantidad = $cantidadnueva;
                $inventario->save();
              }
            } else {

              $inventarioval = Inventory::select('id')->where('id', $value['inventario']['inventory_id'])->first();

              if (isset($inventarioval->id)) {
                $articulo = new Inventario();
                $articulo->name = $value['inventario']['name'];
                $articulo->unit_type_mayor = $value['inventario']['unit_type_mayor'];
                $articulo->unit_type_menor = $value['inventario']['unit_type_menor'];
                $articulo->inventory_id = $value['inventario']['inventory_id'];
                $articulo->status = $value['inventario']['status'];
                $articulo->piso_venta_id = $value['inventario']['piso_venta_id'];
                $articulo->save();

                $inventariopv = Inventario_piso_venta::select('id')->where('inventario_id', $articulo->id)->first();

                if (isset($inventariopv->id)) {
                  $inventariopv->cantidad = $cantidadnueva;
                  $inventariopv->save();
                } else {
                  $inventario = new Inventario_piso_venta();
                  $inventario->inventario_id = $articulo->id;
                  $inventario->piso_venta_id = $request->idpisoventa;
                  $inventario->cantidad = $cantidadnueva;
                  $inventario->save();
                }

                $precioval = Product::where('inventory_id', $value['inventario']['inventory_id'])->first();

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

          foreach ($request->softdeletes as $value) {
            $id = $value["id"];
            $deletes = Inventory::find($id);
            if (isset($deletes->id)) {
              $inventariodeletes = Inventario::where('inventory_id', $id)->first();
              if (isset($inventariodeletes->id)) {
                $inventarioid = $inventariodeletes->id;
                //return $inventarioid;
                $invpv = Inventario_piso_venta::where('inventario_id', $inventarioid)->delete();
                $inventariodeletes = Inventario::where('inventory_id', $id)->delete();
              }
              $deletes->delete();
            }

          }

          DB::commit();

          return response()->json(true);

      }catch(Exception $e){

          DB::rollback();
          return response()->json($e);
      }

    }

    public function ultimo_inventory()
    {
      // BK DE SINRONIZAION
        /*$inventory = Inventory::select('id')->orderBy('id', 'desc')->first();

        if (isset($inventory->id)) {
          return response()->json($inventory->id);
        } else {
          return '0';
        }*/
      // BK DE SINRONIZAION
      try {

        DB::beginTransaction();

        $inventoryCreated = Inventory::select('created_at')->orderBy('created_at', 'desc')->first();
        $inventoryUpdated = Inventory::select('updated_at')->orderBy('updated_at', 'desc')->first();
        $productUpdated = Product::select('updated_at')->orderBy('updated_at', 'desc')->first();
        $inventoryDeleted = Inventory::onlyTrashed()->select('deleted_at')->orderBy('deleted_at', 'desc')->first();

        if (isset($inventoryCreated->created_at)) {
          $created = date('Y-m-d H:i:s', strtotime($inventoryCreated->created_at));
          $updated = date('Y-m-d H:i:s', strtotime($inventoryUpdated->updated_at));
          $updatedProd = date('Y-m-d H:i:s', strtotime($productUpdated->updated_at));
          if (isset($inventoryDeleted->deleted_at)) {
            $deleted = date('Y-m-d H:i:s', strtotime($inventoryDeleted->deleted_at));
          } else {
            $deleted = 0;
          }
          DB::commit();
          return response()->json(['created' => $created, 'updated' => $updated, 'deleted' => $deleted, 'updatedProd' => $updatedProd]);
        } else {
          DB::commit();
          return '0';
        }

      } catch (Exception $e) {
        DB::rollback();
        return response()->json($e);
      }



    }

    public function get_inventory($id)//WEB
    {

        $inventory = Inventory::with('product')->where('id', '>', $id)->get();

        return response()->json($inventory);
    }

    public function store_inventory(Request $request)
    {
        try{

            DB::beginTransaction();
            foreach ($request->productos as $producto) {

                $inventory = new Inventory();
                $inventory->id = $producto['id'];
                $inventory->product_name = $producto['product_name'];
                $inventory->description = $producto['description'];
                $inventory->quantity = $producto['quantity'];
                $inventory->unit_type = $producto['unit_type'];
                $inventory->unit_type_menor = $producto['unit_type_menor'];
                $inventory->qty_per_unit = $producto['qty_per_unit'];
                $inventory->status = $producto['status'];
                $inventory->total_qty_prod = $producto['total_qty_prod'];
                $inventory->created_at = $producto['created_at'];
                $inventory->save();



                if ($producto['product'] != null) {

                  try {

                    //REGISTRAMOS LOS PRECIOS PRODUCT

                    $product = new Product();
                    $product->cost = $producto['product']['cost'];
                    $product->iva_percent = $producto['product']['iva_percent'];
                    $product->retail_margin_gain = $producto['product']['retail_margin_gain'];
                    $product->retail_total_price = $producto['product']['retail_total_price'];
                    $product->retail_iva_amount = $producto['product']['retail_iva_amount'];
                    $product->image = $producto['product']['image'];
                    $product->wholesale_margin_gain = $producto['product']['wholesale_margin_gain'];
                    $product->wholesale_packet_price = $producto['product']['wholesale_packet_price'];
                    $product->wholesale_total_individual_price = $producto['product']['wholesale_total_individual_price'];
                    $product->wholesale_total_packet_price = $producto['product']['wholesale_total_packet_price'];
                    $product->wholesale_iva_amount = $producto['product']['wholesale_iva_amount'];
                    $product->oferta = $producto['product']['oferta'];
                    $product->inventory_id = $producto['product']['inventory_id'];
                    $product->save();

                  } catch (Exception $e) {
                    DB::rollback();
                    return response()->json($e);
                  }

                }
            }

            DB::commit();

            return response()->json(true);

        }catch(Exception $e){

            DB::rollback();
            return response()->json($e);
        }

    }

    public function update_inventory(Request $request)
    {
        try{

            DB::beginTransaction();
            foreach ($request->productos as $producto) {

              $inventory = Inventory::find($producto['id']);

                $inventory->product_name = $producto['product_name'];
                $inventory->description = $producto['description'];
                $inventory->quantity = $producto['quantity'];
                $inventory->unit_type = $producto['unit_type'];
                $inventory->unit_type_menor = $producto['unit_type_menor'];
                $inventory->qty_per_unit = $producto['qty_per_unit'];
                $inventory->status = $producto['status'];
                $inventory->total_qty_prod = $producto['total_qty_prod'];
                $inventory->updated_at = $producto['updated_at'];
                $inventory->save();

                DB::commit();

            }

            return response()->json("exito");

        }catch(Exception $e){

            DB::rollback();
            return response()->json($e);
        }

    }

    public function update_products(Request $request)
    {
        try{

            DB::beginTransaction();
            foreach ($request->precios as $producto) {

              //ACTUALIZAMOS LOS PRECIOS PRODUCT

              $product = Product::where('inventory_id', $producto['inventory_id'])->first();

              if (isset($product->id)) {
                $product->cost = $producto['cost'];
                $product->iva_percent = $producto['iva_percent'];
                $product->retail_margin_gain = $producto['retail_margin_gain'];
                $product->retail_total_price = $producto['retail_total_price'];
                $product->retail_iva_amount = $producto['retail_iva_amount'];
                $product->image = $producto['image'];
                $product->wholesale_margin_gain = $producto['wholesale_margin_gain'];
                $product->wholesale_packet_price = $producto['wholesale_packet_price'];
                $product->wholesale_total_individual_price = $producto['wholesale_total_individual_price'];
                $product->wholesale_total_packet_price = $producto['wholesale_total_packet_price'];
                $product->wholesale_iva_amount = $producto['wholesale_iva_amount'];
                $product->oferta = $producto['oferta'];
                $product->save();
              } else {
                $product = new Product();
                $product->cost = $producto['cost'];
                $product->iva_percent = $producto['iva_percent'];
                $product->retail_margin_gain = $producto['retail_margin_gain'];
                $product->retail_total_price = $producto['retail_total_price'];
                $product->retail_iva_amount = $producto['retail_iva_amount'];
                $product->image = $producto['image'];
                $product->wholesale_margin_gain = $producto['wholesale_margin_gain'];
                $product->wholesale_packet_price = $producto['wholesale_packet_price'];
                $product->wholesale_total_individual_price = $producto['wholesale_total_individual_price'];
                $product->wholesale_total_packet_price = $producto['wholesale_total_packet_price'];
                $product->wholesale_iva_amount = $producto['wholesale_iva_amount'];
                $product->oferta = $producto['oferta'];
                $product->inventory_id = $producto['inventory_id'];
                $product->save();
              }

              DB::commit();

            }

            return response()->json("exito");

        }catch(Exception $e){

            DB::rollback();
            return response()->json($e);
        }

    }

    public function deleted_inventory(Request $request)
    {
        try{

            DB::beginTransaction();
            foreach ($request->productos as $producto) {

              //ELIMINAMOS LOS PRODUCT

              $inventory = Inventory::find($producto['id']);
              if (isset($inventory->id)) {
                $inventory->delete();
              }

              DB::commit();

            }

            return response()->json("exito");

        }catch(Exception $e){

            DB::rollback();
            return response()->json($e);
        }

    }

    public function get_precios_inventory()//WEB
    {
        $inventory = Inventory::with('product')->where('status', 1)->whereHas('product', function($q){ $q->where('id', '!=', null);})->get();

        return response()->json($inventory);
    }

    public function actualizar_precios_inventory(Request $request)
    {
        try{

            DB::beginTransaction();

            foreach ($request->productos as $producto) {

              $inventoryupdate = Inventory::select('id')->where('id', $producto['id'])->first();

              if (isset($inventoryupdate['id'])) {
                $inventoryupdate->product_name   = $producto['product_name'];
                $inventoryupdate->description    = $producto['description'];
                $inventoryupdate->quantity       = $producto['quantity'];
                $inventoryupdate->unit_type      = $producto['unit_type'];
                $inventoryupdate->qty_per_unit   = $producto['qty_per_unit'];
                $inventoryupdate->total_qty_prod = $producto['total_qty_prod'];
                //$inventoryupdate->stock_min      = $producto['stock_min'];

                $inventoryupdate->save();
              } else {
                $inventory = new Inventory();
                $inventory->id = $producto['id'];
                $inventory->product_name = $producto['product_name'];
                $inventory->description = $producto['description'];
                $inventory->quantity = $producto['quantity'];
                $inventory->unit_type = $producto['unit_type'];
                $inventory->unit_type_menor = $producto['unit_type_menor'];
                $inventory->qty_per_unit = $producto['qty_per_unit'];
                $inventory->status = $producto['status'];
                $inventory->total_qty_prod = $producto['total_qty_prod'];
                //$inventoryupdate->stock_min      = $producto['stock_min'];
                $inventory->save();
              }

              $productupdate = Product::select('id')->where('inventory_id', $producto['id'])->first();

              if (isset($productupdate['id'])) {
          			$productupdate->retail_margin_gain     = $producto['product']['retail_margin_gain'];
          			$productupdate->wholesale_margin_gain  = $producto['product']['wholesale_margin_gain'];

                $productupdate->save();
              } else {
                $productupdate = new Product();
                $productupdate->cost                             = $producto['product']['cost'];
            		$productupdate->iva_percent                      = $producto['product']['iva_percent'];
            		$productupdate->retail_margin_gain               = $producto['product']['retail_margin_gain'];
            		$productupdate->retail_total_price               = $producto['product']['retail_total_price'];
            		$productupdate->retail_iva_amount                = $producto['product']['retail_iva_amount'];
            		$productupdate->wholesale_margin_gain            = $producto['product']['wholesale_margin_gain'];
            		$productupdate->wholesale_packet_price           = $producto['product']['wholesale_packet_price'];
            		$productupdate->wholesale_total_packet_price     = $producto['product']['wholesale_total_packet_price'];
            		$productupdate->wholesale_total_individual_price = $producto['product']['wholesale_total_individual_price'];
            		$productupdate->wholesale_iva_amount             = $producto['product']['wholesale_iva_amount'];
            		$productupdate->inventory_id                     = $producto['id'];
                $productupdate->image                            = "default.jpeg";
            		$productupdate->oferta                           = $producto['product']['oferta'];

            		$productupdate->save();
              }

                $inventario = Inventario::select('id')->where('inventory_id', $producto['id'])->orderBy('id', 'desc')->first();

                if ($inventario['id'] != null) {

                  /*$dolar = Dolar::orderby('id','DESC')->first();
                  $datadolar = $dolar['price'];


                  $cost = $producto['product']['cost']*$datadolar;
                  //= $producto['product']['iva_percent'];
                  //= $producto['product']['retail_margin_gain'];
                  $retail_total_price = $producto['product']['retail_total_price']*$datadolar;
                  //= $producto['product']['retail_iva_amount'];
                  //= $producto['product']['image'];
                  //= $producto['product']['wholesale_margin_gain'];
                  $wholesale_packet_price = round($producto['product']['wholesale_packet_price'], 2)*$datadolar;
                  $wholesale_total_individual_price = $producto['product']['wholesale_total_individual_price']*$datadolar;
                  $wholesale_total_packet_price = round($producto['product']['wholesale_total_packet_price'], 2)*$datadolar;
                  //$arrayErrors = array($cost, $retail_total_price, $wholesale_packet_price, $wholesale_total_individual_price, $wholesale_total_packet_price, $datadolar);
                  //= $producto['product']['wholesale_iva_amount'];
                  //= $producto['product']['oferta'];
                  //= $producto['product']['inventory_id'];*/

                  /*/$cost = round($cost, 2);
                  $retail_total_price = round($retail_total_price, 2);
                  $wholesale_packet_price = round($wholesale_packet_price, 2);
                  $wholesale_total_individual_price = round($wholesale_total_individual_price, 2);
                  $wholesale_total_packet_price = round($wholesale_total_packet_price, 2);*/

                  //return response()->json($wholesale_packet_price);

                    $precio = Precio::where('inventario_id', $inventario['id'])->orderBy('id', 'desc')->first();
                    $precio->costo = $producto['product']['cost'];
                    $precio->iva_porc = $producto['product']['iva_percent'];
                    $precio->iva_menor = $producto['product']['retail_iva_amount'];
                    $precio->sub_total_menor = $producto['product']['retail_total_price'] - $producto['product']['retail_iva_amount'];
                    $precio->total_menor = $producto['product']['retail_total_price'];
                    $precio->iva_mayor = $producto['product']['wholesale_iva_amount'] * $producto['qty_per_unit'];
                    $precio->sub_total_mayor = $producto['product']['wholesale_packet_price'];
                    $precio->total_mayor = $precio->sub_total_mayor + $precio->iva_mayor;
                    $precio->oferta = $producto['product']['oferta'];
                    $precio->save();
                }

            }

            foreach ($request->precios as $producto) {

                $inventario = Inventario::select('id')->where('inventory_id', null)->where('id_extra', $producto['id_extra'])->orderBy('id', 'desc')->first();

                if ($inventario['id'] != null) {
                    $precio = Precio::where('inventario_id', $inventario['id'])->orderBy('id', 'desc')->first();
                    $precio->costo = $producto['precio']['costo'];
                    $precio->iva_porc = $producto['precio']['iva_porc'];
                    $precio->iva_menor = $producto['precio']['iva_menor'];
                    $precio->sub_total_menor = $producto['precio']['sub_total_menor'];
                    $precio->total_menor = $producto['precio']['total_menor'];
                    $precio->save();
                }

            }

            DB::commit();

            return response()->json(true);

        }catch(Exception $e){

            DB::rollback();
            return response()->json($e);
        }
    }

    public function get_inventory_id()
    {
        $inventario = Inventario::where('inventory_id', null)->get();

        return response()->json($inventario);

    }

    public function actualizar_inventory_id(Request $request)
    {

        foreach ($request->inventario as $valor) {

            $inventario = Inventario::where('id_extra', $valor['id_extra'])->orderBy('id', 'desc')->first();
            $inventario->inventory_id = $valor['inventory_id'];
            $inventario->save();
        }

        return response()->json(true);
    }

    public function prueba()
    {

        $inventario = Inventario::with('inventory')->get();

    	return $inventario;
    }

}
