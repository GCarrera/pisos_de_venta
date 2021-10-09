<template>
	<div>
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
			<b-progress
			variant="warning"
			:max="dismissSecs"
			:value="dismissCountDown"
			height="4px"
			></b-progress>
		</b-alert>
		<!---->
		<!--ALERT DE EXITO-->
		<b-alert show variant="success" fade dismissible v-if="alert_success == true">{{alert_message}}</b-alert>
		<!---->
		<b-alert show variant="success" fade dismissible v-if="sincro_exitosa == true">sincronozacion exitosa</b-alert>
		<!--<b-alert show variant="warning" fade dismissible v-if="sin_ventas == true"> no se han detectado ventas</b-alert>-->
		<b-alert show variant="danger" fade dismissible v-if="error == true">ah ocurido un error</b-alert>

		<div class="row">
			<div class="col-md-3">
				<div class="card shadow">
					<div class="card-header text-center">
						<span>Sincronizar Ventas</span>
					</div>
					<div class="card-body">
						<div v-if="piso_venta_selected.length != 0" style="font-size: 1em;" class="mt-3">
							<span><span class="font-weight-bold">PV:</span> {{piso_venta_selected.nombre}}</span> <br>
							<!-- <span><span class="font-weight-bold">Lugar:</span> {{piso_venta_selected.ubicacion}}</span> <br> -->
							<!--<span><span class="font-weight-bold">Ventas Realizadas:</span> {{formattedCurrencyValue}}</span> <br>-->
							<span><span class="font-weight-bold">Ventas Realizadas:</span> {{count.ventas}}</span> <br>

						</div>
						<hr>
						<span class="font-weight-bold" > Ultima Actualización: </span> <span v-if="sincronizacion !== null">{{sincronizacion}}</span> <br>
						<!-- <span class="font-weight-bold" >Ultima vez que vacio la caja: </span><span  v-if="caja !== null">{{caja}}</span> <br> -->
						<hr>
						<button class="btn btn-primary btn-block" @click="sincronizar" :disabled="loading">

							<span v-if="loading == false">Sincronizar</span>
							<div class="spinner-border text-light text-center" role="status" v-if="loading == true">
								<span class="sr-only">Loading...</span>
							</div>
						</button>

						<!--<button class="btn btn-warning btn-block" @click="precios">Precios</button>-->
					</div>
				</div>
			</div>
			<div class="col-md-9">

				<div class="card shadow">
					<div class="card-body">
						<h1 class="text-center">Historial de Ventas</h1>
						<div class="mb-3">
							<div class="row justify-content-between">
								<div class="col-12 col-md-2">
									<!--<button class="btn btn-primary" @click="refrescar">Sincronizar</button>-->
									<!--<button type="button" @click="sync_anular" class="btn btn-primary">sync anular</button>-->
								</div>

								<div class="col-12 col-md-4">
<!--
<button type="button" class="btn btn-danger" @click="showModalCompra">Compra</button>
<button type="button" class="btn btn-primary" @click="showModalNuevo">Venta</button>
-->
</div>

</div>
</div>

<div class="text-right my-3">
	<form action="" method="get" @submit.prevent="filtrar">
		<input type="date" v-model="fecha_inicial">
		<input type="date" v-model="fecha_final" >
		<button type="submit" class="btn btn-primary">Filtrar</button>
	</form>

