<!DOCTYPE html>
<html>
<head>
    <title>Edit Kursus & Materi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script>
        function tambahMateri() {
            const container = document.getElementById('materi-container');
            const index = container.children.length;

            const html = `
                <div class="card p-3 mb-3 new-materi">
                    <h6>Materi Baru #${index + 1}</h6>
                    <div class="mb-2">
                        <label>Judul Materi</label>
                        <input type="text" name="materi_baru[${index}][judul]" class="form-control" required>
                    </div>
                    <div class="mb-2">
                        <label>Video (mp4)</label>
                        <input type="file" name="materi_baru[${index}][video]" class="form-control" accept="video/mp4">
                    </div>
                </div>
            `;
            container.insertAdjacentHTML('beforeend', html);
        }
    </script>
</head>
<body class="bg-light p-4">
    <div class="container">
        <h2>Edit Kursus & Materi</h2>

        <form method="POST" action="/admin/kursus/{{ $kursus->kursus_id }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="mb-3">
                <label>Judul Kursus</label>
                <input type="text" name="judul" class="form-control" value="{{ $kursus->judul }}" required>
            </div>

            <div class="mb-3">
                <label>Deskripsi</label>
                <textarea name="deskripsi" class="form-control" rows="3" required>{{ $kursus->deskripsi }}</textarea>
            </div>

            <div class="mb-3">
                <label>Nama Pengrajin</label>
                <input type="text" name="pengrajin_nama" class="form-control" value="{{ $kursus->pengrajin_nama }}" required>
            </div>

            <div class="mb-3">
                <label>Thumbnail Kursus</label><br>
                @if($kursus->thumbnail)
                    <img src="{{ asset('storage/thumbnails/' . $kursus->thumbnail) }}"
                         class="img-thumbnail mb-2"
                         style="width:150px; height:100px; object-fit:cover;">
                @endif
                <input type="file" name="thumbnail" class="form-control" accept="image/*">
                <small class="text-muted">Biarkan kosong jika tidak ingin mengganti thumbnail.</small>
            </div>

            <hr>
            <h4>Materi Kursus</h4>


            <div>
                @foreach($kursus->materi as $index => $materi)
                <div class="card p-3 mb-3">
                    <h6>Materi Lama #{{ $index + 1 }}</h6>
                    <div class="mb-2">
                        <label>Judul Materi</label>
                        <input type="text" name="materi_lama[{{ $materi->materi_id }}][judul]" value="{{ $materi->judul }}" class="form-control">
                    </div>

                    <div class="mb-2">
                        <label>Video Saat Ini:</label><br>
                        @if($materi->video)
                            <video width="200" height="120" controls>
                                <source src="{{ asset('storage/videos/' . $materi->video) }}" type="video/mp4">
                                Browser tidak mendukung video.
                            </video>
                        @else
                            <p class="text-muted">Belum ada video</p>
                        @endif
                    </div>

                    <div class="mb-2">
                        <label>Ganti Video (opsional)</label>
                        <input type="file" name="materi_lama[{{ $materi->materi_id }}][video]" class="form-control" accept="video/mp4">
                    </div>

                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" name="hapus_materi[]" value="{{ $materi->materi_id }}" id="hapus{{ $materi->materi_id }}">
                        <label class="form-check-label text-danger" for="hapus{{ $materi->materi_id }}">
                            Hapus materi ini
                        </label>
                    </div>
                </div>
                @endforeach
            </div>


            <div id="materi-container"></div>
            <button type="button" class="btn btn-sm btn-outline-primary mb-3" onclick="tambahMateri()">+ Tambah Materi Baru</button>

            <div class="d-flex justify-content-between mt-4">
                <a href="/admin/kursus" class="btn btn-secondary">Kembali</a>
                <button type="submit" class="btn btn-success">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</body>
</html>
