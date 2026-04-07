<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

it('lists default overdue loan whatsapp message templates', function () {
    Sanctum::actingAs(User::factory()->create(['role' => 'Administrador']));

    $this->getJson('/api/prestamosRetrasados/mensajes')
        ->assertOk()
        ->assertJsonPath('data.0.clave', 'prestamo_regularizacion')
        ->assertJsonPath('data.1.clave', 'prestamo_fundicion')
        ->assertJsonFragment(['#NOMBRE#'])
        ->assertJsonFragment(['#FECHA#']);
});

it('updates overdue loan whatsapp message templates', function () {
    Sanctum::actingAs(User::factory()->create(['role' => 'Administrador']));

    $this->putJson('/api/prestamosRetrasados/mensajes', [
        'mensajes' => [
            'prestamo_regularizacion' => 'Hola #NOMBRE#, regularice antes del #FECHA#.',
            'prestamo_fundicion' => 'Hola #NOMBRE#, su joya pasara a fundicion si no paga.',
        ],
    ])
        ->assertOk()
        ->assertJsonFragment([
            'message' => 'Mensajes actualizados correctamente',
        ]);

    $this->assertDatabaseHas('mensaje_plantillas', [
        'clave' => 'prestamo_regularizacion',
        'contenido' => 'Hola #NOMBRE#, regularice antes del #FECHA#.',
    ]);

    $this->assertDatabaseHas('mensaje_plantillas', [
        'clave' => 'prestamo_fundicion',
        'contenido' => 'Hola #NOMBRE#, su joya pasara a fundicion si no paga.',
    ]);
});
