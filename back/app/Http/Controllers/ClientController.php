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
            ->orWhere('cellphone', 'like', '%' . $search . '%')
            ->orderBy('id', 'desc')
            ->paginate($perPage);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'ci' => 'required|unique:clients',
        ]);

        return Client::create($this->normalizeClientData($request->all()));
    }

    public function update(Request $request, Client $client)
    {
        $request->validate([
            'name' => 'required|string',
//            'ci' => 'required|unique:clients,ci,' . $client->id,
        ]);

        $client->update($this->normalizeClientData($request->all()));
        return $client;
    }

    public function destroy(Client $client)
    {
        $client->delete();
        return response()->json(['message' => 'Cliente eliminado']);
    }

    private function normalizeClientData(array $data): array
    {
        $fieldsToUpper = ['name', 'ci', 'address', 'observation'];

        foreach ($fieldsToUpper as $field) {
            if (isset($data[$field]) && is_string($data[$field])) {
                $data[$field] = mb_strtoupper(trim($data[$field]), 'UTF-8');
            }
        }

        return $data;
    }
}
