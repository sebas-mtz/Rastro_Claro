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
            'vendible_id' => 'required_if:tipo_venta,animal,lote',
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
            // En el modal mandamos la clave interna: 'leche','lana','huevo','carne','cuero',...
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
        }

        $validated['vendible_type'] = $vendibleType;
        $validated['vendible_id']   = $vendibleId;
        $validated['numero_factura'] = 'FACT-' . date('YmdHis') . '-' . rand(1000, 9999);
        $validated['vendedor_id']    = Auth::id();

        DB::beginTransaction();
        try {
            $venta = Venta::create($validated);

            $this->actualizarEstadoPostVenta($validated, $venta);

            DB::commit();

            return redirect()->route('ventas.index')->with([
                'message' => 'Venta registrada exitosamente',
                'type'    => 'success',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            // 👀 Para ver el error exacto en laravel.log
            Log::error('Error al crear venta', [
                'exception' => $e->getMessage(),
                'trace'     => $e->getTraceAsString(),
            ]);

            return back()->withErrors([
                'message' => 'Error al registrar la venta: ' . $e->getMessage(),
            ])->withInput();
        }
    }

    /**
     * Actualiza estado de animal/lote después de la venta
     */
    private function actualizarEstadoPostVenta(array $data, Venta $venta): void
    {
        // Solo aplica para venta de animal o lote
        if ($data['tipo_venta'] === 'animal' && $venta->vendible_type === Animal::class) {
            /** @var \App\Models\Animal $animal */
            $animal = Animal::find($venta->vendible_id);
            if ($animal) {
                $animal->estado_productivo = 'vendido';
                $animal->save();
            }
        }

        if ($data['tipo_venta'] === 'lote' && $venta->vendible_type === Lote::class) {
            /** @var \App\Models\Lote $lote */
            $lote = Lote::with('animales')->find($venta->vendible_id);
            if ($lote) {
                foreach ($lote->animales as $animal) {
                    $animal->estado_productivo = 'vendido';
                    $animal->save();
                }
            }
        }

        // Para produccion y subproducto_faena no cambiamos estados de animales
    }
}
