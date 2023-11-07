<template>
 <div>
   <div class="container">
     <div class="card shadow">
       <div class="card-body">
         <h1 class="text-center">Nuevo Retiro</h1>
         <div class="mb-3">

           <form action="/despachos-retiro" method="post" @submit.prevent="despachar()" onkeydown="return event.key != 'Enter';"><!--Formulario-->


             <input type="hidden" name="tipo" value="2"><!--ESTABLECER SI ES UN DESPACHO O UN RETIRO-->

             <div class="form-row">

               <div class="form-group col-md-4">
                 <label for="producto">Producto:</label>
                 <v-select id="producto" @input="setFocus" :labelSearchPlaceholder="labelSearch" :labelNotFound="labelNot" :labelTitle="labelTit" :options="inventarioSelect" v-model="selectedValue" searchable showDefaultOption/>
               </div>

               <div class="form-group col-md-2">
                 <label for="cantidad">Disponibles:</label>
                 <input type="number" name="cantidad" id="cantidad" placeholder="Cantidad" class="form-control" v-model="disponibles" disabled="">
               </div>

               <div class="form-group col-md-2">
                 <label for="cantidad">Cantidad al menor:</label>
                 <input type="number" name="cantidad" id="cantidad" placeholder="Cantidad" class="form-control" v-model="articulo.cantidad" ref="cantidad" v-on:keyup.enter="agregar_producto_enter">
               </div>

               <div class="form-group col-md-1">
                 <label class="text-center" for="">Acción:</label><br>
                 <button class="btn btn-primary btn-block" type="button" @click="agregar_producto" :disabled="disabled_nuevo"><b-icon-plus></b-icon-plus></button>
               </div>
             </div>

             <table class="table table-bordered">
               <thead>
                 <tr>
                   <th>Producto</th>
                   <th>cantidad</th>
                   <th>Acciones</th>
                 </tr>
               </thead>
               <tbody>
                 <tr v-for="(produc_enviar, index) in productos" :key="index">
                   <td>{{produc_enviar.nombre}}</td>
                   <td>{{produc_enviar.cantidad}}</td>
                   <td>
                     <button class="btn btn-danger" type="button" @click="eliminar(index)">Eliminar</button>
                   </td>
                 </tr>
               </tbody>
             </table>

               <span v-if="loading == false">
                 <button type="submit" class="btn btn-primary" :disabled="productos.length <= 0">
                   Retirar
                 </button>
               </span>

               <span v-if="loading == true">
                 <button type="submit" class="btn btn-primary" :disabled="true">
                   <div class="spinner-border text-light text-center" role="status" v-if="loading == true">
                     <span class="sr-only">Cargando...</span>
                   </div>
                 </button>
               </span>


           </form>

         </div>

       </div>
     </div>
   </div>

 </div>
</template>

