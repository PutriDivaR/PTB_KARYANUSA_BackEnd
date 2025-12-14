<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use App\Models\Notifikasi;
use App\Models\User;
use App\Http\Controllers\NotifikasiController;

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

        $user = $request->user();
        $path = $request->file('gambar')->store('karya', 'public');

        DB::table('galeri')->insert([
            'user_id'        => $user->user_id,
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
        $user = $request->user();

        $data = DB::table('galeri')
            ->where('user_id', $user->user_id)
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
        $user = $request->user();

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

        $user = $request->user();

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

    // ✅ Increment View dengan Notifikasi FCM (FIXED)
    public function incrementView($id)
    {
        $karya = DB::table('galeri')->where('galeri_id', $id)->first();

        if (!$karya) {
            return response()->json([
                'status' => false,
                'message' => 'Karya tidak ditemukan'
            ], 404);
        }

        $oldViews = $karya->views ?? 0;
        $updatedViews = $oldViews + 1;

        // 🔥 Increment view
        DB::table('galeri')
            ->where('galeri_id', $id)
            ->update(['views' => $updatedViews]);

        Log::info("View incremented for karya {$id}: {$oldViews} -> {$updatedViews}");

        // 🔥 KIRIM NOTIFIKASI PADA MILESTONE TERTENTU
        $milestones = [10, 25, 50, 100, 500, 1000];
        
        if (in_array($updatedViews, $milestones)) {
            Log::info("Milestone reached: {$updatedViews} views for karya {$id}");
            
            $uploader = User::find($karya->user_id);

            if ($uploader) {
                try {
                    // Simpan notifikasi ke database
                    $notif = Notifikasi::create([
                        'from_user' => 0, // System notification
                        'to_user' => $uploader->user_id,
                        'type' => 'view_milestone',
                        'title' => '🎉 Karya Anda Populer!',
                        'message' => "Karya '{$karya->judul}' telah dilihat {$updatedViews} kali!",
                        'related_id' => $karya->galeri_id,
                        'is_read' => false
                    ]);

                    Log::info("Notifikasi created: ", $notif->toArray());

                    // Kirim FCM jika token tersedia
                    if (!empty($uploader->fcm_token)) {
                        Log::info("Sending FCM to token: {$uploader->fcm_token}");
                        
                        $notifController = app(NotifikasiController::class);
                        $result = $notifController->sendFCMV1(
                            $uploader->fcm_token,
                            '🎉 Karya Anda Populer!',
                            "Karya '{$karya->judul}' telah dilihat {$updatedViews} kali!"
                        );

                        if ($result) {
                            Log::info("FCM sent successfully for karya {$id}");
                        } else {
                            Log::error("FCM failed for karya {$id}");
                        }
                    } else {
                        Log::warning("User {$uploader->user_id} has no FCM token");
                    }
                } catch (\Exception $e) {
                    Log::error("Error sending notification: " . $e->getMessage());
                }
            } else {
                Log::warning("Uploader not found for karya {$id}");
            }
        }

        return response()->json([
            'status' => true,
            'message' => 'View berhasil ditambahkan',
            'views' => $updatedViews
        ]);
    }
}