</div>
<table class="table table-bordered table-sm table-hover table-striped">
	<thead>
		<tr>
			<th>Factura</th>
			<th>Fecha</th>
			<th>Total</th>
			<th>Acciones</th>
		</tr>
	</thead>
	<tbody>
		<tr v-for="(venta, index) in ventas" :key="index" :class="{'bg-danger': venta.anulado == 0 || venta.anulado == 1, 'text-white': venta.anulado == 1 || venta.anulado == 0}">
			<td v-if="venta.type == 1">FC-00{{venta.id}}</td>
			<td v-if="venta.type == 1">{{venta.created_at}}</td>
			<td v-if="venta.pago == 1">
				<span class="text-success">
					{{new Intl.NumberFormat("de-DE", {minimumFractionDigits: 2}).format(venta.total-(venta.total*0.03))}}
				</span>
			</td>
			<td v-else>
				{{new Intl.NumberFormat("de-DE", {minimumFractionDigits: 2}).format(venta.total)}}
			</td>
			<td>
				<button type="button" class="btn btn-primary" @click="showModalDetalles(venta.id)">Ver</button>
				<b-button v-b-tooltip.hover title="Anular Venta" size="sm" @click="delmodal(venta, venta.id, $event.target)" class="mr-1" variant="danger">
					<b-icon-trash></b-icon-trash>
				</b-button>
				<!--<button class="btn btn-danger" type="button" @click="showModalAnular(venta.id)" v-if="venta.anulado == null">Anular</button>-->
			</td>

			<!-- Modal VER DETALLES, FACTURA -->
			<b-modal :id="'modal-detalles-'+venta.id" size="lg" title="Detalles de la venta">

				<table class="table table-bordered table-sm">
					<thead>
						<tr>
							<th>Producto</th>
							<th>Cantidad</th>
							<th>Precio</th>
							<th>Subtotal</th>
						</tr>
					</thead>

					<tbody>
						<tr v-for="(producto, index) in venta.detalle" :key="index">
							<td>{{producto.name}}</td>
							<td>{{producto.pivot.cantidad}}</td>
							<td>{{new Intl.NumberFormat("de-DE", {minimumFractionDigits: 2}).format(producto.pivot.total/producto.pivot.cantidad)}}</td>
							<td>{{new Intl.NumberFormat("de-DE", {minimumFractionDigits: 2}).format(producto.pivot.total)}}</td>
						</tr>
					</tbody>

				</table>

				<div class="row">
					<div class="col-md-6">

					</div>

					<div class="col-md-3 text-right">

						<span class="font-weight-bold">Total:</span><br>
						<span v-if="venta.pago == 1" class="font-weight-bold small">Descuento:</span><br>
						<span v-if="venta.pago == 1" class="font-weight-bold small">Total con Descuento:</span>


					</div>

					<div class="col-md-3">
						<span class="small"> BsS {{new Intl.NumberFormat("de-DE", {minimumFractionDigits: 2}).format(venta.total)}}</span><br>
						<span v-if="venta.pago == 1" class="small"> BsS {{new Intl.NumberFormat("de-DE", {minimumFractionDigits: 2}).format(venta.total*0.03)}}</span><br>
						<span v-if="venta.pago == 1"> BsS {{new Intl.NumberFormat("de-DE", {minimumFractionDigits: 2}).format(venta.total-(venta.total*0.03))}}</span>
					</div>

				</div>
			</b-modal>			

		</tr>

		<!-- Modal ANULAR VENTA -->
			<b-modal id="modalDel" size="sm" :title="delModal.title" @hide="resetDelModal" @ok="delOk" @cancel="delCancel">
				<template #default="{  }">
					<b-container fluid>
					<b-form ref="form" @submit.stop.prevent="delOk">
						<b-form-group
						label="Codigo"
						label-for="codDel"
						:invalid-feedback="feedbackDel"
						:state="delState"
						>

						<b-form-input
							id="codDel"
							v-model="codDel"
							:state="delState"
							type="password"
							required
							placeholder="*****"
							size="sm"
						></b-form-input>

						</b-form-group>

					</b-form>
					</b-container>
				</template>
				<template #modal-footer="{ ok, cancel }">
					<b-button size="sm" variant="primary" @click="ok(delModal.idDel)">
						Continuar
					</b-button>
					<b-button size="sm" @click="cancel()">
						Cancelar
					</b-button>
				</template>
			</b-modal>

		<tr v-if="ventas == []">
			<td class="text-center">No hay ventas registradas</td>
		</tr>
	</tbody>
</table>

