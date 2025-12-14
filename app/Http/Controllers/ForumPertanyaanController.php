<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ForumPertanyaan;
use App\Models\ForumJawaban;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ForumPertanyaanController extends Controller
{
    // 🔹 1. Ambil semua pertanyaan + jawaban
    public function index()
    {
        try {
            $pertanyaan = ForumPertanyaan::with(['user', 'jawaban.user'])
                ->orderBy('tanggal', 'desc')
                ->get();
            
            return response()->json($pertanyaan, 200);
        } catch (\Exception $e) {
            Log::error('Error fetching pertanyaan: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Gagal memuat pertanyaan'
            ], 500);
        }
    }

    // 🔹 2. Tambah pertanyaan baru
    public function store(Request $request)
    {
        try {
            $request->validate([
                'isi' => 'required|string|max:2000',
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
                $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                
                // Buat folder jika belum ada
                $uploadPath = public_path('uploads/forum');
                if (!File::exists($uploadPath)) {
                    File::makeDirectory($uploadPath, 0755, true);
                }
                
                $file->move($uploadPath, $filename);
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
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Validasi gagal',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error creating pertanyaan: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Gagal menambah pertanyaan: ' . $e->getMessage()
            ], 500);
        }
    }

    // 🔹 3. Detail pertanyaan + jawaban
    public function show($id)
    {
        try {
            $pertanyaan = ForumPertanyaan::with(['user', 'jawaban.user'])
                ->findOrFail($id);
            
            return response()->json($pertanyaan, 200);
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Pertanyaan tidak ditemukan'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error fetching pertanyaan detail: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Gagal memuat detail pertanyaan'
            ], 500);
        }
    }

    // 🔹 4. Tambah jawaban untuk sebuah pertanyaan
    public function jawaban(Request $request, $id)
    {
        try {
            $request->validate([
                'isi' => 'required|string|max:2000',
                'image_jawaban' => 'nullable|image|mimes:jpeg,jpg,png|max:5120'
            ]);

            // Cek apakah pertanyaan ada
            $pertanyaan = ForumPertanyaan::findOrFail($id);

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
                $filename = time() . '_jawaban_' . uniqid() . '.' . $file->getClientOriginalExtension();
                
                // Buat folder jika belum ada
                $uploadPath = public_path('uploads/forum/jawaban');
                if (!File::exists($uploadPath)) {
                    File::makeDirectory($uploadPath, 0755, true);
                }
                
                $file->move($uploadPath, $filename);
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
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Pertanyaan tidak ditemukan'
            ], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Validasi gagal',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error creating jawaban: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Gagal menambah jawaban: ' . $e->getMessage()
            ], 500);
        }
    }

    // 🔥 5. UPDATE Pertanyaan - SUPPORT MULTIPART/FORM-DATA
    public function update(Request $request, $id)
    {
        try {
            // Log untuk debugging
            Log::info('Update pertanyaan dimulai', [
                'id' => $id,
                'method' => $request->method(),
                'content_type' => $request->header('Content-Type'),
                'has_file' => $request->hasFile('image_forum'),
                'all_data' => $request->all()
            ]);

            $pertanyaan = ForumPertanyaan::findOrFail($id);
            
            // Validasi bahwa user adalah pemilik pertanyaan
            if ($pertanyaan->user_id !== auth()->id()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Anda tidak memiliki akses untuk mengubah pertanyaan ini'
                ], 403);
            }
            
            // Validasi input
            $validated = $request->validate([
                'isi' => 'required|string|max:2000',
                'image_forum' => 'nullable|image|mimes:jpeg,png,jpg|max:5120'
            ]);
            
            // Update isi pertanyaan
            $pertanyaan->isi = $validated['isi'];
            
            // Handle upload gambar baru
            if ($request->hasFile('image_forum')) {
                Log::info('Gambar baru terdeteksi, memproses upload...');
                
                // Hapus gambar lama jika ada
                if ($pertanyaan->image_forum) {
                    // Ekstrak nama file dari URL
                    $oldImagePath = public_path('uploads/forum/' . basename($pertanyaan->image_forum));
                    if (File::exists($oldImagePath)) {
                        File::delete($oldImagePath);
                        Log::info('Gambar lama dihapus: ' . $oldImagePath);
                    }
                }
                
                // Upload gambar baru
                $file = $request->file('image_forum');
                $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                
                // Buat folder jika belum ada
                $uploadPath = public_path('uploads/forum');
                if (!File::exists($uploadPath)) {
                    File::makeDirectory($uploadPath, 0755, true);
                }
                
                $file->move($uploadPath, $filename);
                $pertanyaan->image_forum = url('uploads/forum/' . $filename);
                
                Log::info('Gambar baru berhasil diupload: ' . $pertanyaan->image_forum);
            }
            
            $pertanyaan->save();
            
            // Load relasi
            $pertanyaan->load(['user', 'jawaban.user']);
            
            Log::info('Update pertanyaan berhasil', ['pertanyaan_id' => $pertanyaan->pertanyaan_id]);
            
            return response()->json($pertanyaan, 200);
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::error('Pertanyaan tidak ditemukan: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Pertanyaan tidak ditemukan'
            ], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validasi gagal: ' . json_encode($e->errors()));
            return response()->json([
                'status' => false,
                'message' => 'Validasi gagal',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error updating pertanyaan: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            return response()->json([
                'status' => false,
                'message' => 'Gagal update pertanyaan: ' . $e->getMessage()
            ], 500);
        }
    }

    // 🔥 6. DELETE Pertanyaan
    public function destroy($id)
    {
        try {
            Log::info('Delete pertanyaan dimulai', ['id' => $id]);
            
            $pertanyaan = ForumPertanyaan::findOrFail($id);
            
            // Validasi bahwa user adalah pemilik pertanyaan
            if ($pertanyaan->user_id !== auth()->id()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Anda tidak memiliki akses untuk menghapus pertanyaan ini'
                ], 403);
            }
            
            // Hapus gambar pertanyaan jika ada
            if ($pertanyaan->image_forum) {
                $imagePath = public_path('uploads/forum/' . basename($pertanyaan->image_forum));
                if (File::exists($imagePath)) {
                    File::delete($imagePath);
                    Log::info('Gambar pertanyaan dihapus: ' . $imagePath);
                }
            }
            
            // Hapus gambar jawaban jika ada
            foreach ($pertanyaan->jawaban as $jawaban) {
                if ($jawaban->image_jawaban) {
                    $jawabanImagePath = public_path('uploads/forum/jawaban/' . basename($jawaban->image_jawaban));
                    if (File::exists($jawabanImagePath)) {
                        File::delete($jawabanImagePath);
                        Log::info('Gambar jawaban dihapus: ' . $jawabanImagePath);
                    }
                }
            }
            
            // Hapus semua jawaban terkait (cascade)
            $pertanyaan->jawaban()->delete();
            
            // Hapus pertanyaan
            $pertanyaan->delete();
            
            Log::info('Pertanyaan berhasil dihapus', ['id' => $id]);
            
            return response()->json([
                'status' => true,
                'message' => 'Pertanyaan berhasil dihapus'
            ], 200);
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::error('Pertanyaan tidak ditemukan: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Pertanyaan tidak ditemukan'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error deleting pertanyaan: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            return response()->json([
                'status' => false,
                'message' => 'Gagal menghapus pertanyaan: ' . $e->getMessage()
            ], 500);
        }
    }
}