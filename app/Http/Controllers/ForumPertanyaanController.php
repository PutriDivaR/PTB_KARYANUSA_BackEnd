<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ForumPertanyaan;
use App\Models\ForumJawaban;
use Illuminate\Http\Request;

class ForumPertanyaanController extends Controller
{
    // 🔹 1. Ambil semua pertanyaan + jawaban
    public function index()
    {
        return ForumPertanyaan::with(['user', 'jawaban.user'])
            ->orderBy('tanggal', 'desc')
            ->get();
    }

    // 🔹 2. Tambah pertanyaan baru
    public function store(Request $request)
    {
        $request->validate([
            'isi' => 'required|string',
            'image_forum' => 'nullable|image|mimes:jpeg,jpg,png|max:5120'
        ]);

        $userId = auth()->id();

        if (!$userId) {
            return response()->json([
                'status' => false,
                'message' => 'User tidak terautentikasi'
            ], 401);
        }

        $imagePath = null;
        if ($request->hasFile('image_forum')) {
            $file = $request->file('image_forum');
            $filename = time() . '_' . $file->getClientOriginalName();
            $file->move(public_path('uploads/forum'), $filename);
            $imagePath = url('uploads/forum/' . $filename);
        }

        $pertanyaan = ForumPertanyaan::create([
            'isi' => $request->isi,
            'image_forum' => $imagePath,
            'user_id' => $userId,
            'tanggal' => now(),
        ]);

        $pertanyaan->load(['user', 'jawaban.user']);

        return response()->json($pertanyaan, 201);
    }

    // 🔹 3. Detail pertanyaan + jawaban
    public function show($id)
    {
        $pertanyaan = ForumPertanyaan::with(['user', 'jawaban.user'])
            ->findOrFail($id);
        
        return response()->json($pertanyaan);
    }

    // 🔹 4. Tambah jawaban untuk sebuah pertanyaan
    public function jawaban(Request $request, $id)
    {
        $request->validate([
            'isi' => 'required|string',
            'image_jawaban' => 'nullable|image|mimes:jpeg,jpg,png|max:5120'
        ]);

        $userId = auth()->id();

        if (!$userId) {
            return response()->json([
                'status' => false,
                'message' => 'User tidak terautentikasi'
            ], 401);
        }

        // Handle upload gambar jawaban
        $imagePath = null;
        if ($request->hasFile('image_jawaban')) {
            $file = $request->file('image_jawaban');
            $filename = time() . '_jawaban_' . $file->getClientOriginalName();
            $file->move(public_path('uploads/forum/jawaban'), $filename);
            $imagePath = url('uploads/forum/jawaban/' . $filename);
        }

        $jawaban = ForumJawaban::create([
            'pertanyaan_id' => $id,
            'isi' => $request->isi,
            'image_jawaban' => $imagePath,
            'user_id' => $userId,
            'tanggal' => now(),
        ]);

        $jawaban->load('user');

        return response()->json($jawaban, 201);
    }
}