<!DOCTYPE html>
<html>
<head>
    <title>Tambah Kursus & Materi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script>
        
        function tambahMateri() {
            const container = document.getElementById('materi-container');
            const index = container.children.length;

            const html = `
                <div class="card p-3 mb-3">
                    <h6>Materi #${index + 1}</h6>
                    <div class="mb-2">
                        <label>Judul Materi</label>
                        <input type="text" name="materi[${index}][judul]" class="form-control" required>
                    </div>
                    <div class="mb-2">
                        <label>Video (mp4)</label>
                        <input type="file" name="materi[${index}][video]" class="form-control" accept="video/mp4">
                    </div>
                </div>
            `;
            container.insertAdjacentHTML('beforeend', html);
        }
    </script>
</head>
<body class="bg-light p-4">
    <div class="container">
        <h2>Tambah Kursus & Materi</h2>

        <form method="POST" action="/admin/kursus" enctype="multipart/form-data">
            @csrf
            <div class="mb-3">
                <label>Judul Kursus</label>
                <input type="text" name="judul" class="form-control" required>
            </div>

            <div class="mb-3">
                <label>Deskripsi</label>
                <textarea name="deskripsi" class="form-control" rows="3" required></textarea>
            </div>

            <div class="mb-3">
                <label>Nama Pengrajin</label>
                <input type="text" name="pengrajin_nama" class="form-control" required>
            </div>

            <div class="mb-3">
                <label>Thumbnail Kursus</label>
                <input type="file" name="thumbnail" class="form-control" accept="image/*">
            </div>

            <hr>
            <h4>Materi Kursus</h4>
            <div id="materi-container"></div>

            <button type="button" class="btn btn-sm btn-outline-primary mb-3" onclick="tambahMateri()">+ Tambah Materi</button>

            <div class="d-flex justify-content-between">
                <a href="/admin/kursus" class="btn btn-secondary">Kembali</a>
                <button type="submit" class="btn btn-success">Simpan Kursus</button>
            </div>
        </form>
    </div>
</body>
</html>
