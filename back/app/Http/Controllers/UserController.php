<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

class UserController extends Controller
{
    public function updateAvatar(Request $request, $userId)
    {
        $user = User::find($userId);
        if (! $user) {
            return response()->json(['message' => 'Usuario no encontrado'], 404);
        }

        if ($request->hasFile('avatar')) {
            $file = $request->file('avatar');
            $filename = time().'.'.$file->getClientOriginalExtension();
            $path = public_path('images/'.$filename);

            // Crear instancia del gestor de imágenes
            $manager = new ImageManager(new Driver); // O new Imagick\Driver()

            // Redimensionar y comprimir
            $manager->read($file->getPathname())
                ->resize(300, 300) // o no pongas resize si no quieres cambiar tamaño
                ->toJpeg(70)       // calidad 70%
                ->save($path);

            $user->avatar = $filename;
            $user->save();

            return response()->json(['message' => 'Avatar actualizado', 'avatar' => $filename]);
        }

        return response()->json(['message' => 'No se ha enviado un archivo'], 400);
    }

    public function login(Request $request)
    {
        $credentials = $request->only('username', 'password');
        $user = User::where('username', $credentials['username'])->first();
        if (! $user || ! password_verify($credentials['password'], $user->password)) {
            return response()->json([
                'message' => 'Usuario o contraseña incorrectos',
            ], 401);
        }
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => $user,
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Token eliminado',
        ]);
    }

    public function me(Request $request)
    {
        return $request->user();
    }

    private function ensureAdmin(Request $request): void
    {
        abort_unless($request->user()?->role === 'Administrador', 403, 'No autorizado');
    }

    public function index(Request $request)
    {
        return User::where('id', '!=', 0)
//            ->with('docente')
            ->orderBy('id', 'desc')
            ->get();
    }

    public function update(Request $request, $id)
    {
        $isAdmin = $request->user()?->role === 'Administrador';
        if ($request->has('role') && ! $isAdmin) {
            abort(403, 'Solo el administrador puede cambiar el rol');
        }

        $user = User::findOrFail($id);
        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string'],
            'username' => ['sometimes', 'required', Rule::unique('users')->ignore($user->id)],
            'role' => ['sometimes', 'required', Rule::in(['Administrador', 'Vendedor'])],
            'email' => ['sometimes', 'nullable', 'email', Rule::unique('users')->ignore($user->id)],
            'avatar' => ['sometimes', 'nullable', 'string'],
        ]);

        $user->update($validated);

        return $user;
    }

    public function updatePassword(Request $request, $id)
    {
        $this->ensureAdmin($request);

        $user = User::find($id);
        $user->update([
            'password' => bcrypt($request->password),
        ]);

        return $user;
    }

    public function store(Request $request)
    {
        $this->ensureAdmin($request);

        $request->validate([
            'username' => 'required|unique:users',
            'password' => 'required',
            'name' => 'required',
            'role' => ['nullable', Rule::in(['Administrador', 'Vendedor'])],
            //            'email' => 'required|email|unique:users',
        ]);
        $user = User::create([
            ...$request->only(['name', 'email', 'password', 'username', 'avatar', 'docente_id']),
            'role' => $request->input('role', 'Vendedor'),
        ]);

        return $user;
    }

    public function destroy(Request $request, $id)
    {
        $this->ensureAdmin($request);

        return User::destroy($id);
    }
}
