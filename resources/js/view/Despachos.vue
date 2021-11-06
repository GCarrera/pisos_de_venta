<template>
	<div>
		<div class="container">
			 <b-alert show variant="success" fade dismissible v-if="alert_success == true">{{alert_message}}</b-alert>

		    <b-alert show variant="success" fade dismissible v-if="sincro_exitosa == true">sincronozacion exitosa</b-alert>

		    <b-alert show variant="danger" fade dismissible v-if="error == true">ah ocurrido un error</b-alert>

				<b-alert show variant="danger" fade dismissible v-if="alert_error == true">{{error_message}}</b-alert>

		    <div class="row">
		    	<div class="col-md-3">
					<div class="card shadow">
						<div class="card-header text-center">
							<span>Sincronizar Despachos</span>
						</div>
						<div class="card-body">
							<div v-if="piso_venta_selected.length != 0" style="font-size: 1em;" class="mt-3">
								<span><span class="font-weight-bold">PV:</span> {{piso_venta_selected.nombre}}</span> <br>
								<!-- <span><span class="font-weight-bold">Lugar:</span> {{piso_venta_selected.ubicacion}}</span> <br> -->
								<!--<span><span class="font-weight-bold">Caja:</span> {{formattedCurrencyValue}}</span> <br>-->
								<span><span class="font-weight-bold">Recibidos:</span> {{this.total_paginas}}</span> <br>

							</div>
								<hr>
									<span class="font-weight-bold" > Ultima Actualización: </span> <span v-if="sincronizacion !== null">{{sincronizacion}}</span> <br>
									<!-- <span class="font-weight-bold" >Ultima vez que vacio la caja: </span><span  v-if="caja !== null">{{caja}}</span> <br> -->
								<hr>
							<button class="btn btn-primary btn-block" @click="sincronizar" :disabled="loading">

							<span v-if="loading == false">Sincronizar</span>
							<div class="spinner-border text-light text-center" role="status" v-if="loading == true">
							  	<span class="sr-only">Cargando...</span>
							</div>
							</button>

							<!--<button class="btn btn-warning btn-block" @click="precios">Precios</button>-->
					</div>
				</div>
			</div>
		    <div class="col-md-9">
			<div class="card shadow">
				<div class="card-body">
					<h1 class="text-center">Despachos</h1>
					<div class="mb-3">
						<div class="row justify-content-between">
							<div class="col-12 col-md-2">
								<!--<button class="btn btn-primary " @click="refrescar">Sincronizar</button>-->
							</div>
							<div class="col-12 col-md-2">
							</div>

						</div>
					</div>

					<table class="table table-bordered table-sm table-hover table-striped">
						<thead>
							<tr>
								<th>Fecha</th>
								<th>Tipo</th>
								<th>Estado</th>
								<th>Acciones</th>
							</tr>
						</thead>
						<tbody>
							<tr v-for="(despacho, index) in despachos" :key="index">
								<td>{{despacho.created_at}} {{despacho.id}}</td>
								<th>{{despacho.type == 1? "Despacho" : "Retiro"}}</th>
								<td v-if="despacho.confirmado == 4" class="font-weight-bold">Pendiente</td>
								<td v-else class="font-weight-bold">{{despacho.confirmado == 1 ? "Confirmado" : "Negado"}}</td>
								<td>
									<button type="button" class="btn btn-primary" @click="showModalDetalles(despacho.id)">Ver</button>
									<!--<button class="btn btn-primary" data-toggle="modal" data-target="#modalVer">Ver</button>-->
									<button class="btn btn-success" v-if="despacho.confirmado == 4" @click="confirmar(despacho.id, index)" :disabled="loadingDes">
										Confirmar
									</button>
									<button class="btn btn-danger" v-if="despacho.confirmado == 4" @click="negar(despacho.id, index)" :disabled="loadingDes">
										Negar
									</button>
								</td>

								<!-- Modal PARA VER LOS DETALLES -->
								<b-modal :id="'modal-detalles-'+despacho.id" size="lg" :title="'Detalles del Despacho'">

									<table class="table table-bordered table-sm">
										<thead>
											<tr>
												<th>Producto</th>
												<th>Cantidad</th>
											</tr>
										</thead>
										<tbody>
											<tr v-for="(product, index) in despacho.productos" :key="index">
												<td>{{product.product_name}}</td>
												<td v-if="product.pivot.tipo == 1">al menor</td>
												<td v-if="product.pivot.tipo == 2">al mayor</td>
												<td>{{product.pivot.cantidad}}</td>
											</tr>
										</tbody>
									</table>

								</b-modal>

							</tr>

							<tr v-if="despachos == []">
								<td class="text-center">No hay despachos registrados</td>
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
				despachos: [],
				currentPage: 0,
				loading:false,
				loadingDes:false,
				per_page: 0,
				sincronizacion:'',
				total_paginas: 0,
				id: 0,
				piso_venta_selected:[],
				alert_success: false,
				alert_message : "",
				error_message : null,
				alert_success:false,
				alert_error: false,
				sincro_exitosa:false,
				error:false,
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
			sincronizar(){
				this.error=false
				this.cambiar()
				this.sincro_exitosa = false
				let ultimoDespacho = 0;
				let nuevosDespachos = [];
				let despachosSinConfirmar = [];
				let despachosConfirmados = [];
				//ULTIMO DESPACHO RECIBIDO
				axios.get('http://localhost/pisos_de_venta/public/api/ultimo-despacho').then(response => {
					//ID_EXTRA DEL ULTIMO DESPACHO REGISTRADO
					console.log("Ultimo id_extra");
					console.log(response);
					//SI SE TRAJO ALGUN DESPACHO, ESTO QUITA EL ERROR DE LA PRIMERA VEZ YA QUE NO ABRA NINGUN REGISTRO previo
					if (response.data.id_extra != null) {
						ultimoDespacho = response.data.id_extra;
					}

					//SOLICITAR DESPACHOS NUEVOS (para eso necesito el ultimo despacho recibido)
					axios.post('http://www.mipuchito.com/api/get-despachos-web', {piso_venta_id: this.id, ultimo_despacho: ultimoDespacho}).then(response => {//DEL LADO DE LA WEB
						console.log("despachos mayores al id_extra");
						nuevosDespachos = response.data;
						console.log(nuevosDespachos)
						/*
						if (nuevosDespachos.id == null) {
						console.log(nuevosDespachos)
						}else{
							console.log("hay algo")
						}
						*/
						if (nuevosDespachos.length > 0) {

							console.log("hay despachos por registrar");
							//REGISTRAR LOS DESPACHOS RECIBIDOS
							axios.post('http://localhost/pisos_de_venta/public/api/registrar-despachos-piso-venta', {despachos: nuevosDespachos}).then(response => {//
								console.log('registrar-despachos-piso-venta');
								console.log(response);//SI REGISTRA DEBERIA DAR TRUE
								if (response.data == true) {

									//SINC
									this.sincron.despachos = true;
								} else {
									this.alert_error = true;
									this.error_message = response.data;
									this.cambiar()
								}
							}).catch(e => {
								console.log(e.response)
								this.error = true;
								this.cambiar()
							});

						}else{

							console.log("hey else");

							//SINC
							//this.sincron.despachos = true;
							//this.sincro_exitosa = true;
							//this.cambiar()
							//window.location="http://localhost/pisos_de_venta/public/despachos";
						}
						//PEDIR DE LA WEB LOS DESPACHOS QUE NO ESTAN CONFIRMADOS
						//axios.get('http://mipuchitoex.com/api/get-despachos-sin-confirmacion/'+this.id).then(response => {//DEL LADO DE LA WEB
						axios.get('http://www.mipuchito.com/api/get-despachos-sin-confirmacion/'+this.id).then(response => {//DEL LADO DE LA WEB
						console.log("Despachos sin confirmar type 1 confirmado 4");
						despachosSinConfirmar = response.data;
						console.log("despachos sin confirmar");
						console.log(despachosSinConfirmar);
						if (despachosSinConfirmar.length > 0) {
							console.log("Hay despachos sin confirmar en web");
							//PEDIR LOS DATOS EN LOCAL DE LOS QUE NO ESTAN CONFIRMADOS EN LA WEB
							axios.post('http://localhost/pisos_de_venta/public/api/get-despachos-confirmados', {despachos: despachosSinConfirmar}).then(response => {//
								console.log("despachos confirmados por pv");
								console.log(response);
								despachosConfirmados = response.data
								console.log("despachos confirmados");
								console.log(despachosConfirmados);
								//GUARDAR LOS DATOS ANTERIORES EN LA WEB
								axios.post('http://www.mipuchito.com/api/actualizar-confirmados', {despachos: despachosConfirmados, piso_venta_id: this.id}).then(response => {//DEL LADO DE LA WEB PARA ACTUALIZAR LAS CONFIRMACIONES

									console.log("actualizar despachos confirmados en web");
									console.log(response);

									console.log("ultimo retiro web");
									axios.get('http://www.mipuchito.com/api/ultimo-retiro/'+this.id).then(response => {

										console.log("ultimo retiro guardado en web");
										console.log(response);

										var ultimoretiro = response.data;
										console.log(ultimoretiro);
										console.log("ultimo retiro guardado en web");

										if (ultimoretiro.created_at != null) {
											console.log("hay un retiro en la web");
											axios.post('http://localhost/pisos_de_venta/public/api/get-retiros-web', {id: ultimoretiro}).then(response => {

												console.log("retiros nuevos");
												console.log(response);

												var retirosnuevos = response.data;

												console.log(retirosnuevos);
												console.log("retirnos nuevos del pv");

												if (retirosnuevos.length > 0) {

													axios.post('http://www.mipuchito.com/api/store-retiros', {retiros: retirosnuevos}).then(response => {

														console.log("guardar retiros en web");

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

										}

										axios.get('http://localhost/pisos_de_venta/public/api/get-despachos-guardados/').then(response => {
											console.log(response);
											var despachosGuardados = response.data;
											console.log("despachos guardados en pv");
											console.log(despachosGuardados);
											if (despachosGuardados.length > 0) {
												axios.post('http://www.mipuchito.com/api/get-despachos-no-guardados', {despachos: despachosGuardados}).then(response => {
													console.log("despachos no guardados en prometheus");
													console.log(response);
													var despachosNoguardados = response.data
													console.log(despachosNoguardados);

													axios.post('http://localhost/pisos_de_venta/public/api/actualizar-despachos-guardados', {despachos: despachosNoguardados}).then(response => {														
														console.log(response);
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

										console.log("fuera del if si hay retiros en la web");
										axios.post('http://www.mipuchito.com/api/sincronizacion', {id: this.id}).then(response => {
											axios.post('http://localhost/pisos_de_venta/public/api/sincronizacion', {id: this.id}).then(response => {

												this.cambiar()
												//console.log(response);
												//SINC
												this.sincron.despachos = true;
												console.log('error message');
												console.log(this.error_message);
												if (this.error_message == null) {
													console.log('vacio');
													this.sincro_exitosa = true
													window.location="http://localhost/pisos_de_venta/public/despachos";
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
							console.log("no hay despachos sin confirmar");

							axios.get('http://localhost/pisos_de_venta/public/api/get-despachos-guardados/'+this.id).then(response => {
								var despachosGuardados = response.data;
								console.log("despachos guardados en pv");
								console.log(despachosGuardados);
								if (despachosGuardados.length > 0) {
									axios.post('http://www.mipuchito.com/api/get-despachos-no-guardados', {despachos: despachosGuardados}).then(response => {

										console.log("despachos no guardados en prometheus");
										console.log(response);
										var despachosNoguardados = response.data
										console.log(despachosNoguardados);

										axios.post('http://localhost/pisos_de_venta/public/api/actualizar-despachos-guardados', {despachos: despachosNoguardados}).then(response => {														
											console.log(response);
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

							axios.post('http://www.mipuchito.com/api/sincronizacion', {id: this.id}).then(response => {
								axios.post('http://localhost/pisos_de_venta/public/api/sincronizacion', {id: this.id}).then(response => {
									//console.log("No hay despachos sin confirmar");
									this.cambiar()
									this.sincron.despachos = true;
									console.log('error message');
									console.log(this.error_message);
									if (this.error_message == null) {
										console.log('no error');
										this.sincro_exitosa = true
										window.location="http://localhost/pisos_de_venta/public/despachos";
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

						}).catch(e => {
							console.log(e.response)
							this.error = true;
							this.cambiar()
						});
						//FIN SEGUNDA PETICION

					}).catch(e => {
						console.log(e.response);
						this.error = true;
						this.cambiar()
					})

				}).catch(e => {
					console.log(e.response);
					this.error = true;
					this.cambiar()
				})


			},
			cambiar(){
				console.log("btn cambio")
				this.loading = !this.loading;
			},
			cambiar_des(){
				console.log("btn cambio canfirmar")
				this.loadingDes = !this.loadingDes;
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
			get_despachos(){

				axios.get('http://localhost/pisos_de_venta/public/api/get-despachos').then(response => {
					console.log('get_despachos');
					console.log(response);
					this.per_page = response.data.per_page;
					this.total_paginas = response.data.total;
					this.despachos = response.data.data

					console.log(this.despachos)
				}).catch(e => {
					console.log(e.response)
					location.reload()
				});
			},
			paginar(event){

				axios.get('http://localhost/pisos_de_venta/public/api/get-despachos?page='+event).then(response => {
					console.log(response.data)
					this.per_page = response.data.per_page;
					this.total_paginas = response.data.total;
					this.despachos = response.data.data

				}).catch(e => {
					console.log(e.response)
				});
			},
			confirmar(id, index){

				this.cambiar_des();

				console.log("confirmar despacho");
				console.log(id);

				axios.post('http://localhost/pisos_de_venta/public/api/confirmar-despacho', {id: id}).then(response => {

					console.log(response)
					this.despachos.splice(index, 1, response.data);

					this.cambiar_des();
				}).catch(e => {
					console.log(e.response);
				})
			},
			negar(id, index){

				this.cambiar_des();

				axios.post('http://localhost/pisos_de_venta/public/api/negar-despacho', {id: id}).then(response => {

					console.log(response.data)
					this.despachos.splice(index, 1, response.data);

					this.cambiar_des();
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
			sinconfirmacion(){
				//PEDIR DE LA WEB LOS DESPACHOS QUE NO ESTAN CONFIRMADOS
							axios.get('http://localhost/pisos_de_venta/public/api/get-despachos-sin-confirmacion/'+this.id).then(response => {

								console.log(response);

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
           	 	let n = new Intl.NumberFormat("de-DE").format(this.piso_venta_selected.dinero)
				let a = "Bs " + n +",00"
				return a
        	},
		},
		created(){

			this.get_despachos()
			this.get_id()
			this.get_piso_venta()
		}
	}
</script>
