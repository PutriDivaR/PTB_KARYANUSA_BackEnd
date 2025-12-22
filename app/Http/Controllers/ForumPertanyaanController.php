<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ForumPertanyaan;
use App\Models\ForumJawaban;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

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

    public function store(Request $request)
    {
        try {
            Log::info('=== STORE PERTANYAAN REQUEST ===');
            
            $validated = $request->validate([
                'isi' => 'required|string|max:2000',
                'image_forum' => 'nullable|array|max:4',
                'image_forum.*' => 'nullable|image|mimes:jpeg,jpg,png|max:10240'
            ]);

            $userId = auth()->id();
            if (!$userId) {
                return response()->json([
                    'status' => false,
                    'message' => 'User tidak terautentikasi'
                ], 401);
            }

            $uploadPath = public_path('uploads/forum');
            
            if (!File::exists($uploadPath)) {
                File::makeDirectory($uploadPath, 0775, true, true);
                Log::info('✓ Folder created: ' . $uploadPath);
            }
            
            if (!is_writable($uploadPath)) {
                Log::error('Folder tidak writable: ' . $uploadPath);
                return response()->json([
                    'status' => false,
                    'message' => 'Folder upload tidak memiliki permission yang benar'
                ], 500);
            }

            $imagePaths = [];
            
            if ($request->hasFile('image_forum')) {
                $files = $request->file('image_forum');
                
                if (!is_array($files)) {
                    $files = [$files];
                }

                foreach ($files as $index => $file) {
                    if (!$file->isValid()) {
                        Log::error("File $index tidak valid", [
                            'error' => $file->getError(),
                            'error_message' => $file->getErrorMessage()
                        ]);
                        continue;
                    }
                    
                    $fileSizeMB = $file->getSize() / 1024 / 1024;
                    if ($fileSizeMB > 10) {
                        Log::error("File $index terlalu besar: {$fileSizeMB}MB");
                        continue;
                    }
                    
                    $allowedMimes = ['image/jpeg', 'image/jpg', 'image/png'];
                    if (!in_array($file->getMimeType(), $allowedMimes)) {
                        Log::error("File $index MIME type tidak valid: " . $file->getMimeType());
                        continue;
                    }

                    $filename = time() . '_' . $index . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                    
                    try {
                        $moved = $file->move($uploadPath, $filename);
                        
                        if ($moved) {
                            $imagePaths[] = url('uploads/forum/' . $filename);
                            Log::info("✓ File $index uploaded successfully: $filename");
                        } else {
                            Log::error("✗ Failed to move file $index");
                        }
                        
                    } catch (\Exception $e) {
                        Log::error("Exception uploading file $index: " . $e->getMessage());
                        Log::error("Stack trace: " . $e->getTraceAsString());
                    }
                }
            }
            
            if ($request->hasFile('image_forum') && empty($imagePaths)) {
                return response()->json([
                    'status' => false,
                    'message' => 'Semua gambar gagal diupload. Cek ukuran dan format file.'
                ], 422);
            }

            $pertanyaan = ForumPertanyaan::create([
                'isi' => $request->isi,
                'image_forum' => !empty($imagePaths) ? $imagePaths : null,
                'user_id' => $userId,
                'tanggal' => now(),
            ]);

            $pertanyaan->load(['user', 'jawaban.user']);

            Log::info('✓ Pertanyaan berhasil dibuat', [
                'id' => $pertanyaan->pertanyaan_id,
                'images_count' => count($imagePaths),
                'images' => $imagePaths
            ]);
            
            return response()->json($pertanyaan, 201);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation error:', ['errors' => $e->errors()]);
            return response()->json([
                'status' => false,
                'message' => 'Validasi gagal',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error creating pertanyaan: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
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
                'image_jawaban' => 'nullable|image|mimes:jpeg,jpg,png'
            ]);

            $pertanyaan = ForumPertanyaan::findOrFail($id);
            $userId = auth()->id();

            if (!$userId) {
                return response()->json([
                    'status' => false,
                    'message' => 'User tidak terautentikasi'
                ], 401);
            }

            $imagePath = null;
            if ($request->hasFile('image_jawaban')) {
                $file = $request->file('image_jawaban');
                $filename = time() . '_jawaban_' . uniqid() . '.' . $file->getClientOriginalExtension();
                
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

            // ✅ KIRIM NOTIFIKASI KE PEMILIK PERTANYAAN
            $notificationSent = false;
            if ($pertanyaan->user_id !== $userId) {
                try {
                    $notifController = new NotifikasiController();
                    $currentUser = auth()->user();
                    
                    $notificationSent = $notifController->sendSystemNotification(
                        $userId, // from_user: yang membalas
                        $pertanyaan->user_id, // to_user: pemilik pertanyaan
                        'reply_forum', // type
                        '💬 Balasan Baru di Forum', // title
                        $currentUser->nama . ' membalas pertanyaan Anda: "' . \Illuminate\Support\Str::limit($pertanyaan->isi, 50) . '"', // message
                        $pertanyaan->pertanyaan_id // related_id
                    );
                    
                    Log::info('✅ Notifikasi balasan forum terkirim', [
                        'from_user' => $userId,
                        'to_user' => $pertanyaan->user_id,
                        'pertanyaan_id' => $id,
                        'success' => $notificationSent
                    ]);
                } catch (\Exception $e) {
                    // Jangan gagalkan proses jawaban kalau notifikasi error
                    Log::error('⚠️ Gagal kirim notifikasi balasan: ' . $e->getMessage());
                }
            }

            // ✅ RETURN DENGAN INFO NOTIFIKASI
            return response()->json([
                'jawaban_id' => $jawaban->jawaban_id,
                'pertanyaan_id' => $jawaban->pertanyaan_id,
                'isi' => $jawaban->isi,
                'image_jawaban' => $jawaban->image_jawaban,
                'user_id' => $jawaban->user_id,
                'tanggal' => $jawaban->tanggal,
                'updated_at' => $jawaban->updated_at,
                'user' => $jawaban->user,
                'notification_sent' => $notificationSent
            ], 201);
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Pertanyaan tidak ditemukan'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error creating jawaban: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Gagal menambah jawaban: ' . $e->getMessage()
            ], 500);
        }
    }

    // 🔥 UPDATE PERTANYAAN - FIXED VERSION
    public function update(Request $request, $id)
    {
        try {
            Log::info('=== UPDATE PERTANYAAN ===', [
                'id' => $id,
                'has_files' => $request->hasFile('image_forum'),
                'files_count' => $request->hasFile('image_forum') ? count($request->file('image_forum')) : 0,
                'keep_images' => $request->input('keep_images', [])
            ]);

            $pertanyaan = ForumPertanyaan::findOrFail($id);
            
            if ($pertanyaan->user_id !== auth()->id()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Anda tidak memiliki akses untuk mengubah pertanyaan ini'
                ], 403);
            }
            
            // Validasi input
            $validated = $request->validate([
                'isi' => 'required|string|max:2000',
                'image_forum' => 'nullable|array|max:4',
                'image_forum.*' => 'nullable|image|mimes:jpeg,png,jpg|max:10240',
                'keep_images' => 'nullable|array', // ✅ Array URL gambar yang mau dipertahankan
                'keep_images.*' => 'nullable|string'
            ]);
            
            // Update isi pertanyaan
            $pertanyaan->isi = $validated['isi'];
            
            // ✅ LOGIKA BARU: Gabungkan gambar lama yang dipertahankan + gambar baru
            $finalImages = [];
            
            // 1️⃣ Ambil gambar lama yang mau dipertahankan
            $keepImages = $request->input('keep_images', []);
            if (!empty($keepImages) && is_array($keepImages)) {
                foreach ($keepImages as $oldImage) {
                    // Validasi bahwa gambar ini memang milik pertanyaan ini
                    if ($pertanyaan->image_forum && in_array($oldImage, $pertanyaan->image_forum)) {
                        $finalImages[] = $oldImage;
                        Log::info('✓ Gambar lama dipertahankan: ' . $oldImage);
                    }
                }
            }
            
            // 2️⃣ Upload gambar baru jika ada
            if ($request->hasFile('image_forum')) {
                Log::info('Gambar baru terdeteksi, memproses upload...');
                
                $uploadPath = public_path('uploads/forum');
                
                if (!File::exists($uploadPath)) {
                    File::makeDirectory($uploadPath, 0775, true, true);
                }
                
                $files = $request->file('image_forum');
                if (!is_array($files)) {
                    $files = [$files];
                }
                
                foreach ($files as $index => $file) {
                    // Cek total gambar tidak melebihi 4
                    if (count($finalImages) >= 4) {
                        Log::warning('Maksimal 4 gambar tercapai, file berikutnya diabaikan');
                        break;
                    }
                    
                    if (!$file->isValid()) {
                        Log::error("File $index tidak valid saat update");
                        continue;
                    }
                    
                    $filename = time() . '_update_' . $index . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                    
                    try {
                        $moved = $file->move($uploadPath, $filename);
                        
                        if ($moved) {
                            $finalImages[] = url('uploads/forum/' . $filename);
                            Log::info("✓ File baru $index uploaded: $filename");
                        }
                    } catch (\Exception $e) {
                        Log::error("Exception uploading file $index: " . $e->getMessage());
                    }
                }
            }
            
            // 3️⃣ Hapus gambar lama yang TIDAK ada di keep_images dan TIDAK ada di finalImages
            if ($pertanyaan->image_forum && is_array($pertanyaan->image_forum)) {
                foreach ($pertanyaan->image_forum as $oldImage) {
                    if (!in_array($oldImage, $finalImages)) {
                        $oldImagePath = str_replace(url('/'), public_path(), $oldImage);
                        if (File::exists($oldImagePath)) {
                            File::delete($oldImagePath);
                            Log::info('✓ Gambar lama dihapus: ' . $oldImagePath);
                        }
                    }
                }
            }
            
            // 4️⃣ Simpan array gambar final
            $pertanyaan->image_forum = !empty($finalImages) ? $finalImages : null;
            
            $pertanyaan->save();
            $pertanyaan->load(['user', 'jawaban.user']);
            
            Log::info('✓ Update pertanyaan berhasil', [
                'pertanyaan_id' => $pertanyaan->pertanyaan_id,
                'final_images_count' => count($finalImages),
                'images' => $finalImages
            ]);
            
            return response()->json($pertanyaan, 200);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation error saat update:', ['errors' => $e->errors()]);
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

    // 🔥 6. DELETE Pertanyaan (SUPPORT MULTIPLE IMAGES)
    public function destroy($id)
    {
        try {
            Log::info('Delete pertanyaan dimulai', ['id' => $id]);
            
            $pertanyaan = ForumPertanyaan::findOrFail($id);
            
            if ($pertanyaan->user_id !== auth()->id()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Anda tidak memiliki akses untuk menghapus pertanyaan ini'
                ], 403);
            }
            
            // ✅ Hapus multiple gambar pertanyaan jika ada
            if ($pertanyaan->image_forum && is_array($pertanyaan->image_forum)) {
                foreach ($pertanyaan->image_forum as $image) {
                    $imagePath = str_replace(url('/'), public_path(), $image);
                    if (File::exists($imagePath)) {
                        File::delete($imagePath);
                        Log::info('Gambar pertanyaan dihapus: ' . $imagePath);
                    }
                }
            }
            
            // Hapus gambar jawaban jika ada
            foreach ($pertanyaan->jawaban as $jawaban) {
                if ($jawaban->image_jawaban) {
                    $jawabanImagePath = str_replace(url('/'), public_path(), $jawaban->image_jawaban);
                    if (File::exists($jawabanImagePath)) {
                        File::delete($jawabanImagePath);
                        Log::info('Gambar jawaban dihapus: ' . $jawabanImagePath);
                    }
                }
            }
            
            $pertanyaan->jawaban()->delete();
            $pertanyaan->delete();
            
            Log::info('Pertanyaan berhasil dihapus', ['id' => $id]);
            
            return response()->json([
                'status' => true,
                'message' => 'Pertanyaan berhasil dihapus'
            ], 200);
            
        } catch (\Exception $e) {
            Log::error('Error deleting pertanyaan: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Gagal menghapus pertanyaan: ' . $e->getMessage()
            ], 500);
        }
    }
}