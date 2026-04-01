<?php

use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

it('rejects creating a jewel order with zero weight', function () {
    $user = User::factory()->create(['role' => 'Administrador']);
    Sanctum::actingAs($user);

    $cliente = Client::create([
        'name' => 'CLIENTE PESO CERO',
        'ci' => '909090',
        'status' => 'Confiable',
        'cellphone' => '70090909',
        'address' => 'ORURO',
    ]);

    $this->postJson('/api/ordenes', [
        'tipo' => 'Orden',
        'cliente_id' => $cliente->id,
        'fecha_entrega' => now()->toDateString(),
        'detalle' => 'ANILLO PRUEBA',
        'celular' => $cliente->cellphone,
        'peso' => 0,
        'costo_total' => 100,
        'adelanto' => 0,
        'saldo' => 100,
        'tipo_pago' => 'Efectivo',
    ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['peso']);
});
