<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{

    public function show($id)
    {
        try {
            $user = User::find($id);
            
            if (!$user) {
                return response()->json([
                    'status' => false,
                    'message' => 'User tidak ditemukan'
                ], 404);
            }

            return response()->json([
                'status' => true,
                'message' => 'Profile berhasil dimuat',
                'data' => [
                    'user_id' => $user->user_id,
                    'nama' => $user->nama,
                    'username' => $user->username,
                    'bio' => $user->bio ?? '—',
                    'foto_profile' => $user->foto_profile ? url('storage/' . $user->foto_profile) : null, // ✅
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error fetching profile: ' . $e->getMessage());
            
            return response()->json([
                'status' => false,
                'message' => 'Terjadi kesalahan saat memuat profile'
            ], 500);
        }
    }
    

    public function updatePhoto(Request $request, $id)
    {
        try {
            $user = User::find($id);
            
            if (!$user) {
                return response()->json([
                    'status' => false,
                    'message' => 'User tidak ditemukan'
                ], 404);
            }

            $authUser = Auth::user();
            if ($authUser && $authUser->user_id !== $user->user_id) {
                return response()->json([
                    'status' => false,
                    'message' => 'Anda tidak memiliki akses'
                ], 403);
            }

            $request->validate([
                'foto_profile' => 'required|image|mimes:jpeg,png,jpg|max:5120', // max 5MB
            ]);

      
            if ($user->foto_profile && Storage::exists('public/' . $user->foto_profile)) {
                Storage::delete('public/' . $user->foto_profile);
            }

          
            $file = $request->file('foto_profile');
            $filename = 'profile_' . $user->user_id . '_' . time() . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('profile_photos', $filename, 'public');

        
            $user->foto_profile = $path;
            $user->save();

            Log::info("Profile photo updated for user_id: {$user->user_id}");

            return response()->json([
                'status' => true,
                'message' => 'Foto profil berhasil diperbarui',
                'data' => [
                    'user_id' => $user->user_id,
                    'nama' => $user->nama,
                    'username' => $user->username,
                    'bio' => $user->bio ?? '—',
                    'foto_profile' => url('storage/' . $user->foto_profile),
                ]
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Validasi gagal',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            Log::error('Error updating profile photo: ' . $e->getMessage());
            
            return response()->json([
                'status' => false,
                'message' => 'Terjadi kesalahan saat memperbarui foto'
            ], 500);
        }
    }


    public function update(Request $request, $id)
    {
        try {
            $user = User::find($id);
            
            if (!$user) {
                return response()->json([
                    'status' => false,
                    'message' => 'User tidak ditemukan'
                ], 404);
            }

            $authUser = Auth::user();
            if ($authUser && $authUser->user_id !== $user->user_id) {
                return response()->json([
                    'status' => false,
                    'message' => 'Anda tidak memiliki akses'
                ], 403);
            }

            if ($request->has('username')) {
                return response()->json([
                    'status' => false,
                    'message' => 'Username tidak dapat diubah'
                ], 400);
            }

            $validated = $request->validate([
                'nama' => 'sometimes|required|string|min:2|max:100',
                'bio' => 'sometimes|nullable|string|max:500',
            ]);

            $updated = false;
            
            if ($request->has('nama')) {
                $user->nama = $validated['nama'];
                $updated = true;
            }
            
            if ($request->has('bio')) {
                $user->bio = $validated['bio'];
                $updated = true;
            }

            if (!$updated) {
                return response()->json([
                    'status' => false,
                    'message' => 'Tidak ada perubahan'
                ], 400);
            }

            $user->save();

            return response()->json([
                'status' => true,
                'message' => 'Profile berhasil diperbarui',
                'data' => [
                    'user_id' => $user->user_id,
                    'nama' => $user->nama,
                    'username' => $user->username,
                    'bio' => $user->bio ?? '—',
                    'foto_profile' => $user->foto_profile ? url('storage/' . $user->foto_profile) : null,
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error updating profile: ' . $e->getMessage());
            
            return response()->json([
                'status' => false,
                'message' => 'Terjadi kesalahan'
            ], 500);
        }
    }
}