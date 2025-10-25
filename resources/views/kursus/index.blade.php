<!DOCTYPE html>
<html>
<head>
    <title>Daftar Kursus</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light p-4">
    <div class="container">
        <h2 class="mb-4">Daftar Kursus</h2>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <a href="/admin/kursus/create" class="btn btn-primary mb-3">+ Tambah Kursus</a>

    <table class="table table-bordered bg-white align-middle">
        <thead class="table-light">
            <tr>
                <th>ID</th>
                <th>Thumbnail</th>
                <th>Judul</th>
                <th>Pengrajin</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            @foreach($kursus as $k)
            <tr>
                <td>{{ $k->kursus_id }}</td>

                <td style="width: 120px;">
                    @if($k->thumbnail)
                        <img src="{{ asset('storage/thumbnails/' . $k->thumbnail) }}"
                            alt="thumbnail"
                            class="img-thumbnail"
                            style="width: 100px; height: 70px; object-fit: cover;">
                    @else
                        <span class="text-muted">Tidak ada</span>
                    @endif
                </td>

                <td>{{ $k->judul }}</td>
                <td>{{ $k->pengrajin_nama }}</td>
                <td>
                    <a href="/admin/kursus/{{ $k->kursus_id }}/edit" class="btn btn-warning btn-sm">Edit</a>
                    <form action="/admin/kursus/{{ $k->kursus_id }}" method="POST" style="display:inline;">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-danger btn-sm" onclick="return confirm('Hapus kursus ini?')">Hapus</button>
                    </form>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    </div>
</body>
</html>
