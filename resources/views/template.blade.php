<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Sistema Optica</title>
        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
        @vite(['resources/sass/app.scss', 'resources/js/app.js'])
    </head>
    <body class="d-flex flex-column min-vh-100">
        <div id="app" class="flex-grow-1">
            <nav class="navbar navbar-expand-lg navbar-light bg-light fixed-top">
                <div class="container">
                    <a class="navbar-brand" href="/">Sistema Optica</a>
                    <div class="collapse navbar-collapse" id="navbarNav">
                        <ul class="navbar-nav">
                            <li class="nav-item me-3">
                                <a class="nav-link" href="/">Inicio</a>
                            </li>
                            <!-- Dropdown menu for Usuarios -->
                            <li class="nav-item dropdown me-3">
                                <a class="nav-link dropdown-toggle" href="#" id="usuariosDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    Usuarios de Sistema
                                </a>
                                <ul class="dropdown-menu" aria-labelledby="usuariosDropdown">
                                    <li><a class="dropdown-item" href="/usuarios/crear">Crear</a></li>
                                    <li><a class="dropdown-item" href="/usuarios/consultar">Consultar</a></li>
                                </ul>
                            </li>
                        </ul>
                        <ul class="navbar-nav ms-auto">
                            <!-- User Dropdown -->
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="fas fa-user me-1"></i> User123
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                                    <li><a class="dropdown-item" href="/profile">Perfil</a></li>
                                    <li><a class="dropdown-item" href="/settings">Configuración</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="/logout">Salir del Sistema</a></li>
                                </ul>
                            </li>
                        </ul>
                    </div>
                </div>
            </nav>
            <div class="container mt-4 pt-5">
                @yield('content')
            </div>
        </div>
        <footer class="bg-light py-3 mt-auto">
            <div class="container text-center">
                <p class="mb-0">&copy; {{ date('Y') }} Sistema Optica. All rights reserved.</p>
            </div>
        </footer>
        @vite('resources/js/main.js')
    </body>
</html>
