<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validated = $request->validate([
            'nama' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users',
            'password' => 'required|string|min:4',
        ]);

        $user = User::create([
            'nama' => $validated['nama'],
            'username' => $validated['username'],
            'password' => Hash::make($validated['password']),
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Register berhasil',
            'user' => $user
        ], 201);
    }

    public function login(Request $request)
    {
        if (!Auth::attempt($request->only('username', 'password'))) {
            return response()->json([
                'status' => false,
                'message' => 'username atau password salah'
            ], 401);
        }

        $user = Auth::user();
        $token = $user->createToken('token')->plainTextToken;

        return response()->json([
            'status' => true,
            'message' => 'Login berhasil',
            'token' => $token,
            'user_id' =>  $user->user_id,
            'nama' => $user->nama,
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();
        return response()->json(['message' => 'Logout berhasil']);
    }

    public function me(Request $request)
    {
        return response()->json($request->user());
    }

    public function searchUser(Request $request)
    {
        $keyword = $request->query('username');

        $users = User::where('username', 'like', "%$keyword%")
            ->limit(10)
            ->get(['user_id', 'nama', 'username']);

        return response()->json($users);
    }

    public function getAllUsers(Request $request)
    {
        $currentUserId = $request->user()->id;

        $users = User::where('user_id', '!=', $currentUserId)
                    ->select('user_id', 'username')
                    ->get();

        return response()->json($users, 200);
    }

    public function updateFcmToken(Request $request) {
        $user = $request->user();
        $request->validate(['fcm_token' => 'nullable|string']);
        $user->fcm_token = $request->fcm_token;
        $user->save();
        return response()->json(['success' => true]);
    }

}