<div class="overflow-auto">
	<b-pagination v-model="currentPage" @change="paginar($event)" :per-page="per_page"  :total-rows="total_paginas" size="sm"></b-pagination>
</div>

</div>
</div>
</div>
</div>
</div>
</div>
</template>

<script>
export default{
	data(){
		return{
			feedbackDel: "El Codigo no coincide",
			delState: null,
			codDel: "",
			delModal: {
				id: 'del-modal',
				title: '',
				content: '',
				idDel: ''
			},
			count:{
				ventas: 0,
				compras: 0,
				despachos: 0,
				retiros: 0
			},
			ventas: [],
			piso_venta_selected:[],
			loading:false,
			sincronizacion:'',
			sincron:{},
			inventario: [],
			productos: [],//LISTA DE PRODUCTOS QUE VOY A AGREGAR
			articulo: {
				id: 0,
				nombre: "",
				cantidad: "",
				sub_total: "",
				iva: "",
				total: ""
			},
			sub_total: 0,
			iva: 0,
			total: 0,
			type: "",
			piso_venta: "",
			currentPage: 0,
			per_page: 0,
			total_paginas: 0,
			cantidad_disponible: "",
			error: false,
			error_message: "",
			dismissSecs: 10,//MODAL
			dismissCountDown: 0,
			showDismissibleAlert: false,
			alert_success: false,
			sincro_exitosa:false,
			sin_ventas:false,
			error:false,
			id:'',
			alert_message: "",
			inventario_compra: [],
			articulo_compra: {
				nombre: "",
				cantidad: "",
				unidad: "",
				costo: null,
				iva_porc: null,
				margen_ganancia: null,
				sub_total: null,
				iva: null,
				total: null,
				sub_total_unitario: null,
				iva_unitario: null,
				total_unitario: null,
			},
			productos_comprar: [],
			sub_de_total: 0,
			iva_de_compra: 0,
			total_de_compra: 0,
			fecha_inicial: "",
			fecha_final: ""
		}
	},
	methods:{
		delmodal(item, index, button) {
			this.delModal.title = `Anular Venta: FC-00${item.id}`
			this.delModal.idDel = item.id
			this.$root.$emit('bv::show::modal', 'modalDel', button)
		},
		resetDelModal() {
			if (this.delState != false) {
				this.delModal.title = ''
				this.delModal.content = ''
				this.delModal.idDel = ''
				this.codDel = ''	
			}
		},
		delOk(bvModalEvt){
			var plus = parseFloat(this.codDel);
			if (this.codDel == 'Nostradamus') {
				console.log("Enviar peticion de anular");
				console.log(this.delModal.idDel);				

				axios.post('http://localhost/pisos_de_venta/public/api/negar-venta', {
					id: this.delModal.idDel
				})
				.then(response=>{
					console.log("anular exitoso");
					if (response.data != false) {
						console.log(response);
						window.location = "/admin/inventariov";
						location.reload();
					} else {
						console.log("No se puede borrar la venta es muy antigua");
					}
				}).catch(e => {
					console.log("error al anular");
					console.log(e.response)
				});				
			} else {
				this.delState = false
				bvModalEvt.preventDefault()
			}
		},
		delCancel(){
			this.delState = null
		},
		resumen_dia(){

			axios.get('http://localhost/pisos_de_venta/public/api/resumen-dia').then(response => {
				console.log("Respuesta resumen dia ");
				console.log(response.data);
				this.count = response.data;

			}).catch(e => {
				console.log(e.response)
			});
		},
		sync_anulados(){
			console.log("desde el metodo anulado")
			let ventas = [];
		   //ANULADOS
		   //OBTENEMOS LOS PRODCUTOS QUE HALLAN SIDO ANULADOS
			axios.get('http://localhost/pisos_de_venta/public/api/get-ventas-anuladas').then(response => {
				let ventas = response.data;
				if (ventas.length > 0) {
					//ACTUALIZAMOS LOS ANULADOS EN LA WEB
					//axios.post('http://mipuchitoex.com/api/actualizar-anulados', {ventas: ventas, piso_venta: this.id}).then(response => {//WEB
					axios.post('http://mipuchito.com/api/actualizar-anulados', {ventas: ventas, piso_venta: this.id}).then(response => {//WEB

					//VOLVEMOS A ACTUALIZAR EN LOCAL
					axios.post('http://localhost/pisos_de_venta/public/api/actualizar-anulados-local').then(response => {
						//SINC
						this.sincron.anulados = true
					}).catch(e => {
						console.log(e.response);
					});

				    }).catch(e => {
						console.log(e.response);
					});
				}else{
					//SINC
					this.sincron.anulados = true;
				}
			}).catch(e => {
				console.log(e.response);
			})
		},
		get_id(){

			axios.get('http://localhost/pisos_de_venta/public/api/get-id').then(response => {

				this.id = response.data;

			}).catch(e => {
				console.log(e.response)
			});
		},
		sincronizar(){
			this.cambiar()
			this.sincro_exitosa = false
			this.sin_ventas = false
			this.error = false
			//VENTAS

			//ACTUALIZAR MONTO EN LA WEB
			this.alert_success = true
			this.alert_message = "Actualizando dinero en Prometheus..."
			axios.put('http://www.mipuchito.com/api/actualizar-dinero-piso-venta/'+this.id, {dinero: this.piso_venta_selected.dinero}).then(response => {//EN LA WEB
				console.log(response);
				console.log('Actualizando dinero en Prometheus');
				//SINC
				//this.sincron.montos = true;
			}).catch(e => {
				console.log(e.response);
				this.error = true;
				this.cambiar()
			});

			//ACTUALIZAR VACIADAS DE CAJA

			//SOLICITAMOS EL ULTIMO QUE TENGA
			this.alert_success = true
			this.alert_message = "Buscando ultima caja vaciada en Prometheus..."
			axios.get('http://www.mipuchito.com/api/ultima-vaciada-caja/'+this.id).then(response => {//WEB
				console.log(response);
				console.log('Buscando ultima caja vaciada en Prometheus')
				let ultima_caja = response.data;
				if(ultima_caja == null){
					ultima_caja = 0;
				}
				//SOLICITAMOS LAS VACIADAS QUE TENGO EN LOCAL
				axios.get('http://localhost/pisos_de_venta/public/api/ultima-vaciada-caja-local/'+ultima_caja.id_extra).then(response => {
					console.log(response);
					console.log('Ultima caja vaciada en local')
					let cajas = response.data;

					if (cajas.length > 0) {
						this.alert_success = true
						this.alert_message = "Actualizando cajas vaciadas en Prometheus..."
						axios.post('http://www.mipuchito.com/api/registrar-cajas', {cajas: cajas}).then(response => {//WEB
							console.log(response);
							console.log('Actualizando cajas vaciadas en Prometheus');							
						}).catch(e => {
							console.log(e.response)
							this.error = true;
							this.cambiar()
						});
					}else{
						this.alert_success = true
						this.alert_message = "No hay cajas vaciadas nuevas..."
						console.log('No hay cajas vaciadas nuevas');						

					}
				}).catch(e => {
					console.log(e.response)
					this.error = true;
					this.cambiar()
				});
			}).catch(e => {
				console.log(e.response)
				this.error = true;
				this.cambiar()
			});

			//OBTENEMOS MI ID DE PISO DE VENTA
			axios.get('http://localhost/pisos_de_venta/public/api/get-piso-venta-id').then(response => {
				console.log(response);
				let piso_venta_id = response.data;
				//OBTENEMOS DE LA WEB LA ULTIMA VENTA QUE TIENE REGISTRADA CON NUESTRO PISO DE VENTA
				this.alert_success = true
				this.alert_message = "Buscando la ultima venta registrada en Prometheus..."
				console.log('Buscando la ultima venta registrada en Prometheus '+piso_venta_id)
				axios.get('http://www.mipuchito.com/api/ultima-venta/'+piso_venta_id).then(response => {//WEB
					console.log(response);
					let ultima_venta = response.data.created_at
					console.log('Ultima venta guardada en prometheus');
					console.log(ultima_venta)				

					//OBTENEMOS TODAS LAS VENTAS QUE SEAN MAYOR AL ID_EXTRA QUE ACABO DE CONSEGUIR
					axios.get('http://localhost/pisos_de_venta/public/api/ventas-sin-registrar/'+piso_venta_id+'/'+ultima_venta).then(response => {
						console.log(response);
						console.log('Buscando ventas sin registrar')
						let ventas = response.data
						console.log(ventas);
						//VALIDACION SI TRAJO ALGUNA VENTA
						if (ventas.length > 0) {

							//EN ESE CASO REGISTRAMOS LAS VENTAS EN LA WEB
							this.alert_success = true
							this.alert_message = "Enviando nuevas ventas a Prometheus..."
							console.log('Enviando nuevas ventas a Prometheus')
							axios.post('http://www.mipuchito.com/api/registrar-ventas', {ventas: ventas, piso_venta_id: piso_venta_id}).then(response => {
								console.log(response);

								console.log('Ventas registradas exitosamente')
								this.sin_ventas = true

								axios.post('http://www.mipuchito.com/api/sincronizacion', {id: this.id}).then(response => {
									console.log(response);
									this.alert_success = true
									this.alert_message = "Terminando Sincronizacion..."
									axios.post('http://localhost/pisos_de_venta/public/api/sincronizacion', {id: this.id}).then(response => {
										console.log('Terminando Sincronizacion');
										console.log(response);
										//SINC
										//this.sincron.vaciar_caja = true;
										//this.sincro_exitosa = true;
										//this.cambiar()
										if (this.sin_ventas == true) {
											window.location="http://localhost/pisos_de_venta/public/ventas";										
										}
										//window.location="http://localhost/pisos_de_venta/public/ventas";

									}).catch(e => {
										console.log(e.response)
										this.error = true;
										this.cambiar()
									});
								}).catch(e => {
									console.log(e.response)
									this.error = true;
									this.cambiar()
								});
								if (response.data == true) {

									//SINC
									//this.sincron.ventas = true;
									//this.sync_anulados();
								}else{

									this.showAlert();
								}
							}).catch(e => {
								console.log(e.response)
								this.error = true;
								this.cambiar()
							})
						}else{
							this.alert_success = true
							this.alert_message = "No hay ventas nuevas que registrar..."
							console.log('No hay ventas nuevas que registrar');
							this.sin_ventas = true
							axios.post('http://www.mipuchito.com/api/sincronizacion', {id: this.id}).then(response => {
								console.log(response);
								this.alert_success = true
								this.alert_message = "Terminando Sincronizacion..."
								axios.post('http://localhost/pisos_de_venta/public/api/sincronizacion', {id: this.id}).then(response => {
									console.log('Sincronizar listo');
									console.log(response);
									console.log('Terminando Sincronizacion')
									//SINC
									//this.sincron.vaciar_caja = true;
									//this.sincro_exitosa = true;
									//this.cambiar()
									if (this.sin_ventas == true) {
										window.location="http://localhost/pisos_de_venta/public/ventas";										
									}
								}).catch(e => {
									console.log(e.response)
									this.error = true;
									this.cambiar()
								});
							}).catch(e => {
								console.log(e.response)
								this.error = true;
								this.cambiar()
							});
							//SINC
							//this.sincron.ventas = true;
							//this.sync_anulados();
						}
					}).catch(e => {
						console.log(e.response)
						this.error = true;
						this.cambiar()
					})
				}).catch(e => {
					console.log(e.response)
					this.error = true;
					this.cambiar()
				})

			}).catch(e => {
				console.log(e.response)
				this.error = true;
				this.cambiar()
			});			

		},
		cambiar(){
			console.log("btn cambio")
			this.loading = !this.loading;
		},
		get_piso_venta(){

			axios.get('http://localhost/pisos_de_venta/public/api/get-piso-venta').then(response =>{
				console.log('get-piso-venta');
				console.log(response)
				this.piso_venta_selected = response.data.piso_venta;
				this.sincronizacion = response.data.sincronizacion.created_at;

			}).catch(e => {
				console.log(e.response);
			});
		},
		get_ventas(){

			axios.get('http://localhost/pisos_de_venta/public/api/get-ventas').then(response => {
				console.log(response.data.data);
				this.per_page = response.data.per_page;
				this.total_paginas = response.data.total;
				this.ventas = response.data.data

				console.log(this.despachos)
			}).catch(e => {
				window.reload()
				console.log(e.response)
			});

		},
		get_datos(){
		//SOLICITO LOS PISOS DE VENTAS Y PRODUCTOS
		axios.get('http://localhost/pisos_de_venta/public/api/ventas-datos-create').then(response => {

			console.log(response);
			this.inventario = response.data
			this.inventario_compra = response.data
		}).catch(e => {

		});
		},
		showModalNuevo(){

			this.get_datos();
			this.$bvModal.show("modal-nuevo")
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
				let resultado = this.inventario.find(element => element.inventario.id == id)
				this.articulo.nombre = resultado.inventario.name;
				this.articulo.sub_total = resultado.inventario.precio.sub_total_menor
				this.articulo.iva = resultado.inventario.precio.iva_menor
				this.articulo.total = resultado.inventario.precio.total_menor
				this.cantidad_disponible = resultado.cantidad;
				console.log(this.articulo);
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

				this.articulo.sub_total *= this.articulo.cantidad
				this.articulo.iva *= this.articulo.cantidad
				this.articulo.total *= this.articulo.cantidad
				this.productos.push(this.articulo);
				this.articulo = {id: 0, nombre: "", cantidad: "", sub_total: "", iva: "", total: ""};
			}
		},
		eliminar(index, comprar){
			if (comprar == "comprar") {
				this.productos_comprar.splice(index, 1);
			}else{
				this.productos.splice(index, 1);
			}
		},
		paginar(event){

			axios.get('http://localhost/pisos_de_venta/public/api/get-ventas?page='+event).then(response => {
				console.log(response.data)
				this.per_page = response.data.per_page;
				this.total_paginas = response.data.total;
				this.ventas = response.data.data

			}).catch(e => {
				console.log(e.response)
			});
		},
		showModalDetalles(id){
			this.$bvModal.show("modal-detalles-"+id)
		},
		vender(){
			this.error = false;
			axios.post('http://localhost/pisos_de_venta/public/api/ventas', {venta: {sub_total: this.sub_total, iva: this.iva, total: this.total, type: this.type},productos: this.productos}).then(response => {
				console.log(response.data)
				if (response.data.errors != null) {//COMPROBAR SI HAY ERRORES DE INSUFICIENCIA DE PRODUCTOS
					this.error_message = response.data.errors
					this.error = true;
					this.showAlert();
					this.articulo = {id: 0, nombre: "", cantidad: "", sub_total: "", iva: "", total: ""};
					this.cantidad_disponible = ""
					this.productos = [];
				}else{
					this.articulo = {id: 0, nombre: "", cantidad: "", sub_total: "", iva: "", total: ""};
					this.cantidad_disponible = ""
					this.ventas.splice(0,0, response.data);
					this.productos = [];
				}

			}).catch(e => {

				console.log(e.response)
			})

			this.$bvModal.hide("modal-nuevo")
		},
		countDownChanged(dismissCountDown) {//MODAL
			this.dismissCountDown = dismissCountDown
		},
		showAlert() {//MODAL
			this.dismissCountDown = this.dismissSecs
		},
		refrescar(){
			this.alert_success = false;
			this.error = false
			//OBTENEMOS MI ID DE PISO DE VENTA
			axios.get('http://localhost/pisos_de_venta/public/api/get-piso-venta-id').then(response => {
				let piso_venta_id = response.data;
				//console.log(piso_venta_id)
				//OBTENEMOS DE LA WEB LA ULTIMA VENTA QUE TIENE REGISTRADA CON NUESTRO PISO DE VENTA
				axios.get('http://mipuchito.com/api/ultima-venta/'+piso_venta_id).then(response => {//WEB

					let ultima_venta = response.data.created_at
					console.log('Ultima venta guardada en prometheus');
					console.log(ultima_venta)
						//OBTENEMOS TODAS LAS VENTAS QUE SEAN MAYOR AL ID_EXTRA QUE ACABO DE CONSEGUIR
					axios.get('http://localhost/pisos_de_venta/public/api/ventas-sin-registrar/'+piso_venta_id+'/'+ultima_venta).then(response => {

						console.log(response.data)
						let ventas = response.data
						//VALIDACION SI TRAJO ALGUNA VENTA
						if (ventas.length > 0) {

							//EN ESE CASO REGISTRAMOS LAS VENTAS EN LA WEB
							axios.post('http://mipuchito.com/api/registrar-ventas', {ventas: ventas, piso_venta_id: piso_venta_id}).then(response => {

								console.log(response.data)
								if (response.data == true) {
									this.alert_message = "la sincronizacion fue exitosa";
									this.alert_success = true;
								}else{
									this.error_message = "Ha ocurrido un error."
									this.error = true;
									this.showAlert();
								}
							}).catch(e => {
								console.log(e.response)
							})
						}else{
							this.alert_message = "Usted esta al dia con la sincronizacion";
							this.alert_success = true;
						}
					}).catch(e => {
						console.log(e.response)
					})
				}).catch(e => {
					console.log(e.response)
				})
			}).catch(e => {
				console.log(e.response)
			});
			//SICRONIZACION
				axios.post('http://localhost/pisos_de_venta/public/api/sincronizacion', {id: piso_venta_id}).then(response => {
					console.log(response);

				axios.post('http://localhost/pisos_de_venta/public/api/sincronizacion', {id: piso_venta_id}).then(response => {//WEB
					console.log(response);

				}).catch(e => {
					console.log(e.response);
				});

			}).catch(e => {
				console.log(e.response);
			});
		},
		showModalCompra(){
			this.get_datos();
			this.$bvModal.show("modal-compra")
		},
		comprar(){

			this.error = false;
			axios.post('http://localhost/pisos_de_venta/public/api/ventas-comprar', {venta: {sub_total: this.sub_total_de_compra, iva: this.iva_de_compra, total: this.total_de_compra},productos: this.productos_comprar}).then(response => {
				console.log(response.data)

				this.articulo_compra = {nombre: "", cantidad: "", sub_total: "", iva: "", total: "", unidad: "", costo: null, iva_porc: null, margen_ganancia: null};
				this.ventas.splice(0,0, response.data);
				this.productos_comprar = [];

			}).catch(e => {

				console.log(e.response)
			})

			this.$bvModal.hide("modal-compra")
		},
		filtrar(){

			axios.get('http://localhost/pisos_de_venta/public/api/get-ventas', {params:{fecha_i: this.fecha_inicial, fecha_f: this.fecha_final}}).then(response => {
				console.log(response.data.data);
				this.per_page = response.data.per_page;
				this.total_paginas = response.data.total;
				this.ventas = response.data.data

				console.log(this.despachos)
			}).catch(e => {
				console.log(e.response)
			});
		},
		showModalAnular(id){

			$('#modal-anular-'+id).modal('show');
		},
		anular(id, index){

			axios.put('/api/anular/'+id).then(response => {
				console.log(response);
				this.ventas.splice(index, 1, response.data);
				console.log("anulado")
			}).catch(e => {
				console.log(e.response);
			});

			$('#modal-anular-'+id).modal('hide');
		},
		sync_anular(){

			//OBTENEMOS MI ID DE PISO DE VENTA
			axios.get('http://localhost/pisos_de_venta/public/api/get-piso-venta-id').then(response => {

				let piso_venta_id = response.data;
				//OBTENEMOS LOS PRODCUTOS QUE HALLAN SIDO ANULADOS
				axios.get('http://localhost/pisos_de_venta/public/api/get-ventas-anuladas').then(response => {

					let ventas = response.data;
					if (ventas.length > 0) {
					//ACTUALIZAMOS LOS ANULADOS EN LA WEB
						axios.post('http://mipuchito.com/api/actualizar-anulados', {ventas: ventas, piso_venta: piso_venta_id}).then(response => {//WEB

							console.log(response);
							//VOLVEMOS A ACTUALIZAR EN LOCAL
							axios.post('http://localhost/pisos_de_venta/public/api/actualizar-anulados-local', {ventas: ventas, piso_venta: piso_venta_id}).then(response => {

								console.log(response);

							}).catch(e => {
								console.log(e.response);
							});

						}).catch(e => {
							console.log(e.response);
						});
					}

				}).catch(e => {
					console.log(e.response);
				})

			}).catch(e => {
				console.log(e.response);
			})
		}
	},
	computed:{
		formattedCurrencyValue(){
       	 		if(!this.piso_venta_selected.dinero){
       	 		 return "0.00"
       	 		}
						return "Bs " + new Intl.NumberFormat("de-DE", {minimumFractionDigits: 2}).format(this.piso_venta_selected.dinero)
        },
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
		total_total(){
			let total = 0;

			this.productos.forEach(producto => {

				total += producto.total

			})
			this.total = total

			return total;
		},
		sub_total_comprar(){
			//SUB TOTAL
			let precioSinIva = ((this.articulo_compra.margen_ganancia * this.articulo_compra.costo) / 100) + this.articulo_compra.costo
			this.articulo_compra.sub_total = precioSinIva

			return this.articulo_compra.sub_total;
			},
		iva_total_comprar(){
			let precioSinIva = ((this.articulo_compra.margen_ganancia * this.articulo_compra.costo) / 100) + this.articulo_compra.costo
		//IVA
		let iva = (this.articulo_compra.iva_porc * precioSinIva) / 100
		this.articulo_compra.iva = iva
		return this.articulo_compra.iva;
		},
		total_comprar(){
			let precioSinIva = ((this.articulo_compra.margen_ganancia * this.articulo_compra.costo) / 100) + this.articulo_compra.costo
			let iva = (this.articulo_compra.iva_porc * precioSinIva) / 100
			//TOTAL
			let total = iva + precioSinIva
			this.articulo_compra.total = total
			return this.articulo_compra.total;
		},
		sub_total_total_comprar(){
			let subtotal = 0;

			this.productos_comprar.forEach(producto => {

				subtotal += producto.sub_total

			})
			this.sub_total_de_compra = subtotal;

			return subtotal;
		},
		iva_total_total_comprar(){
			let iva = 0;

			this.productos_comprar.forEach(producto => {

				iva += producto.iva;
			})

			this.iva_de_compra = iva;

			return iva;
		},
		total_total_comprar(){
			let total = 0;

			this.productos_comprar.forEach(producto => {

				total += producto.total

			})
			this.total_de_compra = total

			return total;
		},
		disabled_venta(){

			if (this.articulo.id != 0 && this.articulo.cantidad != ""){

				return false;
			}else{
				return true;
			}
		},
		disabled_compra(){

			if (this.articulo_compra.nombre != "" && this.articulo_compra.cantidad != "" && this.articulo_compra.unidad != "" && this.articulo_compra.costo != null && this.articulo_compra.iva_porc != null && this.articulo_compra.margen_ganancia != null){

				return false;
			}else{
				return true;
			}
		},
		},
		created(){
			this.get_id()
			this.get_ventas()
			this.get_piso_venta()
			this.resumen_dia();
		}
}
</script>
