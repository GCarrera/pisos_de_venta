<template>
	<div>
		<div class="container">

			<div class="row">
				<div class="col-md-3">
					<div class="card shadow">
						<div class="card-header text-center">
							<span>Ventas Anuladas</span>
						</div>
						<div class="card-body">
							<div v-if="piso.length != 0" style="font-size: 1em;" class="mt-3">
								<span><span class="font-weight-bold">PV:</span> {{pv.nombre}}</span> <br>
								<span><span class="font-weight-bold">Ventas Anuladas:</span> {{ventas.length}}</span> <br>
							</div>
						</div>
					</div>
				</div>

				<div class="col-md-9">

					<div class="card shadow">
						<div class="card-body">
							<h1 class="text-center">Ventas Anuladas</h1>

							<b-row>

								<b-col sm="2" md="3" class="my-1">
									<b-form-group
									label="Mostrar"
									label-for="per-page-select"
									label-cols-sm="8"
									label-cols-md="6"
									label-cols-lg="5"
									label-align-sm="left"
									label-size="sm"
									class="mb-0"
									>
									<b-form-select
										id="per-page-select"
										v-model="perPage"
										:options="pageOptions"
										size="sm"
									></b-form-select>
									</b-form-group>
								</b-col>

								<b-col sm="10" lg="9" class="my-1">
									<b-form-group
									label="Buscar"
									label-for="filter-input"
									label-cols-sm="7"
									label-align-sm="right"
									label-size="sm"
									class="mb-0"
									>
									<b-input-group size="sm">
										<b-form-input
										id="filter-input"
										v-model="filter"
										type="search"
										placeholder="..."
										></b-form-input>

										<b-input-group-append>
										<b-button :disabled="!filter" @click="filter = ''">Limpiar</b-button>
										</b-input-group-append>
									</b-input-group>
									</b-form-group>
								</b-col>

							</b-row>

							<b-table
								:items="ventas"
								:fields="fields"
								:current-page="currentPage"
								:per-page="perPage"
								:filter="filter"
								:filter-included-fields="filterOn"
								:sort-by.sync="sortBy"
								:sort-desc.sync="sortDesc"
								:sort-direction="sortDirection"
								stacked="md"
								show-empty
								small
								@filtered="onFiltered"
								empty-filtered-text="No se consiguieron coincidencias"
								empty-text="No hay datos para mostrar"
								>

								<template #cell(actions)="row">
									<b-button v-b-tooltip.hover title="Ver" size="sm" @click="detailmodal(row.item, row.index, $event.target)" class="mr-1" variant="info">
										<b-icon-search></b-icon-search>
									</b-button>
								</template>

							</b-table>

							<!-- Info modal -->
							<b-modal :id="infoModal.id" :title="infoModal.title" ok-only @hide="resetInfoModal">
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
										<tr v-for="(producto, index) in infoModal.content" :key="index">
											<td>{{producto.name}}</td>
											<td>{{producto.pivot.cantidad}}</td>
											<td>{{new Intl.NumberFormat("de-DE", {minimumFractionDigits: 2}).format(producto.pivot.total/producto.pivot.cantidad)}}</td>
											<td>{{new Intl.NumberFormat("de-DE", {minimumFractionDigits: 2}).format(producto.pivot.total)}}</td>
										</tr>
									</tbody>

								</table>
							</b-modal>

							<b-row>
								<b-col sm="7" md="6" class="my-1">
									<b-pagination
									v-model="currentPage"
									:total-rows="totalRows"
									:per-page="perPage"
									align="fill"
									size="sm"
									class="my-0"
									></b-pagination>
								</b-col>
							</b-row>

						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</template>

<script>
export default{
	props: {
      ventas:{
        type: Array,
        required: true,
        default: () => []
      },
	  piso:{
        type: Array,
        required: true,
        default: () => []
      },
    },
	data(){
		return{
			pv: [],
			fields: [
				{
					key: 'id',
					label: 'FACTURA',
					sortable: true,
					thClass: 'text-center negrita',
					tdClass: 'small text-center',
					formatter: (value, key, item) => {
						return 'FC-00'+value
					},
				},
				{
					key: 'updated_at',
					label: 'FECHA',
					sortable: true,
					thClass: 'text-center negrita',
					tdClass: 'small text-center',
				},
				{
					key: 'total',
					label: 'TOTAL',
					sortable: true,
					thClass: 'text-center negrita',
					tdClass: 'small text-center',
					formatter: (value, key, item) => {
						if (item.pago == 1) {
							return new Intl.NumberFormat("de-DE", {minimumFractionDigits: 2}).format(value-(value*0.03))
						} else {							
							return new Intl.NumberFormat("de-DE", {minimumFractionDigits: 2}).format(value)
						}						
					},
				},
				{
					key: 'actions', label: 'ACCIONES'
				}
			],
			totalRows: 1,
			currentPage: 1,
			perPage: 5,
			pageOptions: [10, 20, 30, { value: 100, text: "Mostrar Todo" }],
			sortBy: '',
			sortDesc: false,
			sortDirection: 'asc',
			filter: null,
			filterOn: [],
			infoModal: {
				id: 'info-modal',
				title: '',
				content: ''
			}
		}
	},
	methods:{
		detailmodal(item, index, button) {
			this.infoModal.title = `Venta: FC-00${item.id}`
			this.infoModal.content = item.detalle
			this.$root.$emit('bv::show::modal', this.infoModal.id, button)
		},
		resetInfoModal() {
			this.infoModal.title = ''
			this.infoModal.content = ''
		},
		onFiltered(filteredItems) {
			// Trigger pagination to update the number of buttons/pages due to filtering
			this.totalRows = filteredItems.length
			this.currentPage = 1
		}
	},
	computed:{
		sortOptions() {
			// Create an options list from our fields
			return this.fields
			.filter(f => f.sortable)
			.map(f => {
				return { text: f.label, value: f.key }
			})
		}
	},
	mounted() {
		// Set the initial number of items
		this.totalRows = this.ventas.length;
    },
	created(){
		this.piso.forEach(element => {			
			this.pv = element
		});
		console.log(this.piso);
		console.log(this.ventas);
	}
}
</script>
