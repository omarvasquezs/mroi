<?php

namespace App\Http\Controllers;

use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UsuarioController extends Controller
{
    public function index(Request $request)
    {
        $query = Usuario::query();

        if ($request->has('username') && $request->username) {
            $query->where('username', 'like', '%' . $request->username . '%');
        }

        if ($request->has('name') && $request->name) {
            $query->where('name', 'like', '%' . $request->name . '%');
        }

        if ($request->has('role') && $request->role) {
            $query->where('role', $request->role);
        }

        if ($request->has('estado') && $request->estado !== '') {
            $query->where('estado', $request->estado);
        }

        $usuarios = $query->paginate(10);
        return response()->json($usuarios);
    }

    public function store(Request $request)
    {
        $usuario = Usuario::create($request->all());
        return response()->json($usuario, 201);
    }

    public function show($id)
    {
        $usuario = Usuario::findOrFail($id);
        return response()->json($usuario);
    }

    public function update(Request $request, $id)
    {
        $usuario = Usuario::findOrFail($id);
        $data = $request->all();
        if (empty($data['password'])) {
            unset($data['password']);
        }
        $usuario->update($data);
        return response()->json($usuario);
    }

    public function destroy($id)
    {
        Usuario::destroy($id);
        return response()->json(null, 204);
    }

    public function checkUsername(Request $request)
    {
        $username = $request->query('username');
        $exists = Usuario::where('username', $username)->exists();
        return response()->json(['exists' => $exists]);
    }

    public function login(Request $request)
    {
        $credentials = $request->only('username', 'password');
        $user = Usuario::where('username', $credentials['username'])->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            return response()->json(['error' => 'Credenciales Invalidas'], 401);
        }

        $token = $user->createToken('default')->plainTextToken;
        return response()->json([
            'user' => $user,
            'token' => $token
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();
        return response()->json(['message' => 'Logged out']);
    }

    public function changePassword(Request $request)
    {
        $request->validate([
            'new_password' => 'required|min:6',
            'password_confirmation' => 'required|same:new_password',
            'username' => 'required'
        ]);

        $user = Usuario::where('username', $request->username)->first();
        if (!$user) {
            return response()->json(['error' => 'Usuario no encontrado'], 404);
        }

        $user->password = Hash::make($request->new_password);
        $user->save();

        return response()->json([
            'message' => 'Clave actualizada exitosamente',
            'success' => true
        ]);
    }
}
