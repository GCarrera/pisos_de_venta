<template>
	<div class="container">
		<!--ALERT SI NO HAY SUFICIENTES PRODUCTOS-->
		<b-alert
	      :show="dismissCountDown"
	      dismissible
	      variant="danger"
	      @dismissed="dismissCountDown=0"
	      @dismiss-count-down="countDownChanged"
	      v-if="error == true"
	      fade
	    >
	      <p>{{error_message}}.</p>
	      <!--
	      <b-progress
	        variant="warning"
	        :max="dismissSecs"
	        :value="dismissCountDown"
	        height="4px"
	      ></b-progress>
	  -->
	    </b-alert>

		<div class="card">
			<div class="card-body shadow">

				<h1 class="text-center">Nueva venta:</h1>
				<hr>
				<form method="post" @submit.prevent="" @keyup.alt.enter="venderTec" onkeydown="return event.key != 'Enter';"><!--Formulario-->

					<div>
						<div class="form-row align-items-center">
							<div class="form-group col-md-3">
								<label>Producto:</label>
								<v-select id="producto" @input="setFocus" :labelSearchPlaceholder="labelSearch" :labelNotFound="labelNot" :labelTitle="labelTit" :options="inventario" v-model="selectedValue" searchable showDefaultOption/>

								<!--
							    <select class="form-control" v-model="articulo.id" @change="establecer_nombre(articulo.id)">
								  <option value="0">Seleecion producto</option>
								  <option v-for="(prod, index) in inventario" :key="index" :value="prod.inventario.id">{{prod.inventario.name}}</option>
								</select>
								-->
							</div>

							<div class="form-group col-md-2">
								<label for="cantidad">Disponible:</label>
								<input type="number" name="cantidad_disponible" id="cantidad_disponible" placeholder="" class="form-control" v-model="cantidad_disponible" disabled="">
							</div>

							<div class="form-group col-md-2">
								<label for="cantidad">Precio:</label>
								<input type="text" name="precio_disponible" id="precio_disponible" placeholder="" class="form-control" v-model="precio_disponible" disabled="">
							</div>

							<div class="form-group col-md-2">
								<label for="cantidad">Cantidad:</label>
								<input type="number" name="cantidad" id="cantidad" placeholder="Cantidad" class="form-control" v-model="articulo.cantidad" ref="cantidad" v-on:keyup.enter="agregar_producto_enter">
							</div>

							<div class="form-group col-md-2">
								<label class="text-center" for="">Acción:</label><br>
								<button class="btn btn-primary btn-block" type="button" @click="agregar_producto()" :disabled="disabled_venta">Agregar</button>
							</div>
							<div class="form-group col-md-1">
								<label class="text-center" for="">Divisas:</label><br>
								<b-form-checkbox v-model="checked_divisa" class="align-middle" name="check-button" switch>
						    </b-form-checkbox>
							</div>
						</div>
					</div>

					<table class="table table-bordered table-hover table-striped table-sm">
						<thead class="bg-primary text-white">
							<tr>
								<th>Producto</th>
								<th>Cantidad</th>
								<th>Precio</th>
								<!-- <th>Iva</th> -->
								<th>Sub total</th>
								<th>Acciones</th>
							</tr>
						</thead>
						<tbody>
							<tr v-for="(produc_enviar, index) in productos" :key="index">
								<td>{{produc_enviar.nombre}}</td>
								<td>{{produc_enviar.cantidad}}</td>
								<td>{{new Intl.NumberFormat("de-DE", {minimumFractionDigits: 2}).format(produc_enviar.total/produc_enviar.cantidad)}}</td>
								<!-- <td>{{produc_enviar.iva}}</td> -->
								<td>{{new Intl.NumberFormat("de-DE", {minimumFractionDigits: 2}).format(produc_enviar.total)}}</td>
								<td>
									<button class="btn btn-danger" type="button" @click="eliminar(index)">Eliminar</button>
								</td>
							</tr>
						</tbody>
					</table>

					<div class="row">
		      			<div class="col-md-6">

		      			</div>

		      			<div class="col-md-3 text-right">

		      				<span class="font-weight-bold small">Total:</span><br>
		      				<span v-if="checked_divisa" class="font-weight-bold small">Descuento:</span><br>
		      				<span v-if="checked_divisa" class="font-weight-bold small">Total con Descuento:</span>

		      			</div>

		      			<div class="col-md-3">
									<span class="small"> BsS {{mostar_total_total}}</span><br>
									<span v-if="checked_divisa" class="small"> BsS {{mostar_descuento}}</span><br>
									<span v-if="checked_divisa" class="small"> BsS {{mostar_total_descuento}}</span>
		      				<span class="d-none"> Bs {{mostar_sub_total}}</span><br>
			      			<!-- <span class="small">{{iva_total}}</span><br> -->
		      			</div>

		      		</div>

		      		<div class="row">
			        	<button type="submit" class="btn btn-primary ml-auto" @click="vender" :disabled="productos.length <= 0">Vender</button>
			      	</div>
		      	</form>
	      	</div>
		</div>
	</div>
</template>

