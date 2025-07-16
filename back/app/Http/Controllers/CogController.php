<?php

namespace App\Http\Controllers;

use App\Models\Cog;
use Illuminate\Http\Request;

class CogController extends Controller
{
    public function index()
    {
        return Cog::all();
    }

    public function update(Request $request, Cog $cog)
    {
        $request->validate([
            'value' => 'required|numeric|min:0',
        ]);
        $cog->update(['value' => $request->value]);
        return response()->json(['message' => 'Actualizado correctamente']);
    }
}
