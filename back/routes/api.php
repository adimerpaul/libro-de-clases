<?php

use App\Http\Controllers\BajaController;
use App\Http\Controllers\CajaMovimientoController;
use App\Http\Controllers\CierreCajaController;
use App\Http\Controllers\CompraController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DerivacionController;
use App\Http\Controllers\DoctorController;
use App\Http\Controllers\InternacionController;
use App\Http\Controllers\InternacionItemController;
use App\Http\Controllers\LaboratorioReporteController;
use App\Http\Controllers\PacienteCompraController;
use App\Http\Controllers\PacienteController;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\ProductoFarmaciaController;
use App\Http\Controllers\ProductoLaboratorioController;
use App\Http\Controllers\ProductoLoteController;
use App\Http\Controllers\ProductoVencimientoController;
use App\Http\Controllers\ProveedorController;
use App\Http\Controllers\ReactivoController;
use App\Http\Controllers\ReactivoKardexController;
use App\Http\Controllers\SeguroController;
use App\Http\Controllers\SolicitudeController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VentaClienteController;
use App\Http\Controllers\VentaController;
use Illuminate\Support\Facades\Route;

// ── Rutas públicas ────────────────────────────────────────────
Route::post('/login', [UserController::class, 'login']);
Route::get('/verificacion-laboratorio/{codigo}', [SolicitudeController::class, 'verificacion']);

