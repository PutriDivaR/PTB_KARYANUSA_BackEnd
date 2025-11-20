<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Kursus;
use App\Models\Materi;
use getID3;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;


class AdminKursusController extends Controller
{
    // ini API json untuk Android – tampilkan semua kursus
    public function apiIndex()
    {
        $kursus = Kursus::all();
        $kursus->transform(function ($item) {
            if ($item->thumbnail) {
                $item->thumbnail = asset('storage/thumbnails/' . $item->thumbnail);
            }
            return $item;
        });
        return response()->json($kursus);
    }


    // ini API json untuk Android – tampilkan detail kursus by ID
    public function apiShow($id)
    {
        $kursus = Kursus::find($id);
        if (!$kursus) {
            return response()->json(['message' => 'Kursus tidak ditemukan'], 404);
        }
        return response()->json($kursus);
    }
    public function apiMateri($kursus_id)
    {
        $materi = Materi::where('kursus_id', $kursus_id)->get();

        // Ubah field video dan tambahkan URL lengkap
        $materi->transform(function ($item) {
            if ($item->video) {
                $item->video = asset('storage/videos/' . $item->video);
            }
            return $item;
        });

        return response()->json($materi);
    }


    // view semua kursus
    public function index()
    {
        $kursus = Kursus::all();
        return view('kursus.index', compact('kursus'));
    }

    // Form tambah kursus
    public function create()
    {
        return view('kursus.create');
    }

    // Simpan kursus baru
    public function store(Request $request)
    {
        $request->validate([
            'judul' => 'required|string|max:150',
            'deskripsi' => 'required',
            'pengrajin_nama' => 'required|string|max:100',
            'thumbnail' => 'nullable|image|mimes:jpg,jpeg,png|max:2048'
        ]);

        $data = $request->only(['judul', 'deskripsi', 'pengrajin_nama']);

        if ($request->hasFile('thumbnail')) {
            $file = $request->file('thumbnail');

            $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $extension = $file->getClientOriginalExtension();
            $filename = time() . '_' . Str::slug($originalName) . '.' . $extension;

            // Simpan file ke storage/app/public/thumbnails dan 
            $file->move(storage_path('app/public/thumbnails'), $filename);

            
            $data['thumbnail'] = $filename;
        }

         $kursus = Kursus::create($data);

    // Simpan Video Materi
    if ($request->has('materi')) {
        foreach ($request->materi as $index => $materiData) {
            $videoName = null;
            $durasi = null;

            if ($request->hasFile("materi.$index.video")) {
                $video = $request->file("materi.$index.video");
                $videoName = time().'_'.Str::slug(pathinfo($video->getClientOriginalName(), PATHINFO_FILENAME)).'.'.$video->getClientOriginalExtension();
                
                // Simpan video di folder videos
                $video->move(storage_path('app/public/videos'), $videoName);

                // Hitung durasi video pakai getID3 (yg belum bisa ndeh)
                $analyzer = new getID3();
                $info = $analyzer->analyze(storage_path('app/public/videos/'.$videoName));
                $durasi = isset($info['playtime_seconds']) ? round($info['playtime_seconds'] / 60) : 0;
            }

            Materi::create([
                'kursus_id' => $kursus->kursus_id,
                'judul' => $materiData['judul'],
                'video' => $videoName,
                'durasi' => $durasi
            ]);
        }
    }



        return redirect('/admin/kursus')->with('success', 'Kursus berhasil ditambahkan!');
    }

    // Form edit kursus
    public function edit($id)
    {
        $kursus = Kursus::findOrFail($id);
        return view('kursus.edit', compact('kursus'));
    }

