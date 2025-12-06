<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class KaryaController extends Controller
{
    // ✅ Upload Karya (pakai user dari token)
    public function store(Request $request)
    {
        $request->validate([
            'nama'      => 'required',
            'deskripsi' => 'required',
            'gambar'    => 'required|image|mimes:jpg,png,jpeg|max:4096',
        ]);

        // ✅ Ambil user dari token
        $user = $request->user();

        // Simpan file
        $path = $request->file('gambar')->store('karya', 'public');

        DB::table('galeri')->insert([
            'user_id'        => $user->user_id, // ✅ Pakai user_id dari token
            'judul'          => $request->nama,
            'caption'        => $request->deskripsi,
            'gambar'         => $path,
            'tanggal_upload' => now(),
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);

        return response()->json([
            'status'   => true,
            'message'  => 'Upload berhasil',
            'file_url' => asset('storage/'.$path)
        ]);
    }

    // ✅ Get Semua Karya (publik)
    public function index()
    {
        $data = DB::table('galeri')
            ->join('users', 'galeri.user_id', '=', 'users.user_id')
            ->select('galeri.*', 'users.nama as uploader_name')
            ->orderBy('galeri.galeri_id', 'DESC')
            ->get();

        return response()->json([
            'status' => true,
            'data'   => $data,
        ]);
    }

    // ✅ Get Karya Pribadi (filter berdasarkan user yang login)
    public function my(Request $request)
    {
        // ✅ Ambil user dari token
        $user = $request->user();

        $data = DB::table('galeri')
            ->where('user_id', $user->user_id) // ✅ Filter by user_id dari token
            ->orderBy('galeri_id', 'DESC')
            ->get();

        return response()->json([
            'status' => true,
            'data' => $data
        ]);
    }

    // ✅ Delete Karya (hanya milik user sendiri)
    public function destroy(Request $request, $id)
    {
        // ✅ Ambil user dari token
        $user = $request->user();

        $karya = DB::table('galeri')
            ->where('galeri_id', $id)
            ->where('user_id', $user->user_id) // ✅ Pastikan karya milik user
            ->first();

        if (!$karya) {
            return response()->json([
                'status' => false,
                'message' => 'Karya tidak ditemukan atau bukan milik Anda'
            ], 404);
        }

        // Hapus file gambar
        if ($karya->gambar && Storage::disk('public')->exists($karya->gambar)) {
            Storage::disk('public')->delete($karya->gambar);
        }

        DB::table('galeri')->where('galeri_id', $id)->delete();

        return response()->json([
            'status' => true,
            'message' => 'Karya berhasil dihapus'
        ]);
    }

    // ✅ Update Karya (hanya milik user sendiri)
    public function update(Request $request, $id)
    {
        $request->validate([
            'nama' => 'required',
            'deskripsi' => 'required'
        ]);

        // ✅ Ambil user dari token
        $user = $request->user();

        // ✅ Cek apakah karya milik user
        $karya = DB::table('galeri')
            ->where('galeri_id', $id)
            ->where('user_id', $user->user_id)
            ->first();

        if (!$karya) {
            return response()->json([
                'status' => false,
                'message' => 'Karya tidak ditemukan atau bukan milik Anda'
            ], 404);
        }

        DB::table('galeri')
            ->where('galeri_id', $id)
            ->update([
                'judul' => $request->nama,
                'caption' => $request->deskripsi,
                'updated_at' => now()
            ]);

        return response()->json([
            'status' => true,
            'message' => 'Karya berhasil diperbarui'
        ]);
    }

    // ✅ Increment View (publik, tidak perlu auth)
    public function incrementView($id)
    {
        $karya = DB::table('galeri')->where('galeri_id', $id)->first();

        if (!$karya) {
            return response()->json([
                'status' => false,
                'message' => 'Karya tidak ditemukan'
            ], 404);
        }

        DB::table('galeri')
            ->where('galeri_id', $id)
            ->increment('views');

        $updatedViews = DB::table('galeri')
            ->where('galeri_id', $id)
            ->value('views');

        return response()->json([
            'status' => true,
            'message' => 'View berhasil ditambahkan',
            'views' => $updatedViews
        ]);
    }
}