<script>
 import VSelect from '@alfsnd/vue-bootstrap-select'
 export default{
   components: {
       VSelect,
   },
   data(){
     return{
       id:'',
       toastCount: 0,
       loading: false,
       despachos: [],
       piso_ventas: [],
       inventario: [],
       inventarioSelect: [],
       productos: [],//LISTA DE PRODUCTOS QUE VOY A AGREGAR
       articulo: {
         id: 0,
         nombre: "",
         cantidad: "",
         modelo: {}
       },
       piso_venta: "",
       currentPage: 0,
       per_page: 0,
       total_paginas: 0,
       produc_cantidad: "",
       productos_retirar: [],
       piso_venta_retiro: "",
       inventario_piso_venta: [],
       disab: true,
       inventario_cantidad_piso: "",
       articulo_retiro: {
         id: "",
         nombre: "",
         cantidad: "",
         modelo: {}
       },
       pagination: {//PAGINACION DE RIMORSOFT
        'total' : 0,
         'current_page' : 0,
               'per_page' : 0,
               'last_page' : 0,
               'from' : 0,
               'to' : 0
       },
       offset: 5,
       disponibles: "",
       //data select vue
       selectedValue: null,
       labelSearch: "Buscar",
       labelNot: 'No se encontro nada',
       labelTit: "Nada seleccionado"
     }
   },
   methods:{
     makeToast(variant = null) {
      this.toastCount++
      this.$bvToast.toast(`Estamos procesando el retiro por espere un momento, gracias`, {
        title: 'Excelente!!!',
        autoHideDelay: 5000,
        variant: variant,
        solid: true,
        //toaster: 'b-toaster-bottom-left',

      })
    },
     setFocus(){
       this.$refs.cantidad.focus();
       console.log("setFocus");
     },
     agregar_producto_enter(){
       var validation = parseFloat(this.disponibles)-parseFloat(this.articulo.cantidad);

       if (this.articulo.id != 0 && this.articulo.cantidad != "" && validation >= 0){

         this.agregar_producto();

       }
     },
     get_datos(){
       console.log("get_datos");
			 //SOLICITO LOS PISOS DE VENTAS Y PRODUCTOS
			 axios.get(location.origin + '/api/ventas-datos-create').then(response => {

        //this.inventario = response.data
        //this.inventario_compra = response.data
        //console.log(this.inventario);

        /*this.inventario.forEach(item => {
          let datos = {value: item.id, text: item.inventario.name}
          this.inventarioSelect.push(datos);
        });*/
        this.inventario = response.data.inventario
				this.inventarioSelect = response.data.select

				//console.log(this.inventarioSelect);
				//console.log(this.inventario);
			 }).catch(e => {
				 console.log(e.response)
			 });
     },
     get_id(){
       axios.get(location.origin + '/api/get-id').then(response => {
         this.id = response.data;
         console.log(this.id);
       }).catch(e => {
         console.log(e.response);
       });
     },
     establecer_nombre(id, valor){//COLOCAR EL NOMBRE AL PRODUCTO QUE ESTOY AGREGANDO
       console.log('establecer_nombre');
       //console.log(id);
       //console.log(valor);
       let resultado = this.inventario.find(element => element.inventario.id == id)
       this.articulo.nombre = resultado.inventario.name;
       this.articulo.modelo = resultado;
       this.articulo.id = resultado.id;
       this.disponibles = resultado.cantidad;
       console.log('this.articulo');
       console.log(this.articulo);
       if(valor == "retiro"){

         this.produc_cantidad = resultado.total_qty_prod;
       }
     },
     agregar_producto(retiro){

       if (retiro == "retiro") {

         this.productos_retirar.push(this.articulo_retiro)
         this.articulo_retiro = {id: "", nombre: "", cantidad: ""};
       }else{
         this.productos.push(this.articulo);

         //console.log(this.productos)
         this.articulo = {id: 0, nombre: "", cantidad: ""};
       }


     },
     eliminar(index, retiro){
       if (retiro == "retiro") {

         this.productos_retirar.splice(index, 1);
       }else{
       this.productos.splice(index, 1);
       }
     },
     despachar(){

       this.loading = true;
       this.makeToast('info');

       console.log(this.productos);

       axios.post(location.origin + '/api/despachos-retiro', {productos: this.productos, piso_venta: this.id}).then(response => {
         console.log(response)
         var idlocal = response.data;
         window.location="http://localhost/pisos_de_venta/public/despachos";
         /*axios.post('http://www.mipuchito.com/api/store-retiro', {productos: this.productos, piso_venta: this.id}).then(response => {
           console.log(response);
           var idextra = response.data;
           //this.despachos.splice(0,0, response.data);

             axios.post(location.origin + '/api/id-extra-retiro', {despacho: idextra, local: idlocal}).then(response => {

               this.articulo = {id: 0, nombre: "", cantidad: ""};
               this.productos = [];
               this.loading = false;
               window.location="http://localhost/pisos_de_venta/public/despachos";

             }).catch(e => {
               console.log(e.response)
               this.error = true;
             });

         }).catch(e => {
           console.log(e.response)
           this.error = true;
         });*/
       }).catch(e => {

         console.log(e.response)
       })
       //$('#modal-nuevo').modal('hide')
       //this.$bvModal.hide("modal-nuevo")
     },
   },
   computed: {
     disabled_nuevo(){
       console.log('disabled_nuevo');
       console.log(this.articulo.id);
       console.log(this.articulo.cantidad);
       var validation = parseFloat(this.disponibles)-parseFloat(this.articulo.cantidad);

       if (this.articulo.id != 0 && this.articulo.cantidad != "" && validation >= 0){

         return false;
       }else{
         return true;
       }
     }
   },
   created(){

     this.get_id();
     this.get_datos();
   },
   watch:{
     selectedValue: function (val) {
       if (val != null) {
         this.establecer_nombre(val.value)
         console.log('selectedValue');
             console.log(val)
           }
       }
   }
 }
</script>
