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
							<span>Sincronizar Solicitudes</span>
						</div>
						<div class="card-body">
							<div v-if="piso_venta_selected.length != 0" style="font-size: 1em;" class="mt-3">

								<span><span class="font-weight-bold">PV:</span> {{piso_venta_selected.nombre}}</span> <br>
								<span><span class="font-weight-bold">Solicitudes:</span> {{this.total_solicitudes}}</span> <br>

							</div>
								<hr>
									<span class="font-weight-bold" > Ultima Actualización: </span> <span v-if="sincronizacion !== null">{{sincronizacion}}</span> <br>
								<hr>
							<button class="btn btn-primary btn-block" @click="sincronizar" :disabled="loading">

							<span v-if="loading == false">Sincronizar</span>
							<div class="spinner-border text-light text-center" role="status" v-if="loading == true">
							  	<span class="sr-only">Cargando...</span>
							</div>
							</button>

							<button class="btn btn-primary btn-block" v-b-modal.modal-solicitud>

							<span v-if="loading_sol == false">Solicitud</span>
							<div class="spinner-border text-light text-center" role="status" v-if="loading_sol == true">
							  	<span class="sr-only">Cargando...</span>
							</div>
							</button>

							<!-- MODAL AUDITORIA -->
							<b-modal id="modal-solicitud" size="md" title="Nueva Solicitud" @ok="handleOk">

								<b-container fluid>

									<b-form validate="true">

										<b-form-group
						          label="Nombre"
						          label-for="nombre-input"
						          invalid-feedback="El nombre del cliente es requerido"
						          :state="nameState"
						        >
						          <b-form-input
						            id="nombre-input"
						            v-model="nombre"
						            :state="nameState"
						            required
						          ></b-form-input>
						        </b-form-group>

										<b-form-group
						          label="Telefono"
						          label-for="telefono-input"
						          invalid-feedback="El telefono del cliente es requerido"
						          :state="telState"
						        >
						          <b-form-input
						            id="telefono-input"
						            v-model="telefono"
						            :state="telState"
						            required
						          ></b-form-input>
						        </b-form-group>

										<b-form-group
						          label="Producto"
						          label-for="producto-input"
						          invalid-feedback="El producto solicitado es requerido"
						          :state="prodState"
						        >
						          <b-form-input
						            id="producto-input"
						            v-model="producto"
						            :state="prodState"
						            required
						          ></b-form-input>
						        </b-form-group>

									</b-form>

					      </b-container>

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

					</div>
				</div>
			</div>

			<div class="col-md-9">
				<div class="card shadow">
				<div class="card-body">
					<h1 class="text-center">Solicitudes</h1>
					<div class="mb-3 row justify-content-between">
						<div class="col-md-5">
						</div>
						<div class="col-md-7">

							<div class="form-inline">
								<div class="form-group">
									<input type="text" v-model="search" class="form-control d-inline" placeholder="Buscar solicitud" @change="get_solicitud">
									<button type="button" class="btn btn-primary" @click="get_solicitud">Buscar</button>
								</div>

							</div>
						</div>

					</div>

					<table class="table table-bordered table-sm table-hover table-striped">
						<thead>
							<tr>
								<th>Nombre</th>
								<th>Telefono</th>
								<th>Producto</th>
							</tr>
						</thead>
						<tbody>
							<tr v-for="(solicitud, index) in solicitudes" :key="index">

								<td>{{solicitud.nombre}}</td>
								<td>{{solicitud.telefono}}</td>
								<td>{{solicitud.producto}}</td>

							</tr>

							<tr v-if="solicitudes == []">
								<td class="text-center">No hay solicitudes registradas</td>
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
		components: {

		},
		data(){
			return{
				nombre: null,
				nameState: null,
				telefono: null,
				telState: null,
				producto: null,
				prodState: null,
				solicitudes: [],
				sincronizacion:'',
				loading:false,
				loading_sol:false,
				id:'',
				error: false,
				piso_venta_selected:[],
				dolar:0,
				page: "",
				currentPage: 0,
				per_page: 0,
				total_paginas: 0,
				total_solicitudes: 0,
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
			handleOk(bvModalEvt){
				bvModalEvt.preventDefault()
				this.cambiar_sol();
				console.log("empieza solicitud");

				if (!this.nombre) {
					this.nameState = false;
				} else {
					this.nameState = true;
					if (!this.telefono) {
						this.telState = false;
					} else {
						this.telState = true;
						if (!this.producto) {
							this.prodState = false;
						} else {
							this.prodState = true;

							axios.post(location.origin + '/api/store-solicitud',
							{
								nombre: this.nombre,
								telefono: this.telefono,
								producto: this.producto
							}).then(response => {
								console.log(response);
								this.cambiar_sol();
								window.location="http://localhost/pisos_de_venta/public/solicitudes";
							}).catch(e => {
								console.log(e.response)
								this.error = true;
								this.cambiar_sol();
							});

						}
					}
				}

			},
			sincronizar(){

				console.log(this.id);

				//ULTIMA SOLICITUD GUARDADA EN WEB
				axios.post('http://mipuchito.com/api/last-solicitud', {id: this.id}).then(response => {
					console.log(response);
					if (response.data != "") {
						var idExtra = response.data;
					} else {
						var idExtra = 0;
					}

					//TODAS LAS SOLICITUDES MAYORES AL ID-EXTRA WEB
					axios.post(location.origin + '/api/last-solicitud', {id: this.id, idExtra: idExtra}).then(response => {
						console.log(response);
						if (response.data != 0) {
							//SE REGISTRAN LAS SOLICITUDES NUEVAS
							axios.post('http://mipuchito.com/api/nuevas-solicitud', {data: response.data}).then(response => {
								console.log(response);
								//TODAS LAS SOLICITUDES FINALIZADAS EN LA WEB
								axios.post('http://mipuchito.com/api/finished-solicitud', {id: this.id}).then(response => {
									console.log(response);
									if (response.data != "") {
										var data = response.data;
									} else {
										var data = 0;
									}
									//BORRAR LAS SOLICITUDES EN LOCAL
									axios.post(location.origin + '/api/finish-solicitud', {data: data}).then(response => {
										console.log(response);
										this.sincro_exitosa = true;
										this.cambiar()
										window.location="http://localhost/pisos_de_venta/public/solicitudes";
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
						} else {
							//TODAS LAS SOLICITUDES FINALIZADAS EN LA WEB
							axios.post('http://mipuchito.com/api/finished-solicitud', {id: this.id}).then(response => {
								console.log(response);
								if (response.data != "") {
									var data = response.data;
								} else {
									var data = 0;
								}
								//BORRAR LAS SOLICITUDES EN LOCAL
								axios.post(location.origin + '/api/finish-solicitud', {data: data}).then(response => {
									console.log(response);
									this.sincro_exitosa = true;
									this.cambiar()
									window.location="http://localhost/pisos_de_venta/public/solicitudes";
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
			cambiar_sol(){
				this.loading_sol = !this.loading_sol;
			},
			get_piso_venta(){

				axios.get(location.origin + '/api/get-piso-venta').then(response =>{
					this.piso_venta_selected = response.data.piso_venta;
					this.id = response.data.piso_venta.id;
					this.sincronizacion = response.data.sincronizacion.created_at;
					console.log(this.id);

				}).catch(e => {
					console.log(e.response);
				});
			},
			get_solicitud(){

				axios.get(location.origin + '/api/get-solicitudes', {params:{search: this.search}}).then(response => {
					console.log('solicitudes');
					console.log(response);
					this.per_page = response.data.per_page;
					this.total_paginas = response.data.total;
					this.total_solicitudes = response.data.total;
					this.solicitudes = response.data.data
				}).catch(e => {
					console.log(e.response)
				});
			},
			paginar(event){

				axios.get(location.origin + '/api/get-solicitudes?page='+event).then(response => {
					console.log(response.data)
					this.per_page = response.data.per_page;
					this.total_paginas = response.data.total;
					this.solicitudes = response.data.data

				}).catch(e => {
					console.log(e.response)
				});
			},

		},
		computed:{

		},
		mounted(){
			//console.log(this.productos)

		},
		created(){

			this.get_solicitud();
			this.get_piso_venta();

		}

	}
</script>
