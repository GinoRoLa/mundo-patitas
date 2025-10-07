
<!doctype html>
<html lang="es">
    <head>
        <meta charset="utf-8">
        <title>Menú – Mundo Patitas</title>
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <link rel="stylesheet" href="/Vista/Style/Style.css">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    </head>
    <body class="menu">

        <main class="container my-5">
            <section class="menu-card">
                <div class="text-center mb-4">
                    <h2 class="fw-bold">Menú principal</h2>
                    <p class="text-muted">Selecciona un caso de uso:</p>
                </div>

                <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">

                    <!-- Card CUS01 -->
                    <div class="col">
                        <div class="card shadow-sm h-100">
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title">CUS01</h5>
                                <p class="card-text flex-grow-1">Generar Preorden</p>
                                <a href="Vista/Page/CUS01_IU001.php"
                                   class="btn btn-primary mt-auto" target="_blank">
                                    Ir al caso de uso
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Card CUS02 -->
                    <div class="col">
                        <div class="card shadow-sm h-100">
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title">CUS02</h5>
                                <p class="card-text flex-grow-1">Generar Orden de Pedido</p>
                                <a href="Vista/Page/CUS02_IU003.php" 
                                   class="btn btn-primary mt-auto" target="_blank">
                                    Ir al caso de uso
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Card CUS03 -->
                    <div class="col">
                        <div class="card shadow-sm h-100">
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title">CUS03</h5>
                                <p class="card-text flex-grow-1">Generar Boleta de Pago</p>
                                <a href="http://127.0.0.1:5501/index.html" 
                                   class="btn btn-primary mt-auto" target="_blank">
                                    Ir al caso de uso
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Card CUS04 -->
                    <div class="col">
                        <div class="card shadow-sm h-100">
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title">CUS04</h5>
                                <p class="card-text flex-grow-1">Preparar pedido de venta</p>
                                <a href="Vista/Page/CUS04_IU004.php" 
                                   class="btn btn-primary mt-auto" target="_blank">
                                    Ir al caso de uso
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Card CUS24 -->
                    <div class="col">
                        <div class="card shadow-sm h-100">
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title">CUS24</h5>
                                <p class="card-text flex-grow-1">Salida de Entrega</p>
                                <a href="Vista/Page/CUS24_IU024.php" 
                                   class="btn btn-primary mt-auto" target="_blank">
                                    Ir al caso de uso
                                </a>
                            </div>
                        </div>
                    </div>

                </div>
            </section>
        </main>


        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>

    </body>

</html>
