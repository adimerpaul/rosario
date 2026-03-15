<?php

use App\Models\Client;
use App\Models\Orden;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

it('allows admin to toggle metodo of an orden adelanto', function () {
    $admin = User::factory()->create(['role' => 'Administrador']);
    Sanctum::actingAs($admin);

    $cliente = Client::create([
        'name' => 'CLIENTE ORDEN',
        'ci' => '654321',
        'status' => 'Confiable',
        'cellphone' => '77770000',
        'address' => 'ORURO',
    ]);

    $orden = Orden::create([
        'numero' => 'O0001-2026',
        'tipo' => 'Orden',
        'fecha_creacion' => now(),
        'fecha_entrega' => now()->toDateString(),
        'detalle' => 'ORDEN PRUEBA',
        'celular' => $cliente->cellphone,
        'costo_total' => 1000,
        'adelanto' => 300,
        'saldo' => 700,
        'estado' => 'Pendiente',
        'peso' => 1,
        'tipo_pago' => 'Efectivo',
        'user_id' => $admin->id,
        'cliente_id' => $cliente->id,
    ]);

    $this->postJson("/api/ordenes/{$orden->id}/toggle-metodo")
        ->assertOk()
        ->assertJsonFragment([
            'id' => $orden->id,
            'tipo_pago' => 'QR',
        ]);

    $this->assertDatabaseHas('ordenes', [
        'id' => $orden->id,
        'tipo_pago' => 'QR',
    ]);
});
