<template>
	<div>
		<div class="container">
			<!--ALERT DE EXITO-->
		    <b-alert show variant="success" fade dismissible v-if="alert_success == true">{{alert_message}}</b-alert>
		    <b-alert show variant="success" fade dismissible v-if="sincro_exitosa == true">sincronozacion exitosa</b-alert>
		    <b-alert show variant="danger" fade dismissible v-if="error == true">ah ocurrido un error</b-alert>

			<div class="row">
				<div class="col-md-3">
					<div class="card shadow">
						<div class="card-header text-center">
							<span>Sincronizar Inventario</span>
						</div>
						<div class="card-body">
							<div v-if="piso_venta_selected.length != 0" style="font-size: 1em;" class="mt-3">
								<span><span class="font-weight-bold">PV:</span> {{piso_venta_selected.nombre}}</span> <br>
								<!-- <span><span class="font-weight-bold">Lugar:</span> {{piso_venta_selected.ubicacion}}</span> <br> -->
								<span><span class="font-weight-bold">Productos:</span> {{this.total_productos}}</span> <br>
								<!--<span><span class="font-weight-bold">Caja:</span> {{formattedCurrencyValue}}</span> <br>-->

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

							<button class="btn btn-primary btn-block" v-b-modal.modal-auditoria>

							<span v-if="loading_aud == false">Auditoria</span>
							<div class="spinner-border text-light text-center" role="status" v-if="loading_aud == true">
							  	<span class="sr-only">Cargando...</span>
							</div>
							</button>

							<!-- MODAL AUDITORIA -->
							<b-modal id="modal-auditoria" size="sm" title="Modificar cantidad" @ok="handleOk">
					      <div class="d-block text-center">
									<p>¿Esta segura que desea proceder con la auditoria?</p>
					      </div>
								<template #modal-footer="{ ok, cancel }">
						      <!-- Emulate built in modal footer ok and cancel button actions -->
						      <b-button size="sm" variant="success" @click="ok()">
						        Continuar
						      </b-button>
						      <b-button size="sm" variant="danger" @click="cancel()">
						        Cancelar
						      </b-button>
						    </template>
					    </b-modal>
							<!-- FIN MODAL AUDITORIA -->

							<!--<button class="btn btn-warning btn-block" @click="precios">Precios</button>-->
					</div>
				</div>
			</div>
			<div class="col-md-9">
				<div class="card shadow">
				<div class="card-body">
					<h1 class="text-center">Inventario</h1>
					<div class="mb-3 row justify-content-between">
						<div class="col-md-3">
							<!--<button class="btn btn-primary" type="button" @click="refrescar">Refrescar</button>-->
						</div>
						<div class="col-md-7">
							<div class="form-inline">
								<div class="form-group">
									<input type="text" v-model="search" class="form-control d-inline" placeholder="Buscar producto" @change="get_inventario">
									<button type="button" class="btn btn-primary" @click="get_inventario">Buscar</button>
								</div>

							</div>
						</div>

					</div>

					<table class="table table-bordered table-sm table-hover table-striped">
						<thead>
							<tr>
								<th rowspan="">Producto</th>
								<th>Cantidad</th>
								<th>Precio</th>
								<th>Acciones</th>
							</tr>
						</thead>
						<tbody>
							<tr v-for="(producto, index) in productos" :key="index">
								<td>{{producto.inventario.name}}</td>
								<td>{{producto.cantidad}}</td>
								<td>{{new Intl.NumberFormat("de-DE", {minimumFractionDigits: 2}).format(producto.inventario.precio.total_menor*dolar)}}</td>
								<td>
									<button type="button" class="btn btn-primary" @click="showModalDetalles(producto.id)">Ver</button>
									<!--<button type="button" class="btn btn-primary" data-toggle="modal" data-target="#verDetalles">Detalles</button>-->
								</td>

								<!-- Modal PARA VER LOS DETALLES -->
								<b-modal :id="'modal-detalles-'+producto.id" size="lg" :title="'Detalles del Producto '+producto.inventario.name">

									<table class="table table-bordered table-sm">

										<thead>
											<tr>
												<th>Propiedades</th>
												<th>Valores</th>

											</tr>
										</thead>

										<tbody>
											<tr>
												<th>Cantidad</th>
												<td>{{producto.cantidad}} {{producto.inventario.unit_type_menor}}</td>

											</tr>

											<tr>
												<td>Total</td>
												<td>{{new Intl.NumberFormat("de-DE", {minimumFractionDigits: 2}).format(producto.inventario.precio.total_menor*dolar)}}</td>

											</tr>


										</tbody>
									</table>

								</b-modal>

							</tr>

							<tr v-if="productos == []">
								<td class="text-center">No hay productos registrados</td>
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

		   <!---->


		</div>
	</div>
</template>

