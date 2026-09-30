<?php

namespace Tests\Feature;

use App\Models\Animal;
use App\Models\AnimalValuation;
use App\Models\User;
use Database\Seeders\AlertaSeeder;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\SaludSeeder;
use Database\Seeders\ValuacionEjemploSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class DatabaseSchemaTest extends TestCase
{
    use RefreshDatabase;

    public static function factories(): array
    {
        $factories = [];
        foreach (glob(dirname(__DIR__, 2).'/database/factories/*Factory.php') as $file) {
            $name = basename($file, '.php');
            $factories[$name] = ['Database\\Factories\\'.$name];
        }

        return $factories;
    }

    #[DataProvider('factories')]
    public function test_every_factory_persists_columns_and_relations_from_migrations(string $factory): void
    {
        $this->actingAs(User::factory()->create());
        $model = $factory::new()->create();

        $this->assertTrue($model->exists);
        $this->assertSame([], array_diff(
            array_keys($model->getAttributes()),
            Schema::getColumnListing($model->getTable()),
        ));
        $this->assertDatabaseHas($model->getTable(), ['id' => $model->getKey()]);
    }

    public function test_all_seeders_work_with_the_migrated_schema(): void
    {
        $this->seed([DatabaseSeeder::class, AlertaSeeder::class, SaludSeeder::class]);

        $this->assertDatabaseHas('bajas', ['tipo_salida' => 'fallecimiento']);
        $this->assertDatabaseHas('pesajes', ['notas' => 'Peso de nacimiento']);
        $this->assertDatabaseHas('pajillas', ['capacidad_pajilla' => 0.25]);

        $this->seed(ValuacionEjemploSeeder::class);
        $this->seed(ValuacionEjemploSeeder::class);

        $animal = Animal::where('arete', 'DEMO-001')->firstOrFail();
        $this->assertSame('H', $animal->sexo);
        $this->assertSame('DEMO-REG-001', $animal->numero_registro);
        $this->assertDatabaseHas('animal_valuations', [
            'animal_id' => $animal->id,
            'costo_total_produccion' => 8000,
            'precio_estimado' => 17000,
        ]);
        $this->assertSame(2, AnimalValuation::count());
        $this->assertDatabaseHas('animal_valuations', [
            'animal_id' => Animal::where('arete', 'DEMO-002')->value('id'),
            'costo_gestacion' => 600,
        ]);

        if (DB::getDriverName() === 'sqlite') {
            $this->assertSame([], DB::select('PRAGMA foreign_key_check'));
        }
    }

    public function test_tenant_scopes_only_target_models_with_an_owner_column(): void
    {
        foreach (config('tenancy.models') as $modelClass) {
            $this->assertTrue(Schema::hasColumn((new $modelClass)->getTable(), 'owner_id'), $modelClass);
        }
    }

    public function test_animal_preserves_current_identification_and_origin_columns(): void
    {
        $animal = Animal::create([
            'especie' => 'Ovino', 'sexo' => 'H', 'arete' => 'CAMPOS-VIGENTES',
            'siniiga_id' => 'SIN-001', 'identificador' => 'ID-001',
            'numero_registro' => 'REG-001', 'grado_pureza' => 'Puro',
            'lectura_microchip' => 'CHIP-001', 'color' => 'Blanco',
            'estado_reproductivo' => 'vacia', 'tipo_origen' => 'comprado',
            'fecha_adquisicion' => '2026-01-01', 'proveedor_origen' => 'Rancho origen',
        ]);

        $this->assertDatabaseHas('animals', $animal->getAttributes());
        $this->assertSame('Rancho origen', $animal->fresh()->proveedor_origen);
        $this->assertSame('vacia', $animal->fresh()->getRawOriginal('estado_reproductivo'));
    }
}