    // Update kursus
    public function update(Request $request, $id)
    {
        $kursus = Kursus::findOrFail($id);

        $request->validate([
            'judul' => 'required|string|max:150',
            'deskripsi' => 'required',
            'pengrajin_nama' => 'required|string|max:100',
            'thumbnail' => 'nullable|image|mimes:jpg,jpeg,png|max:2048'
        ]);

        $data = $request->only(['judul', 'deskripsi', 'pengrajin_nama']);

    if ($request->hasFile('thumbnail')) {
        // Hapus file lama 
        if ($kursus->thumbnail && Storage::exists('public/thumbnails/' . $kursus->thumbnail)) {
            Storage::delete('public/thumbnails/' . $kursus->thumbnail);
        }

        $file = $request->file('thumbnail');

        $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $extension = $file->getClientOriginalExtension();
        $filename = time() . '_' . Str::slug($originalName) . '.' . $extension;

        // Pindahkan n simpan lagi ke folder thumbnails
        $file->move(storage_path('app/public/thumbnails'), $filename);

        $data['thumbnail'] = $filename;
    } else {
        $data['thumbnail'] = $kursus->thumbnail;
    }

        $kursus->update($data);
    
    // Update Materi Lama
    if ($request->has('materi_lama')) {
        foreach ($request->materi_lama as $materiId => $materiData) {
            $materi = Materi::find($materiId);
            if (!$materi) continue;

            // hapus video lama
            if ($request->hasFile("materi_lama.$materiId.video")) {
                $video = $request->file("materi_lama.$materiId.video");
                $videoName = time().'_'.Str::slug(pathinfo($video->getClientOriginalName(), PATHINFO_FILENAME)).'.'.$video->getClientOriginalExtension();
                $video->move(storage_path('app/public/videos'), $videoName);

                if ($materi->video && Storage::exists('public/videos/'.$materi->video)) {
                    Storage::delete('public/videos/'.$materi->video);
                }

                // Hitung ulang durasi
                $analyzer = new getID3();
                $info = $analyzer->analyze(storage_path('app/public/videos/'.$videoName));
                $durasi = isset($info['playtime_seconds']) ? round($info['playtime_seconds'] / 60) : 0;

                $materi->update([
                    'judul' => $materiData['judul'],
                    'video' => $videoName,
                    'durasi' => $durasi
                ]);
            } else {
                $materi->update(['judul' => $materiData['judul']]);
            }
        }
    }

    // Hapus Materi 
    if ($request->has('hapus_materi')) {
        foreach ($request->hapus_materi as $materiId) {
            $materi = Materi::find($materiId);
            if ($materi) {
                if ($materi->video && Storage::exists('public/videos/'.$materi->video)) {
                    Storage::delete('public/videos/'.$materi->video);
                }
                $materi->delete();
            }
        }
    }

    // Tambah Materi Baru
    if ($request->has('materi_baru')) {
        foreach ($request->materi_baru as $materiData) {
            $videoName = null;
            $durasi = 0;

            if (isset($materiData['video'])) {
                $video = $materiData['video'];
                $videoName = time().'_'.Str::slug(pathinfo($video->getClientOriginalName(), PATHINFO_FILENAME)).'.'.$video->getClientOriginalExtension();
                $video->move(storage_path('app/public/videos'), $videoName);

                $analyzer = new getID3();
                $info = $analyzer->analyze(storage_path('app/public/videos/'.$videoName));
                $durasi = isset($info['playtime_seconds']) ? round($info['playtime_seconds'] / 60) : 0;
            }

            Materi::create([
                'kursus_id' => $kursus->kursus_id,
                'judul' => $materiData['judul'],
                'video' => $videoName,
                'durasi' => $durasi
            ]);
        }
    }


        return redirect('/admin/kursus')->with('success', 'Kursus berhasil diperbarui!');
    }

    // Hapus kursus
    public function destroy($id)
    {
        $kursus = Kursus::findOrFail($id);

        if ($kursus->thumbnail && Storage::exists('public/thumbnails/' . $kursus->thumbnail)) {
            Storage::delete('public/thumbnails/' . $kursus->thumbnail);
        }

        $kursus->delete();

        return redirect('/admin/kursus')->with('success', 'Kursus berhasil dihapus!');
    }
}
