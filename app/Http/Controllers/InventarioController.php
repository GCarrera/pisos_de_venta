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
      foreach ($request->productosauditoria as $value) {

        $inventory_id = $value['inventario']['inventory_id'];
        $cantidadnueva = $value['cantidad'];

        $producto = Inventario::select('id')->where('inventory_id', $inventory_id)->first();
        $idunventario = $producto['id'];

        $inventarioexist = Inventario_piso_venta::with('inventario')->where('piso_venta_id', $request->idpisoventa)->where('inventario_id', $idunventario)->orderBy('id', 'desc')->exists();
        if ($inventarioexist) {
          $inventario = Inventario_piso_venta::with('inventario')->where('piso_venta_id', $request->idpisoventa)->where('inventario_id', $idunventario)->orderBy('id', 'desc')->first();
          $inventario->cantidad = $cantidadnueva;

          $inventario->save();
        }

      }

      return response()->json(true);
    }

    public function ultimo_inventory()
    {

        $inventory = Inventory::select('id')->orderBy('id', 'desc')->first();

        if (isset($inventory->id)) {
          return response()->json($inventory->id);
        } else {
          return '0';
        }


    }

    public function get_inventory($id)//WEB
    {

        $inventory = Inventory::with('product')->where('id', '>', $id)->get();

        return response()->json($inventory);
    }

    public function store_inventory(Request $request)
    {
      //return response()->json($request->productos);
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
                $inventory->save();



                if ($producto['product'] != null) {

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
                  //return response()->json($wholesale_packet_price);
                  $wholesale_total_individual_price = $producto['product']['wholesale_total_individual_price']*$datadolar;
                  $wholesale_total_packet_price = round($producto['product']['wholesale_total_packet_price'], 2)*$datadolar;
                  //$arrayErrors = array($cost, $retail_total_price, $wholesale_packet_price, $wholesale_total_individual_price, $wholesale_total_packet_price, $datadolar);
                  //= $producto['product']['wholesale_iva_amount'];
                  //= $producto['product']['oferta'];
                  //= $producto['product']['inventory_id'];*/

                  /*$cost = round($cost, 2);
                  $retail_total_price = round($retail_total_price, 2);
                  $wholesale_packet_price = round($wholesale_packet_price, 2);
                  $wholesale_total_individual_price = round($wholesale_total_individual_price, 2);
                  $wholesale_total_packet_price = round($wholesale_total_packet_price, 2);*/

                  try {

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

                    //REGISTRAMOS LOS PRECIOS PRODUCT
                    /*$product = new Product();
                    $product->cost = $cost;
                    $product->iva_percent = $producto['product']['iva_percent'];
                    $product->retail_margin_gain = $producto['product']['retail_margin_gain'];
                    $product->retail_total_price = $retail_total_price;
                    $product->retail_iva_amount = $producto['product']['retail_iva_amount'];
                    $product->image = $producto['product']['image'];
                    $product->wholesale_margin_gain = $producto['product']['wholesale_margin_gain'];
                    $product->wholesale_packet_price = $wholesale_packet_price;
                    $product->wholesale_total_individual_price = $wholesale_total_individual_price;
                    $product->wholesale_total_packet_price = $wholesale_total_packet_price;
                    $product->wholesale_iva_amount = $producto['product']['wholesale_iva_amount'];
                    $product->oferta = $producto['product']['oferta'];
                    $product->inventory_id = $producto['product']['inventory_id'];
                    $product->save();*/


                  } catch (Exception $e) {
                    DB::rollback();
                    return response()->json($e);
                  }


                }
            }
            //DB::commit();
            DB::commit();

            return response()->json(true);

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