<script>
	import VSelect from '@alfsnd/vue-bootstrap-select'
	import Swal from 'sweetalert2'

	export default{
		components: {
		    VSelect,
		},
		data(){
			return{
				checked_divisa: false,
				inventario_completo: [],
				inventario: [],
				productos: [],//LISTA DE PRODUCTOS QUE VOY A AGREGAR
				iva:"",
				dolar:0,
				articulo: {
					id: 0,
					nombre: "",
					cantidad: "",
					sub_total: "",
					iva: "",
					total: ""
				},
				cantidad_disponible: "",
				precio_disponible: "",
				error: false,
				error_message: "",
				dismissSecs: 10,//MODAL
		        dismissCountDown: 0,
		        showDismissibleAlert: false,
		        selectedValue: null,
						labelSearch: "Buscar",
						labelNot: 'No se encontro nada',
						labelTit: "Nada seleccionado"
			}
		},
		methods:{
			agregar_producto_enter(){
				if (this.articulo.id != 0 && this.articulo.cantidad != ""){
					//console.log("holis");

					var validation = parseFloat(this.cantidad_disponible)-parseFloat(this.articulo.cantidad);

					if (this.articulo.id != 0 && this.articulo.cantidad != "" && validation >= 0 && this.articulo.cantidad > 0){

						this.agregar_producto();

					}

				}
			},
			setFocus(){
				this.$refs.cantidad.focus();
			},
			get_datos(){
				//SOLICITO LOS PISOS DE VENTAS Y PRODUCTOS
				axios.get('http://localhost/pisos_de_venta/public/api/ventas-datos-create').then(response => {

					console.log(response);
					//this.inventario_completo = response.data
					//this.inventario_compra = response.data
					console.log("axios get datos");
					response.data.forEach(item => {
						if (item.inventario != null) {
							let datos = {value: item.inventario.id , text: item.inventario.name}
							this.inventario_completo.push(item);
							this.inventario.push(datos);
						}
					});
					console.log("luego del forEach");
					console.log(this.inventario);

				}).catch(e => {
					console.log(e.response);
				});
			},
			get_dolar() {
				axios.get('http://localhost/pisos_de_venta/public/api/get-dolar').then(response =>{
					console.log(response)
					this.dolar = response.data.dolar;
				}).catch(e => {
					console.log(e.response);
				});
			},
			establecer_nombre(id, compra){//COLOCAR EL NOMBRE AL PRODUCTO QUE ESTOY AGREGANDO
				if (compra == "compra") {

					let resultado = this.inventario_compra.find(element => element.inventario.id == id)
					this.articulo_compra.nombre = resultado.inventario.name;

					this.articulo_compra.sub_total = resultado.inventario.precio.sub_total_menor
					this.articulo_compra.iva = resultado.inventario.precio.iva_menor
					this.articulo_compra.total = resultado.inventario.precio.total_menor


					console.log(this.articulo_compra);
				}else{
					console.log("establecer_nombre");
					let resultado = this.inventario_completo.find(element => element.inventario.id == id)
					this.articulo.id = id;
					this.articulo.nombre = resultado.inventario.name;

					this.articulo.sub_total = resultado.inventario.precio.sub_total_menor
					this.articulo.iva = resultado.inventario.precio.iva_menor
					this.articulo.total = resultado.inventario.precio.total_menor

					this.cantidad_disponible = resultado.cantidad;
					this.precio_disponible = new Intl.NumberFormat("de-DE", {minimumFractionDigits: 2, maximumFractionDigits: 2}).format(resultado.inventario.precio.total_menor * this.dolar);

					console.log(this.precio_disponible);
				}

			},
			agregar_producto(compra){

				if (compra == "compra") {

					this.articulo_compra.sub_total_unitario = this.articulo_compra.sub_total
					this.articulo_compra.iva_unitario = this.articulo_compra.iva
					this.articulo_compra.total_unitario = this.articulo_compra.total

					this.articulo_compra.sub_total *= this.articulo_compra.cantidad
					this.articulo_compra.iva *= this.articulo_compra.cantidad
					this.articulo_compra.total *= this.articulo_compra.cantidad

					this.productos_comprar.push(this.articulo_compra);


					this.articulo_compra = {nombre: "", cantidad: "", sub_total: "", iva: "", total: "", unidad: "", costo: null, iva_porc: null, margen_ganancia: null};
				}else{
					this.selectedValue = null
					this.articulo.sub_total *= this.articulo.cantidad
					this.articulo.iva *= this.articulo.cantidad
					this.articulo.total *= this.articulo.cantidad

					//AGREGAR PRECIO DOLAR AQUI
					this.articulo.sub_total *= this.dolar
					this.articulo.iva *= this.dolar
					this.articulo.total *= this.dolar

					this.productos.push(this.articulo);
					console.log(this.productos)

					//console.log(this.productos)
					this.articulo = {id: 0, nombre: "", cantidad: "", sub_total: "", iva: "", total: ""};

					this.cantidad_disponible = "";
					this.precio_disponible = "";
				}
			},
			eliminar(index, comprar){
				if (comprar == "comprar") {
					this.productos_comprar.splice(index, 1);
				}else{
					this.productos.splice(index, 1);
				}

			},
			venderTec(){
				if (this.productos.length > 0) {
					this.vender();
				}
			},
			vender(){
				this.error = false;
				console.log('estoy en el vender');
				console.log(this.productos);
				// console.log(
				// 		 this.sub_total,
				// 	 	 this.iva,
				// 	  	 this.total,
				// 	    this.type

				// 	)
				//AGREGAR PRECIO DOLAR AQUI
				axios.post('http://localhost/pisos_de_venta/public/api/ventas',
					{venta:
						{
							sub_total: this.sub_total,
						 	iva: this.iva_total,
						  total: this.total,
							divisa: this.checked_divisa,
						  type: this.type
						},
						productos:this.productos
					}).then(response => {
					console.log("en el response",response)
					this.productos = [];
					console.log("hice el post")
					if (response.data.errors != null) {//COMPROBAR SI HAY ERRORES DE INSUFICIENCIA DE PRODUCTOS
						this.error_message = response.data.errors
						console.log('el mensaje',this.error_message)
						this.error = true;
						this.showAlert();
						//window.location="http://localhost/pisos_de_venta/public/ventas/create";

					}else{
						this.cantidad_disponible = null
						this.precio_disponible = null
					Swal.fire({
					  	position: 'top-end',
					  	icon: 'success',
					  	title: 'Producto vendido exitosamente',
					  	showConfirmButton: false,
					  	timer: 1500
					})
					setTimeout(function () {
						//window.location = "/pisos_de_venta/public/ventas/create";
					}, 1500);
					//window.location = "/ventas/create";
					// setTimeout((1500) => {
					// 	  window.location = "/ventas/create";
					// }, ms)


				 }
				}).catch(e => {
					console.log('se genero un error')
					// this.error_message = e.data
					this.error = true;
					this.showAlert();
					console.log(e)
				})


			},
			countDownChanged(dismissCountDown) {//MODAL
		        this.dismissCountDown = dismissCountDown
		    },
			showAlert() {//MODAL
		    	this.dismissCountDown = this.dismissSecs
		    },
		    funcionando(){
		    	console.log("funcionando");
		    }
		},
		created(){

			this.get_dolar();
			this.get_datos();
		},
		computed:{
			sub_total_total(){
				let subtotal = 0;

				this.productos.forEach(producto => {

					subtotal += producto.sub_total

				})
				this.sub_total = subtotal;

				return subtotal;
			},
			iva_total(){
				let iva = 0;

				this.productos.forEach(producto => {

					iva += producto.iva;
				})

				this.iva = iva;

				return iva;
			},
			// mostar_iva_total(){
			// 	let n = new Intl.NumberFormat("en-IN", {maximumSignificantDigits: 2}).format(this.iva_total)
			// 	return n
			// },
			mostar_sub_total(){
				//AGREGAR PRECIO DOLAR AQUI
				let n = new Intl.NumberFormat("de-DE", {minimumFractionDigits: 2}).format(this.sub_total_total*this.dolar)
				//let a = n +",00"
				return n
			},
			mostar_total_total(){
				console.log("funcion mostrar_total_total");
				console.log(this.total_total);
				//AGREGAR PRECIO DOLAR AQUI
				let n = new Intl.NumberFormat("de-DE", {minimumFractionDigits: 2}).format(this.total_total)
				//let a = n +",00"
				return n
			},
			mostar_descuento(){
				console.log("funcion mostrar_total_total");
				console.log(this.total_total);
				//AGREGAR PRECIO DOLAR AQUI
				let n = new Intl.NumberFormat("de-DE", {minimumFractionDigits: 2}).format(this.total_total*0.03)
				//let a = n +",00"
				return n
			},
			mostar_total_descuento(){
				console.log("funcion mostrar_total_total");
				console.log(this.total_total);
				let total = this.total_total-(this.total_total*0.03);
				//AGREGAR PRECIO DOLAR AQUI
				let n = new Intl.NumberFormat("de-DE", {minimumFractionDigits: 2}).format(total)
				//let a = n +",00"
				return n
			},

			total_total(){
				let total = 0;

				this.productos.forEach(producto => {

					total += producto.total

				})
				this.total = total

				return total;
			},
			agregar_producto_enteritos: function(){
				if (this.articulo.id != 0 && this.articulo.cantidad != ""){

					this.agregar_producto();
				}
			},
			disabled_venta(){

				var validation = parseFloat(this.cantidad_disponible)-parseFloat(this.articulo.cantidad);

				if (this.articulo.id != 0 && this.articulo.cantidad != "" && validation >= 0 && this.articulo.cantidad > 0){

					return false;
				}else{
					return true;
				}

				/*if (this.articulo.id != 0 && this.articulo.cantidad != ""){

					return false;
				}else{
					return true;
				}*/
			}
		},
		watch:{
			selectedValue: function (val) {
				if (val != null) {
					this.establecer_nombre(val.value)
		      		console.log(val.value)
	      		}
	    	}
		}
	}
</script>
