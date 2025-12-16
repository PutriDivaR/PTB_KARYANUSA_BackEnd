<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class KaryaController extends Controller
{
    private NotifikasiController $notif;

    public function __construct(NotifikasiController $notif)
    {
        $this->notif = $notif;
    }

    // ✅ Upload Karya
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

    // ✅ Get Semua Karya
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

    // ✅ Get Karya Pribadi
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

    // ✅ Delete Karya
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

    // ✅ Update Karya
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

    /**
     * ✅ Increment View dengan Notifikasi Milestone
     */
    public function incrementView($id)
    {
        \Log::info("🔥 INCREMENT VIEW START", [
            'galeri_id' => $id,
            'time' => now()->toDateTimeString()
        ]);

        try {
            $karya = DB::table('galeri')->where('galeri_id', $id)->first();

            if (!$karya) {
                \Log::error("KARYA_NOT_FOUND", ['galeri_id' => $id]);
                return response()->json([
                    'status' => false,
                    'message' => 'Karya tidak ditemukan'
                ], 404);
            }

            $oldViews = $karya->views ?? 0;

            // ✅ Increment views
            DB::table('galeri')
                ->where('galeri_id', $id)
                ->update([
                    'views' => DB::raw('views + 1'),
                    'updated_at' => now()
                ]);

            $newViews = $oldViews + 1;

            \Log::info("VIEW_INCREMENT", [
                'galeri_id' => $id,
                'old_views' => $oldViews,
                'new_views' => $newViews,
                'user_id' => $karya->user_id
            ]);

            // ✅ Milestone check
            $milestones = [5, 10, 25, 50, 100, 250, 500, 1000];
            
            $message = 'View berhasil ditambahkan';
            $isMilestone = false;

            if (in_array($newViews, $milestones)) {
                $message = "🎉 Milestone tercapai: {$newViews} views!";
                $isMilestone = true;
                
                \Log::info("🎉 MILESTONE TERCAPAI!", [
                    'galeri_id' => $id,
                    'views' => $newViews,
                    'judul' => $karya->judul,
                    'owner_user_id' => $karya->user_id
                ]);
                
                // 🔥 KIRIM NOTIFIKASI VIA NotifikasiController
                // ⚠️ GUNAKAN user_id KARYA SEBAGAI from_user (bukan 0/null)
                $result = $this->notif->sendSystemNotification(
                    fromUser: $karya->user_id, // ✅ PERBAIKAN: gunakan owner karya
                    toUser: $karya->user_id,
                    type: 'view_milestone',
                    title: '🎉 Karya Anda Populer!',
                    message: "Karya \"{$karya->judul}\" telah mencapai {$newViews} views!",
                    relatedId: $id
                );

                \Log::info("NOTIF_SENT_RESULT", ['success' => $result]);
            }

            \Log::info("🔥 INCREMENT VIEW END", [
                'galeri_id' => $id,
                'final_views' => $newViews,
                'time' => now()->toDateTimeString()
            ]);

            return response()->json([
                'status' => true,
                'message' => $message,
                'views' => $newViews,
                'milestone' => $isMilestone
            ]);

        } catch (\Exception $e) {
            \Log::error("ERROR_INCREMENT_VIEW", [
                'galeri_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Gagal menambah view: ' . $e->getMessage()
            ], 500);
        }
    }
}