<script>


	export default{
		components: {

		},
		data(){
			return{
				productos: [],
				sincronizacion:'',
				loading:false,
				loading_aud:false,
				id:'',
				error: false,
				piso_venta_selected:[],
				dolar:0,
				page: "",
				currentPage: 0,
				per_page: 0,
				total_paginas: 0,
				total_productos: 0,
				alert_success: false,
				alert_message: "",
				id: 0,
				search: null,
				sincro_exitosa:false,
				sincron:{
		        	precios: false,
		        	despachos: false,
		        	ventas: false,
		        	monto: false,
		        	vaciar_caja: false,
		        	anulados: false,
		        	sincronizacion: false
		        },
			}
		},
		methods:{
			showModalDetalles(id){
				this.$bvModal.show("modal-detalles-"+id)
			},
			handleOk(){
				this.cambiar_aud();
				console.log("empieza audotioria");
				axios.post('http://mipuchito.com/api/auditoria', {id: this.id}).then(response => {
					console.log(response);
					let productos = response.data;
					axios.post('http://localhost/pisos_de_venta/public/api/auditoria', {idpisoventa: this.id, productosauditoria: productos}).then(response => {
						console.log(response.data);
						if (response.data) {
							this.cambiar_aud();
							//window.location="http://localhost/pisos_de_venta/public/inventario";
						}
						//this.sincro_exitosa = true
					}).catch(e => {
						console.log(e.response)
						this.error = true;
						this.cambiar_aud();
					});
				}).catch(e => {
					console.log(e.response)
					this.error = true;
					this.cambiar_aud();
				});
			},
			sincronizar(){
				this.sincron = {
		        	precios: false,
		        	despachos: false,
		        	ventas: false,
		        	monto: false,
		        	vaciar_caja: false,
		        	sincronizacion: false
		        }

		        this.error = false;

		        this.loading = false;

				this.cambiar()


				//PRECIOS
				//ULTIMO PRODUCTO DE INVENTORY REGISTRADO
				axios.get('http://localhost/pisos_de_venta/public/api/ultimo-inventory').then(response => {
					//console.log(response.data)
					let ultimoInventory = response.data
					//TRAEMOS DE LA WEB TODOS LOS PRODUCTOS APARTIR DEL ULTIMO ID
					//axios.get('http://mipuchitoex.com/api/get-inventory/'+ultimoInventory).then(response => {//WEB
					axios.get('http://mipuchito.com/api/get-inventory/'+ultimoInventory).then(response => {//WEB

						//console.log(response)
						let productos = response.data
						//REGISTRAMOS LOS NUEVOS PRODUCTOS
						if (productos.length > 0) {
							//console.log(productos);
							console.log("hay que registrar")
							axios.post('http://localhost/pisos_de_venta/public/api/registrar-inventory', {productos: productos}).then(response => {

								console.log(response.data);
								if (response.data == true) {
									console.log("productos registrados exitosamente")

									//SINC
									this.sincron.precios = true;
								}
							}).catch(e => {
								console.log(e.response)
								this.error = true;
								this.cambiar()
							});

						}else{
							console.log("no hay productos para registrar")
						}

						//ACTUALIZAMOS LOS PRECIOS
						//ANCLAR PRODUCTOS A UN INVENTORY_ID
						//OBTENEMOS DE LOS PISOS TODOS LOS QUE INVENTORY_ID == NUll
						axios.get('http://localhost/pisos_de_venta/public/api/get-inventories-id').then(response => {
							console.log(response);
							let inventario = response.data;

							if (inventario.length > 0) {
								//LOS BUSCAMOS EN LA WEB A VER SI SE LE ASIGNO UN INVENTORY_ID
								axios.post('http://mipuchito.com/api/get-inventories', {inventario: inventario, piso_venta: this.id}).then(response => {

									console.log(response);
									let nuevoInventario = response.data;
									//ACTUALIZAMOS EN LOCAL LOS INVENTORY_ID
									axios.post('http://localhost/pisos_de_venta/public/api/actualizar-inventory-id', {inventario: nuevoInventario}).then(response => {

										console.log(response);
										//ACTUALIZAMOS LOS PRECIOS

										axios.get('http://mipuchito.com/api/get-precios-inventory/'+this.id).then(response => {//WEB

											console.log(response)
											let inventory = response.data.inventory
											let inventario = response.data.inventario

											axios.post('http://localhost/pisos_de_venta/public/api/actualizar-precios-inventory', {productos: inventory, precios: inventario}).then(response => {

												console.log(response.data)
												//SINC
												this.sincron.precios = true;
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

									}).catch(e => {
										console.log(e.response)
									});

								}).catch(e => {
									console.log(e.response)
								});
							}else{
								console.log("no hay productos para anclar")
								//ACTUALIZAMOS LOS PRECIOS

								axios.get('http://mipuchito.com/api/get-precios-inventory/'+this.id).then(response => {//WEB

									console.log(response)
									let inventory = response.data.inventory
									let inventario = response.data.inventario

									axios.post('http://localhost/pisos_de_venta/public/api/actualizar-precios-inventory', {productos: inventory, precios: inventario}).then(response => {

										console.log(response.data)
										//SINC
										this.sincron.precios = true;
										this.get_inventario();
			   							this.get_id();
									 	this.cambiar()
										axios.post('http://mipuchito.com/api/sincronizacion', {id: this.id}).then(response => {
											axios.post('http://localhost/pisos_de_venta/public/api/sincronizacion', {id: this.id}).then(response => {
		       							this.sincro_exitosa = true
		       							window.location="http://localhost/pisos_de_venta/public/inventario";
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
							}
						}).catch(e => {
							console.log(e.response)
						});
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


		    },// fin del sincronizar
		    cambiar(){
				console.log("btn cambio")
				this.loading = !this.loading;
			},
			cambiar_aud(){
				this.loading_aud = !this.loading_aud;
			},
			get_dolar() {
				axios.get('http://localhost/pisos_de_venta/public/api/get-dolar').then(response =>{
					console.log(response)
					this.dolar = response.data.dolar;
				}).catch(e => {
					console.log(e.response);
				});
			},
			get_piso_venta(){

				axios.get('http://localhost/pisos_de_venta/public/api/get-piso-venta').then(response =>{
					console.log(response)
					this.piso_venta_selected = response.data.piso_venta;
					this.sincronizacion = response.data.sincronizacion.created_at;

				}).catch(e => {
					console.log(e.response);
				});
			},
			get_inventario(){

				axios.get('http://localhost/pisos_de_venta/public/api/get-inventario', {params:{search: this.search}}).then(response => {
					//console.log(response.data);
					this.per_page = response.data.per_page;
					this.total_paginas = response.data.total;
					this.total_productos = response.data.total;
					this.productos = response.data.data
					console.log("total productos");
					console.log(this.total_productos)
				}).catch(e => {
					console.log(e.response)
				});
			},
			paginar(event){

				axios.get('http://localhost/pisos_de_venta/public/api/get-inventario?page='+event).then(response => {
					console.log(response.data)
					this.per_page = response.data.per_page;
					this.total_paginas = response.data.total;
					this.productos = response.data.data

				}).catch(e => {
					console.log(e.response)
				});
			},
			refrescar(){
				this.alert_success = false;
				//ULTIMO PRODUCTO DE INVENTORY REGISTRADO
				axios.get('http://localhost/pisos_de_venta/public/api/ultimo-inventory').then(response => {
					//console.log(response.data)
					let ultimoInventory = response.data
					//TRAEMOS DE LA WEB TODOS LOS PRODUCTOS APARTIR DEL ULTIMO ID
					axios.get('http://mipuchito.com/api/get-inventory/'+ultimoInventory).then(response => {//WEB
						console.log('respues');
						console.log(response)
						let productos = response.data
						console.log(productos);
						//REGISTRAMOS LOS NUEVOS PRODUCTOS
						if (productos.length > 0) {

							console.log("hay que registrar")
							axios.post('http://localhost/pisos_de_venta/public/api/registrar-inventory', {productos: productos}).then(response => {
								console.log(response);
								if (response.data == true) {
									console.log("productos registrados exitosamente")
									this.alert_message = "productos registrados exitosamente"
									this.alert_success = true
								}
							}).catch(e => {
								console.log(e.response)
							});

						}else{
							console.log("no hay productos para registrar")
						}

						//ACTUALIZAMOS LOS PRECIOS

						axios.get('http://mipuchito.com/api/get-precios-inventory').then(response => {//WEB

							console.log(response)
							let articulos = response.data

							axios.post('http://localhost/pisos_de_venta/public/api/actualizar-precios-inventory', {productos: articulos}).then(response => {

								console.log(response.data)
							}).catch(e => {
								console.log(e.response)
							});
						}).catch(e => {
							console.log(e.response)
						});
					}).catch(e => {
						console.log(e.response)
					});

				}).catch(e => {
					console.log(e.response)
				});

				//SICRONIZACION
				axios.post('http://localhost/pisos_de_venta/public/api/sincronizacion', {id: this.id}).then(response => {
					console.log(response);

					axios.post('http://localhost/pisos_de_venta/public/api/sincronizacion', {id: this.id}).then(response => {//WEB
						console.log(response);

					}).catch(e => {
						console.log(e.response);
					});

				}).catch(e => {
					console.log(e.response);
				});
			},
			get_id(){

				axios.get('http://localhost/pisos_de_venta/public/api/get-id').then(response => {

					this.id = response.data;

				}).catch(e => {
					console.log(e.response)
				});
			}
		},
		computed:{
			formattedCurrencyValue(){
       	 		if(!this.piso_venta_selected.dinero){
       	 		 return "0.00"
       	 		}
           	 	let n = new Intl.NumberFormat("de-DE", {minimumFractionDigits: 2}).format(this.piso_venta_selected.dinero)
				let a = "Bs " + n; //+",00"
				return a
        	},
		},
		mounted(){
			//console.log(this.productos)
			this.get_inventario();
			this.get_id();
			this.get_piso_venta();
			//this.get_dolar();

		},
		created(){
			this.get_dolar();
		}

	}
</script>