// ── Rutas protegidas ──────────────────────────────────────────
Route::middleware('auth:sanctum')->group(function () {

    // Dashboard principal
    Route::get('/dashboard', [DashboardController::class, 'index']);

    Route::get('/me', [UserController::class, 'me']);
    Route::post('/logout', [UserController::class, 'logout']);
    Route::put('/cambiar-password', [UserController::class, 'changePassword']);

    // CRUD usuarios
    Route::get('/users', [UserController::class, 'index']);
    Route::post('/users', [UserController::class, 'store']);
    Route::put('/users/{id}', [UserController::class, 'update']);
    Route::delete('/users/{id}', [UserController::class, 'destroy']);
    Route::put('/users/{id}/reset-password', [UserController::class, 'resetPassword']);
    Route::put('/users/{id}/bloqueo', [UserController::class, 'toggleBloqueo']);
    Route::post('/users/{id}/avatar', [UserController::class, 'uploadAvatar']);

    // Permisos
    Route::get('/permissions', [UserController::class, 'permissions']);
    Route::get('/users/{id}/permissions', [UserController::class, 'userPermissions']);
    Route::put('/users/{id}/permissions', [UserController::class, 'updateUserPermissions']);

    // Farmacia
    Route::get('/farmacia/datos', [ProductoController::class, 'datos']);
    Route::get('/farmacia/resumen', [ProductoController::class, 'resumen']);
    Route::get('/productos-por-vencer', [ProductoVencimientoController::class, 'porVencer']);
    Route::get('/productos-vencidos', [ProductoVencimientoController::class, 'vencidos']);

    // Productos de farmacia (solo tipo FARMACIA, permisos propios)
    Route::get('/productos-farmacia/catalogos', [ProductoFarmaciaController::class, 'catalogos']);
    Route::get('/productos-farmacia/resumen', [ProductoFarmaciaController::class, 'resumen']);
    Route::get('/productos-farmacia/{id}/historial', [ProductoFarmaciaController::class, 'historial']);
    Route::get('/productos-farmacia/export-pdf', [ProductoFarmaciaController::class, 'exportPdf']);
    Route::get('/productos-farmacia/export-excel', [ProductoFarmaciaController::class, 'exportExcel']);
    Route::get('/productos-farmacia', [ProductoFarmaciaController::class, 'index']);
    Route::post('/productos-farmacia', [ProductoFarmaciaController::class, 'store']);
    Route::put('/productos-farmacia/{id}', [ProductoFarmaciaController::class, 'update']);
    Route::delete('/productos-farmacia/{id}', [ProductoFarmaciaController::class, 'destroy']);

    // Bajas de farmacia (las rutas fijas van antes de /bajas/{id})
    Route::get('/bajas/catalogos', [BajaController::class, 'catalogos']);
    Route::get('/bajas/resumen', [BajaController::class, 'resumen']);
    Route::get('/bajas/productos', [BajaController::class, 'productos']);
    Route::get('/bajas/productos/{producto}/lotes', [BajaController::class, 'lotes']);
    Route::get('/bajas', [BajaController::class, 'index']);
    Route::post('/bajas', [BajaController::class, 'store']);
    Route::put('/bajas/{id}/anular', [BajaController::class, 'anular']);
    Route::get('/bajas/{id}', [BajaController::class, 'show']);

    // Catálogos - Fabricantes
    Route::get('/fabricantes/export-pdf', [ProductoController::class, 'exportFabricantesPdf']);
    Route::get('/fabricantes/export-excel', [ProductoController::class, 'exportFabricantesExcel']);
    Route::get('/fabricantes', [ProductoController::class, 'fabricantes']);
    Route::post('/fabricantes', [ProductoController::class, 'storeFabricante']);
    Route::put('/fabricantes/{id}', [ProductoController::class, 'updateFabricante']);
    Route::delete('/fabricantes/{id}', [ProductoController::class, 'destroyFabricante']);

    // Catálogos - Unidades
    Route::get('/unidades/export-pdf', [ProductoController::class, 'exportUnidadesPdf']);
    Route::get('/unidades/export-excel', [ProductoController::class, 'exportUnidadesExcel']);
    Route::get('/unidades', [ProductoController::class, 'unidades']);
    Route::post('/unidades', [ProductoController::class, 'storeUnidad']);
    Route::put('/unidades/{id}', [ProductoController::class, 'updateUnidad']);
    Route::delete('/unidades/{id}', [ProductoController::class, 'destroyUnidad']);

    // Catálogos - Tipos de producto
    Route::get('/tipo-productos', [ProductoController::class, 'tiposProducto']);
    Route::post('/tipo-productos', [ProductoController::class, 'storeTipoProducto']);
    Route::put('/tipo-productos/{id}', [ProductoController::class, 'updateTipoProducto']);
    Route::delete('/tipo-productos/{id}', [ProductoController::class, 'destroyTipoProducto']);

    // Catálogos - Tipos de producto padre
    Route::get('/tipo-producto-padres', [ProductoController::class, 'tiposProductoPadre']);
    Route::post('/tipo-producto-padres', [ProductoController::class, 'storeTipoProductoPadre']);
    Route::put('/tipo-producto-padres/{id}', [ProductoController::class, 'updateTipoProductoPadre']);
    Route::delete('/tipo-producto-padres/{id}', [ProductoController::class, 'destroyTipoProductoPadre']);

    // Productos
    Route::get('/productos/export-pdf', [ProductoController::class, 'exportProductosPdf']);
    Route::get('/productos/export-excel', [ProductoController::class, 'exportProductosExcel']);
    Route::get('/productos/{id}/historial', [ProductoController::class, 'historial']);
    Route::put('/productos/{producto}/historial/{tipo}/{detalle}', [ProductoLoteController::class, 'update']);
    Route::get('/productos/{id}/lotes-disponibles', [ProductoController::class, 'lotesDisponibles']);
    Route::get('/productos/{producto}/laboratorio-configuracion', [ProductoLaboratorioController::class, 'show']);
    Route::post('/productos/{producto}/laboratorio-datos', [ProductoLaboratorioController::class, 'storeDato']);
    Route::put('/productos/{producto}/laboratorio-datos/orden', [ProductoLaboratorioController::class, 'reorderDatos']);
    Route::put('/producto-laboratorio-datos/{dato}', [ProductoLaboratorioController::class, 'updateDato']);
    Route::delete('/producto-laboratorio-datos/{dato}', [ProductoLaboratorioController::class, 'destroyDato']);
    Route::post('/producto-laboratorio-datos/{dato}/formula', [ProductoLaboratorioController::class, 'storeDatoFormula']);
    Route::post('/productos/{producto}/laboratorio-formulas', [ProductoLaboratorioController::class, 'storeFormula']);
    Route::put('/producto-laboratorio-formulas/{formula}', [ProductoLaboratorioController::class, 'updateFormula']);
    Route::delete('/producto-laboratorio-formulas/{formula}', [ProductoLaboratorioController::class, 'destroyFormula']);
    Route::post('/productos/{producto}/laboratorio-validaciones', [ProductoLaboratorioController::class, 'storeValidacion']);
    Route::put('/producto-laboratorio-validaciones/{validacion}', [ProductoLaboratorioController::class, 'updateValidacion']);
    Route::delete('/producto-laboratorio-validaciones/{validacion}', [ProductoLaboratorioController::class, 'destroyValidacion']);

    // Reportes generales de laboratorio
    Route::get('/reportes-laboratorio', [LaboratorioReporteController::class, 'index']);
    Route::get('/reportes-laboratorio/export-excel', [LaboratorioReporteController::class, 'excel']);
    Route::get('/reportes-laboratorio/export-pdf', [LaboratorioReporteController::class, 'pdf']);

    // Solicitudes de laboratorio
    Route::get('/solicitudes-laboratorio/form-data', [SolicitudeController::class, 'formData']);
    Route::get('/solicitudes-laboratorio/pacientes', [SolicitudeController::class, 'pacientes']);
    Route::get('/solicitudes-laboratorio/ventas-laboratorio', [SolicitudeController::class, 'ventasLaboratorio']);
    Route::get('/solicitudes-laboratorio', [SolicitudeController::class, 'index']);
    Route::post('/solicitudes-laboratorio', [SolicitudeController::class, 'store']);
    Route::get('/solicitudes-laboratorio/{solicitude}/auditoria', [SolicitudeController::class, 'auditoria']);
    Route::get('/solicitudes-laboratorio/{solicitude}/pdf', [SolicitudeController::class, 'pdf']);
    Route::get('/solicitudes-laboratorio/{solicitude}/impresion', [SolicitudeController::class, 'impresion']);
    Route::put('/solicitudes-laboratorio/{solicitude}', [SolicitudeController::class, 'update']);
    Route::get('/solicitudes-laboratorio/{solicitude}', [SolicitudeController::class, 'show']);
    Route::delete('/solicitudes-laboratorio/{solicitude}', [SolicitudeController::class, 'destroy']);

    // Reactivos y consumo por servicio de laboratorio
    Route::get('/reactivos-kardex/pdf', [ReactivoKardexController::class, 'pdf']);
    Route::get('/reactivos-kardex', [ReactivoKardexController::class, 'index']);
    Route::get('/reactivos/form-data', [ReactivoController::class, 'formData']);
    Route::apiResource('reactivos', ReactivoController::class);

    // Derivaciones de laboratorio
    Route::get('/derivaciones/form-data', [DerivacionController::class, 'formData']);
    Route::get('/derivaciones/{derivacion}/pdf', [DerivacionController::class, 'pdf']);
    Route::get('/derivaciones/{derivacion}/imagen', [DerivacionController::class, 'image']);
    Route::get('/derivaciones', [DerivacionController::class, 'index']);
    Route::post('/derivaciones', [DerivacionController::class, 'store']);
    Route::get('/derivaciones/{derivacion}', [DerivacionController::class, 'show']);
    Route::post('/derivaciones/{derivacion}', [DerivacionController::class, 'update']);
    Route::delete('/derivaciones/{derivacion}', [DerivacionController::class, 'destroy']);
    Route::get('/productos', [ProductoController::class, 'index']);
    Route::post('/productos', [ProductoController::class, 'store']);
    Route::put('/productos/{id}', [ProductoController::class, 'update']);
    Route::delete('/productos/{id}', [ProductoController::class, 'destroy']);

    // Seguros
    Route::get('/seguros', [SeguroController::class, 'index']);
    Route::get('/seguros/{id}/detalle', [SeguroController::class, 'detalle']);
    Route::post('/seguros', [SeguroController::class, 'store']);
    Route::put('/seguros/{id}', [SeguroController::class, 'update']);
    Route::delete('/seguros/{id}', [SeguroController::class, 'destroy']);

    // Pacientes
    Route::get('/pacientes', [PacienteController::class, 'index']);
    Route::get('/pacientes/{id}/internaciones', [PacienteController::class, 'internaciones']);
    Route::get('/pacientes/{id}/estado-cuenta-pdf', [PacienteController::class, 'estadoCuentaPdf']);
    Route::get('/pacientes/{id}/estado-cuenta-categoria-pdf', [PacienteController::class, 'estadoCuentaCategoriaPdf']);
    Route::post('/pacientes/{id}/cobrar-todo', [PacienteController::class, 'cobrarTodo']);
    Route::get('/pacientes/{id}', [PacienteController::class, 'show']);
    Route::post('/pacientes', [PacienteController::class, 'store']);
    Route::put('/pacientes/{id}', [PacienteController::class, 'update']);
    Route::delete('/pacientes/{id}', [PacienteController::class, 'destroy']);

    // Internaciones
    Route::get('/internaciones', [InternacionController::class, 'index']);
    Route::get('/internaciones/{id}/pdf', [InternacionController::class, 'pdf']);
    Route::put('/internaciones/{id}/cerrar', [InternacionController::class, 'cerrar']);
    Route::post('/internaciones/{id}/pagar-total', [InternacionController::class, 'pagarTotal']);
    Route::put('/internaciones/{id}/seguimiento', [InternacionController::class, 'updateSeguimiento']);
    Route::post('/internaciones', [InternacionController::class, 'store']);
    Route::put('/internaciones/{id}', [InternacionController::class, 'update']);
    Route::delete('/internaciones/{id}', [InternacionController::class, 'destroy']);

    // Cargos de internación (productos/servicios)
    Route::post('/internaciones/{internacionId}/items', [InternacionItemController::class, 'store']);
    Route::put('/internacion-items/{id}', [InternacionItemController::class, 'update']);
    Route::delete('/internacion-items/{id}', [InternacionItemController::class, 'destroy']);

    // Proveedores
    Route::get('/proveedores', [ProveedorController::class, 'index']);
    Route::post('/proveedores', [ProveedorController::class, 'store']);
    Route::put('/proveedores/{id}', [ProveedorController::class, 'update']);
    Route::delete('/proveedores/{id}', [ProveedorController::class, 'destroy']);

    // Compras
    Route::get('/compras/export-excel', [CompraController::class, 'exportExcel']);
    Route::get('/compras/{id}/export-excel', [CompraController::class, 'exportDetalleExcel']);
    Route::get('/compras', [CompraController::class, 'index']);
    Route::get('/compras/{id}', [CompraController::class, 'show']);
    Route::post('/compras', [CompraController::class, 'store']);
    Route::delete('/compras/{id}', [CompraController::class, 'destroy']);

    // Ventas por cliente (para facturar): no expone el listado general
    Route::get('/ventas-clientes/clientes', [VentaClienteController::class, 'clientes']);
    Route::get('/ventas-clientes', [VentaClienteController::class, 'index']);

    // Compras por paciente: historial completo y total acumulado de un paciente
    Route::get('/compras-pacientes/pacientes', [PacienteCompraController::class, 'pacientes']);
    Route::get('/compras-pacientes/{pacienteId}', [PacienteCompraController::class, 'show']);

    // Ventas
    Route::get('/ventas', [VentaController::class, 'index']);
    Route::get('/ventas/export-excel', [VentaController::class, 'exportExcel']);
    Route::get('/ventas/export-pdf', [VentaController::class, 'exportPdf']);
    Route::get('/ventas/{id}', [VentaController::class, 'show']);
    // Antes de POST /ventas: un gasto de caja se registra por su propia ruta.
    Route::post('/ventas/gasto', [VentaController::class, 'gasto']);
    Route::post('/ventas', [VentaController::class, 'store']);
    Route::put('/ventas/{id}/completar', [VentaController::class, 'completar']);
    Route::delete('/ventas/{id}', [VentaController::class, 'destroy']);

    // Cierre de caja
    Route::get('/cierres-caja/estado', [CierreCajaController::class, 'estado']);
    Route::get('/cierres-caja', [CierreCajaController::class, 'index']);
    Route::get('/cierres-caja/{id}/ventas/export-excel', [CierreCajaController::class, 'ventasExportExcel']);
    Route::get('/cierres-caja/{id}/ventas/export-pdf', [CierreCajaController::class, 'ventasExportPdf']);
    Route::get('/cierres-caja/{id}/ventas', [CierreCajaController::class, 'ventas']);
    Route::post('/cierres-caja', [CierreCajaController::class, 'store']);
    Route::put('/cierres-caja/{id}/validar', [CierreCajaController::class, 'validar']);
    Route::put('/cierres-caja/{id}', [CierreCajaController::class, 'update']);

    // Ingresos y gastos de Caja Administrativa y Caja General
    Route::get('/caja-movimientos/reporte/pdf', [CajaMovimientoController::class, 'reportePdf']);
    Route::get('/caja-movimientos/reporte/excel', [CajaMovimientoController::class, 'reporteExcel']);
    Route::get('/caja-movimientos/reporte', [CajaMovimientoController::class, 'reporte']);
    Route::get('/caja-movimientos', [CajaMovimientoController::class, 'index']);
    Route::post('/caja-movimientos', [CajaMovimientoController::class, 'store']);
    Route::put('/caja-movimientos/{cajaMovimiento}/anular', [CajaMovimientoController::class, 'anular']);
    Route::put('/caja-movimientos/{cajaMovimiento}', [CajaMovimientoController::class, 'update']);

    // Doctores y especialidades
    Route::get('/especialidades', [DoctorController::class, 'especialidades']);
    Route::post('/especialidades', [DoctorController::class, 'storeEspecialidad']);
    Route::get('/doctores', [DoctorController::class, 'index']);
    Route::get('/doctores/{id}/pacientes', [DoctorController::class, 'pacientes']);
    Route::post('/doctores', [DoctorController::class, 'store']);
    Route::put('/doctores/{id}', [DoctorController::class, 'update']);
    Route::delete('/doctores/{id}', [DoctorController::class, 'destroy']);

});
