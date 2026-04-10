<?php

use App\Models\Client;
use App\Models\Cog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

it('stores the current prestamos para comprar value in precio oro when creating a prestamo', function () {
    $user = User::factory()->create();
    $client = Client::create([
        'name' => 'Cliente prueba',
        'ci' => '1234567',
        'cellphone' => '70000000',
        'status' => 'Confiable',
    ]);

    Cog::query()->updateOrCreate(
        ['name' => 'Prestamos para comprar'],
        ['value' => 900.00, 'description' => 'Interes por prestamos para comprar oro']
    );

    Sanctum::actingAs($user);

    $this->postJson('/api/prestamos', [
        'cliente_id' => $client->id,
        'user_id' => $user->id,
        'fecha_limite' => now()->addMonth()->toDateString(),
        'peso' => 10,
        'merma' => 1,
        'valor_prestado' => 500,
        'interes' => 3,
        'almacen' => 2,
        'celular' => '70000000',
        'detalle' => 'Prueba de precio compra',
    ])->assertCreated();

    $this->assertDatabaseHas('prestamos', [
        'cliente_id' => $client->id,
        'precio_oro' => 900.00,
    ]);
});
