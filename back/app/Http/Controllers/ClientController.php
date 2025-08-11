<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Http\Request;

class ClientController extends Controller{
    public function index(Request $request){
        $search = $request->input('search', '');
        $perPage = $request->input('per_page', 100);
//        return Client::orderBy('id', 'desc')->get();
        return Client::where('name', 'like', '%' . $search . '%')
            ->orWhere('ci', 'like', '%' . $search . '%')
            ->orderBy('id', 'desc')
            ->paginate($perPage);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'ci' => 'required|unique:clients',
        ]);

        return Client::create($request->all());
    }

    public function update(Request $request, Client $client)
    {
        $request->validate([
            'name' => 'required|string',
//            'ci' => 'required|unique:clients,ci,' . $client->id,
        ]);

        $client->update($request->all());
        return $client;
    }

    public function destroy(Client $client)
    {
        $client->delete();
        return response()->json(['message' => 'Cliente eliminado']);
    }
}
