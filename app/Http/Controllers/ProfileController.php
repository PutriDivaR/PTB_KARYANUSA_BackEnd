<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    // 🔹 1. Ambil data profile user
    public function show($id)
    {
        return User::findOrFail($id);
    }
    
    // 🔹 2. Update profile user (nama dan email saja)
    public function update(Request $request, $id)
    {
        $request->validate([
            'nama' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $id . ',user_id'
        ]);
        
        $user = User::findOrFail($id);
        
        $user->update([
            'nama' => $request->nama ?? $user->nama,
            'email' => $request->email ?? $user->email,
        ]);
        
        return response()->json($user, 200);
    }
}