<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Scripts -->
    <script src="{{ asset('js/app.js') }}" defer></script>

    <!-- Fonts -->
    <link rel="dns-prefetch" href="//fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css?family=Nunito" rel="stylesheet">

    <!-- Styles -->
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
    <style>
        body{
            background-color: #f8f9fa !important;
        }
    </style>
</head>
<body>
    <div id="app">

        <nav class="navbar navbar-expand-md navbar-dark bg-dark shadow-sm">
            <div class="container">
                <a class="navbar-brand" href="{{ url('/') }}">
                    Pisos de ventas
                </a>
                <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="{{ __('Toggle navigation') }}">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="collapse navbar-collapse" id="navbarSupportedContent">

                    <ul class="navbar-nav mr-auto">

                    </ul>

                    <ul class="navbar-nav ml-auto">
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('home') }}">Inicio</a>
                        </li>

                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('solicitudes.index') }}">Solicitudes <font-awesome-icon icon="user"/></a>
                        </li>

                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('ventas.index') }}" >Lista de Ventas </a>
                        </li>

                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('ventas.create') }}" >Vender </a>
                        </li>

                        <!-- Li que incluye compras

                        <li class="nav-item dropdown">
                            <a id="navbarDropdown" class="nav-link dropdown-toggle" href="#" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" v-pre>
                                Ventas<span class="caret"></span>
                            </a>

                            <div class="dropdown-menu dropdown-menu-right" aria-labelledby="navbarDropdown">
                                <a class="dropdown-item" href="{{ route('ventas.index') }}">
                                Lista
                                </a>
                                <a class="dropdown-item" href="{{ route('ventas.create') }}">
                                Nueva venta
                                </a>
                                <a class="dropdown-item" href="{{ route('ventas.create.compra') }}">
                                Nueva Compra
                                </a>
                            </div>
                        </li>

                      -->

                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('inventario.index') }}">Inventario <font-awesome-icon :icon="['fas', 'store']"/></a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('despachos.index') }}">Despachos <font-awesome-icon :icon="['fas', 'cart-arrow-down']"/></a>
                        </li>
                        <li class="nav-item">
                          <a type="button" class="nav-link" data-toggle="modal" data-target="#modal-dolar">
                            Dolar $
                          </a>
                        </li>
                        <!--
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('despachos.almacen.index') }}">Despachos almacen</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('compras.index') }}">Compras</a>
                        </li>
                        -->
                        @guest
                            <li class="nav-item">
                                <a class="nav-link" href="{{ route('login') }}">{{ __('Login') }}</a>
                            </li>
                            @if (Route::has('register'))
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ route('register') }}">{{ __('Register') }}</a>
                                </li>
                            @endif
                        @else
                            <li class="nav-item dropdown">
                                <a id="navbarDropdown" class="nav-link dropdown-toggle" href="#" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" v-pre>
                                    {{ Auth::user()->name }}  <span class="caret"></span>
                                </a>

                                <div class="dropdown-menu dropdown-menu-right" aria-labelledby="navbarDropdown">
                                    <a class="dropdown-item" href="{{ route('logout') }}"
                                       onclick="event.preventDefault();
                                                     document.getElementById('logout-form').submit();">
                                        {{ __('Cerrar sessión') }}
                                    </a>

                                    <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                                        @csrf
                                    </form>
                                </div>
                            </li>
                        @endguest
                    </ul>
                </div>
            </div>
        </nav>

        <main class="py-4">
          <!-- Modal DEL DOLAR -->
          <div class="modal fade" id="modal-dolar" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
            	<div class="modal-dialog">
              	<div class="modal-content">
                		<div class="modal-header">
          	        	<h5 class="modal-title" id="exampleModalLabel">Precio del dolar</h5>
          	        	<button type="button" class="close" data-dismiss="modal" aria-label="Close">
          	          	<span aria-hidden="true">&times;</span>
          	        	</button>
                		</div>
          	      	<div class="modal-body">

                        <h6 class="font-weight-bold text-success text-center">Dolar Trabajo: <span id="spanDolarAct"></span> / Dolar Oficial: <span id="spanDolarO"></span></h6>

                        <p class="text-center">Establesca un nuevo precio.</p>                        
            
                        <form action="{{ action('PisoVentasController@establecer_dolar')}}" method="post">
                            @csrf
                            <div class="text-center">
                            <input type="text" placeholder="Ejem: 310000" name="precio" id="precio" required onkeyup="formattMoney(this)">
                            BS (Trabajo)
                            <hr>
                            <input type="text" placeholder="Ejem: 310000" name="precioo" id="precioo" required onkeyup="formattMoneyO(this)">
                            BS (Oficial)
                            <hr>
                            <button class="btn btn-primary" type="submit">Establecer</button>
                            </div>
                        </form>

          	      	</div>

              	</div>
            	</div>
          </div>            
            <!-- Modal -->
            <div class="modal fade" id="exampleModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Modal title</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    </div>
                    <div class="modal-body">
                    ...
                    </div>
                    <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary">Save changes</button>
                    </div>
                </div>
                </div>
            </div>
            @if (session('errordolar'))
                <div class="toast" data-autohide="false" style="position: absolute; z-index: 1; margin-left: 51px; margin-top: 15px;">
                    <div class="toast-header" style="color: #721c24; background-color: #f8d7da; border-color: #f5c6cb;">
                        <strong class="mr-auto text-danger">Precio del Dolar</strong>
                        <button type="button" class="ml-2 mb-1 close" data-dismiss="toast">&times;</button>
                    </div>
                    <div class="toast-body" style="color: #721c24; background-color: #f8d7da; border-color: #f5c6cb;">
                        {{ session('errordolar') }}
                    </div>
                </div>
            @endif            
            @yield('content')
        </main>
    </div>
</body>
<script src="{{ asset('js/jquery-3.4.1.min.js') }}"></script>
<script type="text/javascript">
    function formattMoney(){
        $("#precio").on({
            "focus": function (event) {
                $(event.target).select();
            },
            "keyup": function (event) {
                $(event.target).val(function (index, value ) {
                    return value.replace(/\D/g, "")
                                .replace(/([0-9])([0-9]{2})$/, '$1,$2')
                                .replace(/\B(?=(\d{3})+(?!\d)\.?)/g, ".");
                });
            }
        });
    };
    
    function formattMoneyO(){
        $("#precioo").on({
            "focus": function (event) {
                $(event.target).select();
            },
            "keyup": function (event) {
                $(event.target).val(function (index, value ) {
                    return value.replace(/\D/g, "")
                                .replace(/([0-9])([0-9]{2})$/, '$1,$2')
                                .replace(/\B(?=(\d{3})+(?!\d)\.?)/g, ".");
                });
            }
        });
    };
    

  $(document).ready( function () {

    $('.toast').toast('show');
    setTimeout(function(){ $('.toast').toast('hide'); }, 10000);

    $.get({
      url : `http://localhost/pisos_de_venta/public/api/get-dolar`
    })
    .done((data) => {
        console.log(data);
      $('#spanDolarAct').text(new Intl.NumberFormat("de-DE", {minimumFractionDigits: 2}).format(data.dolar));
      $('#spanDolarO').text(new Intl.NumberFormat("de-DE", {minimumFractionDigits: 2}).format(data.dolaro));
      //console.log(data);
    })
    .fail((err)=> {
      console.log(err)
      toastr.error('Ha ocurrido un error.')
    })

  } );
</script>
</html>
