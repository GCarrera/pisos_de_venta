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

							<button class="btn btn-primary btn-block" v-b-modal.modal-auditoriap>
								<span v-if="loading_audp == false">Auditoria Parcial</span>
								<div class="spinner-border text-light text-center" role="status" v-if="loading_audp == true">
								  	<span class="sr-only">Cargando...</span>
								</div>
							</button>

							<!-- MODAL AUDITORIA -->
							<b-modal id="modal-auditoriap" size="sm" title="Modificar cantidad" @ok="handleOkp">
								<div class="d-block text-center">
									<p>¿Esta segur@ que desea proceder con la auditoria parcial?</p>
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
							
							<button class="btn btn-primary btn-block" v-b-modal.modal-auditoria>
								<span v-if="loading_aud == false">Auditoria Total</span>
								<div class="spinner-border text-light text-center" role="status" v-if="loading_aud == true">
								  	<span class="sr-only">Cargando...</span>
								</div>
							</button>

							<!-- MODAL AUDITORIA -->
							<b-modal id="modal-auditoria" size="sm" title="Modificar cantidad" @ok="handleOk">
								<div class="d-block text-center">
									<p>¿Esta segura que desea proceder con la auditoria total?</p>
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
				loading_audp:false,
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
				axios.post('http://www.mipuchito.com/api/auditoria', {id: this.id}).then(response => {
					console.log("data del servidor con inventario del pv y productos borrados");
					console.log(response);
					let productos = response.data.productos;
					let softdeletes = response.data.softdeletes;
					let cantidades = response.data.cantidades;
					axios.post('http://localhost/pisos_de_venta/public/api/auditoria', {idpisoventa: this.id, productosauditoria: productos, softdeletes: softdeletes, cantidades: cantidades}).then(response => {
						console.log('Actualizar inventory products y sofdelete');
						console.log(response);
						if (response.data) {
							this.cambiar_aud();
							window.location="http://localhost/pisos_de_venta/public/inventario";
							console.log("EXITO");
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
			handleOkp(){
				this.cambiar_audp();
				console.log("empieza audotioria parcial");
				axios.post('http://www.mipuchito.com/api/auditoriap', {id: this.id}).then(response => {
					console.log("data del servidor con cantidades del pv");
					console.log(response);
					let cantidades = response.data.cantidades;
					console.log(cantidades);
					axios.post('http://localhost/pisos_de_venta/public/api/auditoriap', {idpisoventa: this.id, cantidades: cantidades}).then(response => {
						console.log('Actualizar cantidades');
						console.log(response);
						if (response.data) {
							axios.post('http://www.mipuchito.com/api/auditorias', {id: this.id}).then(response => {
								console.log('Cambiando status en Prometheus');
								console.log(response);
								this.cambiar_aud();
								window.location="http://localhost/pisos_de_venta/public/inventario";
								console.log("EXITO");
							}).catch(e => {
								console.log(e.response)
								this.error = true;
								this.cambiar_aud();
							});							
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
				//ULTIMA FEHA DE CREATED AT LOCAL
				axios.get('http://localhost/pisos_de_venta/public/api/ultimo-inventory').then(response => {
					console.log("ultimas fechas created_at y updated_at");
					console.log(response)
					let ultimoInventory = response.data
					//TRAEMOS DE LA WEB TODOS LOS PRODUCTOS APARTIR DEL ULTIMO ID
					//axios.get('http://mipuchito.com/api/get-inventory/'+ultimoInventory).then(response => {//WEB
					axios.post('http://www.mipuchito.com/api/get-inventorybk', {id: ultimoInventory}).then(response => {//WEB
						console.log("productos nuevos y productos actualizados");
						console.log(response)
						var productosCreated = response.data.created
						var productosUpdated = response.data.updated
						var preciosUpdated = response.data.productUpdated
						var productosDeleted = response.data.deleted
						//REGISTRAMOS LOS NUEVOS PRODUCTOS
						if (productosCreated.length > 0) {
							//console.log(productos);
							console.log("hay que registrar")
							this.alert_success = true
							this.alert_message = "Registrando Productos Nuevos..."
							axios.post('http://localhost/pisos_de_venta/public/api/registrar-inventory', {productos: productosCreated}).then(response => {
								console.log("dentro del axios registrar");

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
							console.log("no hay productos nuevos")
							this.alert_success = true
							this.alert_message = "No hay Productos Nuevos..."
						}

						if (productosUpdated.length > 0) {
							console.log("hay que actualizar");
							console.log(productosUpdated);
							this.alert_success = true
							this.alert_message = "Actualizando Datos de Prodructos..."
							axios.post('http://localhost/pisos_de_venta/public/api/actualizar-inventory', {productos: productosUpdated}).then(response => {
								console.log("dentro del axios");

								console.log(response);
								if (response.data == true) {
									console.log("productos actualizados exitosamente")

									//SINC
									this.sincron.precios = true;
									//this.sincron.precios = true;

								}
							}).catch(e => {
								console.log(e.response)
								this.error = true;
								this.cambiar()
							});

						}else{
							console.log("no hay productos actualizados")
							this.alert_success = true
							this.alert_message = "No hay Actualizaciones en los Productos..."
						}

						if (preciosUpdated.length > 0) {
							console.log("hay que actualizar precios");
							console.log(preciosUpdated);
							this.alert_success = true
							this.alert_message = "Actualizando Precios de Prodructos..."
							axios.post('http://localhost/pisos_de_venta/public/api/actualizar-products', {precios: preciosUpdated}).then(response => {
								console.log("dentro del axios");

								console.log(response);
								if (response.data == true) {
									console.log("productos actualizados exitosamente")

									//SINC
									this.sincron.precios = true;
									//this.sincron.precios = true;

								}
							}).catch(e => {
								console.log(e.response)
								this.error = true;
								this.cambiar()
							});

						}else{
							console.log("no hay productos actualizados por fecha update")
							console.log("Ronda de Verificacion");
							this.alert_success = true
							this.alert_message = "Verificando ultimos detalles de precios..."

							axios.post('http://localhost/pisos_de_venta/public/api/all-product-price').then(response => {
								console.log("Data del PV a comparar");
								var dataPrecios = response.data;
								console.log(dataPrecios);

								axios.post('http://www.mipuchito.com/api/all-product-price', {id: this.id, data: dataPrecios}).then(response => {
									console.log("Respuesta del servidor respecto a diferecias en precios")
									console.log(response);
									if (response.data.length > 0) {
										console.log("Si hay productos pendientes se actualizan")
										axios.post('http://localhost/pisos_de_venta/public/api/verify-product-price', {id: this.id, precios: response.data}).then(response => {
											console.log(response);
										}).catch(e => {
											console.log(e.response)
											this.error = true;
											this.cambiar()
										});										
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

						}

						if (productosDeleted.length > 0) {
							console.log("hay que eliminar productos");
							console.log(productosDeleted);
							this.alert_success = true
							this.alert_message = "Hay que eliminar algunos productos..."
							axios.post('http://localhost/pisos_de_venta/public/api/borrar-inventory', {productos: productosDeleted}).then(response => {
								console.log("dentro del axios");

								console.log(response);
								if (response.data == true) {
									console.log("productos eliminados exitosamente")

									//SINC
									this.sincron.precios = true;
									//this.sincron.precios = true;

								}
							}).catch(e => {
								console.log(e.response)
								this.error = true;
								this.cambiar()
							});

						}else{
							console.log("no hay productos eliminados")
						}

						if (this.error == false) {

							axios.post('http://www.mipuchito.com/api/sincronizacion', {id: this.id}).then(response => {
								axios.post('http://localhost/pisos_de_venta/public/api/sincronizacion', {id: this.id}).then(response => {
									this.alert_success = false
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


		    },// fin del sincronizar

		  cambiar(){
				console.log("btn cambio")
				this.loading = !this.loading;
			},

			cambiar_aud(){
				this.loading_aud = !this.loading_aud;
			},
			cambiar_audp(){
				this.loading_audp = !this.loading_audp;
			},
			get_dolar() {
				axios.get('http://localhost/pisos_de_venta/public/api/get-dolar').then(response =>{
					console.log(response)
					this.dolar = response.data.dolar;
				}).catch(e => {
					console.log(e.response);
					//location.reload();
				});
			},
			get_piso_venta(){

				axios.get('http://localhost/pisos_de_venta/public/api/get-piso-venta').then(response =>{
					console.log(response)
					this.piso_venta_selected = response.data.piso_venta;
					this.sincronizacion = response.data.sincronizacion.created_at;

				}).catch(e => {
					console.log(e.response);
					//location.reload();
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
