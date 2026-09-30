<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Venta;
use App\Services\HaciendaService;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Produccion;
use App\Services\EstadoProductivoService;
use App\Models\Animal;
use App\Models\Lote;

class VentaController extends Controller
{
    protected $haciendaService;

    public function __construct(HaciendaService $haciendaService)
    {
        $this->haciendaService = $haciendaService;
    }

    public function index(Request $request)
    {
        $ventas = $this->haciendaService->getVentasPaginated($request);
        $estadisticas = $this->haciendaService->getVentaStatistics();
        $animales = $this->haciendaService->getAvailableAnimals();
        $lotes = $this->haciendaService->getLotes();
        $inventarioProducciones = $this->haciendaService->getInventarioProducciones();
        $inventarioSubproductos = $this->haciendaService->getInventarioSubproductos();
        $compradores = $this->haciendaService->getCompradores();

        return Inertia::render('Ventas/Index', [
            'ventas' => $ventas,
            'estadisticas' => $estadisticas,
            'animales' => $animales,
            'lotes' => $lotes,
            'inventario_producciones' => $inventarioProducciones,
            'inventario_subproductos' => $inventarioSubproductos,
            'compradores' => $compradores,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'tipo_venta'       => 'required|in:animal,lote,produccion,subproducto_faena',
            'vendible_id'      => 'required_if:tipo_venta,animal,lote',
            'producto'         => 'required|string|max:255',
            'comprador_id'     => 'required|exists:compradores,id',
            'fecha_venta'      => 'required|date',
            'cantidad'         => 'required|numeric|min:0.01',
            'unidad'           => 'required|string|max:50',
            'precio_unitario'  => 'required|numeric|min:0.01',
            'precio_total'     => 'required|numeric|min:0.01',
            'metodo_pago'      => 'required|in:efectivo,transferencia,tarjeta,cheque',
            'estado_venta'     => 'required|in:pendiente,completada,cancelada',
            'estado_pago'      => 'required|in:pendiente,parcial,completado',
            'condiciones_entrega' => 'nullable|string',
            'fecha_entrega'    => 'nullable|date',
            'observaciones'    => 'nullable|string|max:500',
        ]);

        // ⭐ Normalizamos clave de producto y validamos stock para producción/subproducto
        if (in_array($validated['tipo_venta'], ['produccion', 'subproducto_faena'])) {
            $productoKey = strtolower($validated['producto']);
            $validated['producto'] = $productoKey;

            $cantidad = (float) $validated['cantidad'];

            if (!$this->haciendaService->validarStock($validated['tipo_venta'], $productoKey, $cantidad)) {
                return back()
                    ->withErrors(['cantidad' => 'Stock insuficiente para ' . $productoKey])
                    ->withInput();
            }
        }

        $vendibleType = null;
        $vendibleId   = null;

        // 🔹 Solo animal y lote usan relación morph (vendible_type / vendible_id)
        if (in_array($validated['tipo_venta'], ['animal', 'lote'])) {
            $vendibleType = match ($validated['tipo_venta']) {
                'animal' => Animal::class,
                'lote'   => Lote::class,
            };

            $vendibleId = $validated['vendible_id'];

            if (!$vendibleType::find($vendibleId)) {
                return back()
                    ->withErrors(['vendible_id' => 'El producto seleccionado no existe'])
                    ->withInput();
            }

            // Antes solo bloqueaba 'muerto'; ahora cubre todos los estados
            // terminales (faeneado, sacrificado, vendido, robado, donado…),
            // que es justo lo que el scope activo() considera fuera del rebaño.
            if ($validated['tipo_venta'] === 'animal') {
                $animal = Animal::find($vendibleId);

                if (EstadoProductivoService::esTerminal($animal->estado_productivo)) {
                    return back()
                        ->withErrors(['vendible_id' => 'Este animal ya no está disponible para venta.'])
                        ->withInput();
                }
            }
        }

        $validated['vendible_type']  = $vendibleType;
        $validated['vendible_id']    = $vendibleId;
        $validated['numero_factura'] = 'FACT-' . date('YmdHis') . '-' . rand(1000, 9999);
        $validated['vendedor_id']    = Auth::id();

        DB::beginTransaction();
        try {
            $venta = Venta::create($validated);

            $this->actualizarEstadoPostVenta($validated, $venta);

            DB::commit();

            return back()->with([
                'message' => 'Venta registrada exitosamente',
                'type'    => 'success',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Error al crear venta', [
                'exception' => $e->getMessage(),
                'trace'     => $e->getTraceAsString(),
            ]);

            return back()->withErrors([
                'message' => 'Error al registrar la venta: ' . $e->getMessage(),
            ])->withInput();
        }
    }

