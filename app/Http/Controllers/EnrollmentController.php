<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Enrollment;
use App\Models\Materi;
use App\Models\ProgressMateri;
use Illuminate\Support\Facades\Auth;

class EnrollmentController extends Controller
{
    // Menampilkan semua kursus yang user ikuti
    public function index()
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['message' => 'User tidak terautentikasi.'], 401);
        }

        $enrollments = Enrollment::with('kursus')
            ->where('user_id', $user->user_id) 
            ->get();

        return response()->json($enrollments, 200);
    }

    // Enroll ke kursus tertentu
    public function store(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['message' => 'User tidak terautentikasi.'], 401);
        }

        // Validasi input
        $request->validate([
            'kursus_id' => 'required|integer|exists:kursus,kursus_id',
        ]);

        // Cegah double enroll
        $exists = Enrollment::where('user_id', $user->user_id)
            ->where('kursus_id', $request->kursus_id)
            ->exists();

        if ($exists) {
            return response()->json(['message' => 'Sudah terdaftar di kursus ini.'], 400);
        }

        // Buat data enrollment baru
        $enroll = Enrollment::create([
            'user_id'   => $user->user_id,  
            'kursus_id' => $request->kursus_id,
            'progress'  => 0,
            'status'    => 'ongoing',
        ]);

        return response()->json([
            'message' => 'Berhasil mendaftar kursus.',
            'data'    => $enroll
        ], 201);
    }

// Update progress & status kursus
public function updateStatus(Request $request)
{
    $request->validate([
        'kursus_id' => 'required|integer|exists:kursus,kursus_id',
        'progress'  => 'required|integer|min:0|max:100',
    ]);

    $user = Auth::user();
    if (!$user) {
        return response()->json(['message' => 'User tidak terautentikasi.'], 401);
    }

    $enroll = Enrollment::where('user_id', $user->user_id)
        ->where('kursus_id', $request->kursus_id)
        ->first();

    if (!$enroll) {
        return response()->json(['message' => 'Enrollment tidak ditemukan.'], 404);
    }

    $status = $request->progress >= 100 ? 'completed' : 'ongoing';

    $enroll->progress = $request->progress;
    $enroll->status = $status;
    $enroll->save();

    return response()->json([
        'message' => 'Progress diperbarui.',
        'data' => $enroll
    ], 200);
}



    public function checkEnrollment($kursus_id)
    {
        $user = Auth::user();

        $enrollment = Enrollment::where('user_id', $user->user_id)
            ->where('kursus_id', $kursus_id)
            ->first();

        if (!$enrollment) {
            return response()->json(['enrolled' => false]);
        }

        return response()->json([
            'enrolled' => true,
            'status' => $enrollment->status,
            'progress' => $enrollment->progress
        ]);
    }


    // FUNGSI STATUS PROGRESS VIDEO MATERI
    public function tandaiMateriSelesai(Request $request)
    {
        $request->validate([
            'enrollment_id' => 'required|exists:enrollment,enrollment_id',
            'materi_id' => 'required|exists:materi,materi_id',
        ]);

        $progress = ProgressMateri::updateOrCreate(
            [
                'enrollment_id' => $request->enrollment_id,
                'materi_id' => $request->materi_id
            ],
            ['is_completed' => true]
        );

        $this->updateProgress($request->enrollment_id);

        return response()->json([
            'message' => 'Materi marked as completed',
            'data' => $progress
        ]);
    }

    public function cekMateriSelesai($enrollmentId, $materiId)
    {
        $completed = ProgressMateri::where('enrollment_id', $enrollmentId)
            ->where('materi_id', $materiId)
            ->where('is_completed', true)
            ->exists();

        return response()->json(['completed' => $completed]);
    }

    private function updateProgress($enrollmentId)
    {
        $enrollment = Enrollment::find($enrollmentId);
        $kursusId = $enrollment->kursus_id;

        $totalMateri = Materi::where('kursus_id', $kursusId)->count();

        $completed = ProgressMateri::where('enrollment_id', $enrollmentId)
            ->where('is_completed', true)
            ->count();

        $progress = round(($completed / $totalMateri) * 100);

        $enrollment->progress = $progress;

        if ($progress == 100) {
            $enrollment->status = 'completed';
        }

        $enrollment->save();
    }

    public function destroy($kursus_id)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $enrollment = Enrollment::where('user_id', $user->user_id)
            ->where('kursus_id', $kursus_id)
            ->first();

        if (!$enrollment) {
            return response()->json(['message' => 'Enrollment tidak ditemukan'], 404);
        }

        // Hapus progress materi dulu
        ProgressMateri::where('enrollment_id', $enrollment->enrollment_id)->delete();

        // Hapus enrollment
        $enrollment->delete();

        return response()->json([
            'message' => 'Enrollment berhasil dibatalkan'
        ], 200);
    }


}
