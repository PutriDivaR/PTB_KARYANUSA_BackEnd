<?php
// app/Http/Controllers/LikeController.php

namespace App\Http\Controllers;

use App\Models\Like;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LikeController extends Controller
{
    private $notifController;

    public function __construct()
    {
        
        $this->notifController = new NotifikasiController();
    }


    public function toggleLike(Request $request, $galeriId)
    {
        try {
            $user = $request->user();

            
            $karya = DB::table('galeri')->where('galeri_id', $galeriId)->first();
            if (!$karya) {
                return response()->json([
                    'status' => false,
                    'message' => 'Karya tidak ditemukan'
                ], 404);
            }

           
            $existingLike = Like::where('user_id', $user->user_id)
                ->where('galeri_id', $galeriId)
                ->first();

            if ($existingLike) {
               
                $existingLike->delete();
                DB::table('galeri')->where('galeri_id', $galeriId)->decrement('likes');
                $newLikesCount = DB::table('galeri')->where('galeri_id', $galeriId)->value('likes');

                return response()->json([
                    'status' => true,
                    'action' => 'unliked',
                    'message' => 'Like dihapus',
                    'likes' => $newLikesCount,
                    'is_liked' => false
                ]);
            } else {
               
                Like::create([
                    'user_id' => $user->user_id,
                    'galeri_id' => $galeriId
                ]);
                DB::table('galeri')->where('galeri_id', $galeriId)->increment('likes');
                $newLikesCount = DB::table('galeri')->where('galeri_id', $galeriId)->value('likes');

               
                if ($user->user_id != $karya->user_id) {
                    $notifRequest = new Request([
                        'from_user' => intval($user->user_id),
                        'to_user' => intval($karya->user_id),
                        'type' => 'like',
                        'title' => '❤️ Karya Anda Disukai!',
                        'message' => "{$user->nama} menyukai karya \"{$karya->judul}\"",
                        'related_id' => intval($galeriId)
                    ]);

                    $this->notifController->sendNotification($notifRequest);
                }

                return response()->json([
                    'status' => true,
                    'action' => 'liked',
                    'message' => 'Like berhasil',
                    'likes' => $newLikesCount,
                    'is_liked' => true
                ]);
            }

        } catch (\Exception $e) {
            \Log::error("TOGGLE_LIKE_ERROR", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    public function checkLike(Request $request, $galeriId)
    {
        try {
            $user = $request->user();
            $isLiked = Like::where('user_id', $user->user_id)
                ->where('galeri_id', $galeriId)
                ->exists();

            $likesCount = DB::table('galeri')
                ->where('galeri_id', $galeriId)
                ->value('likes') ?? 0;

            return response()->json([
                'status' => true,
                'is_liked' => $isLiked,
                'likes' => $likesCount
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Terjadi kesalahan'
            ], 500);
        }
    }
}