    public function updateEstado(Request $request, Venta $venta)
    {
        $validated = $request->validate([
            'estado_venta' => 'required|in:pendiente,completada,cancelada',
            'estado_pago'  => 'required|in:pendiente,parcial,completado',
        ]);

        DB::transaction(function () use ($venta, $validated) {
            $venta->update($validated);

            // $venta->fresh() en vez de toArray() sobre la instancia vieja:
            // necesitamos tipo_venta y vendible_* ya persistidos junto al
            // estado nuevo, no una mezcla manual de arrays.
            $this->actualizarEstadoPostVenta($venta->fresh()->toArray(), $venta);
        });

        return back()->with(['message' => 'Estado de venta actualizado', 'type' => 'success']);
    }

    /**
     * Actualiza estado de animal/lote después de la venta.
     *
     * Solo una venta COMPLETADA saca al animal del rebaño. Mientras esté
     * pendiente (o cancelada) el animal sigue intacto: activo, editable y
     * disponible para pesajes, producción y cualquier otro registro.
     */
    private function actualizarEstadoPostVenta(array $data, Venta $venta): void
    {
        if ($data['estado_venta'] !== 'completada') {
            return;
        }

        if ($data['tipo_venta'] === 'animal' && $venta->vendible_type === Animal::class) {
            $animal = Animal::find($venta->vendible_id);

            if ($animal) {
                $this->marcarVendido($animal);
            }
        }

        if ($data['tipo_venta'] === 'lote' && $venta->vendible_type === Lote::class) {
            $lote = Lote::with('animales')->find($venta->vendible_id);

            if ($lote) {
                foreach ($lote->animales as $animal) {
                    $this->marcarVendido($animal);
                }
            }
        }
    }
    
    /**
     * El animal sale del rebaño: el scope Animal::activo() lo excluye
     * automáticamente porque 'vendido' está en estadosSistema().
     */
    private function marcarVendido(Animal $animal): void
    {
        Animal::conEdicionTerminalPermitida(function () use ($animal) {
            $animal->update(['estado_productivo' => 'vendido']);
        });
    }
            
    
    public function destroy(Venta $venta)
        {
            DB::transaction(function () use ($venta) {
                $animales = match (true) {
                    $venta->tipo_venta === 'animal' && $venta->vendible_type === Animal::class =>
                        Animal::whereKey($venta->vendible_id)->get(),
                    $venta->tipo_venta === 'lote' && $venta->vendible_type === Lote::class =>
                        Lote::with('animales')->find($venta->vendible_id)?->animales ?? collect(),
                    default => collect(),
                };

                $ventaId = $venta->id;

                $venta->delete();

                foreach ($animales as $animal) {
                    if ($animal->estado_productivo !== 'vendido') {
                        continue;
                    }

                    // Solo reactiva si no queda ninguna OTRA venta completada
                    // vigente sobre este animal — evita reactivarlo si había una
                    // venta duplicada y solo se borró una de las dos.
                    $otraVentaVigente = $animal->ventas()
                        ->where('estado_venta', 'completada')
                        ->where('id', '!=', $ventaId)
                        ->exists();

                    if ($otraVentaVigente) {
                        continue;
                    }

                    Animal::conEdicionTerminalPermitida(function () use ($animal) {
                        $animal->update([
                            'estado_productivo' => EstadoProductivoService::estadoInicial($animal->especie),
                        ]);
                    });
                }
            });

            return back()->with(['message' => 'Venta eliminada exitosamente', 'type' => 'success']);
        }
}