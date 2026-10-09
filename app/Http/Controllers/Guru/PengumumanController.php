<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Http\Requests\Guru\PengumumanRequest;
use App\Models\Pengumuman;
use App\Models\User;
use App\Notifications\Pemberitahuan;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\View\View;

/** Pengumuman kelas oleh guru. */
class PengumumanController extends Controller
{
    public function index(Request $request): View
    {
        $guru = $request->user()->guru;
        $daftarKelas = PengumumanRequest::kelasGuru($guru);

        $pengumuman = Pengumuman::with(['pembuat.guru', 'pembuat.kepsek', 'pembuat.operator', 'kelas'])
            ->where(fn ($q) => $q->whereIn('target', ['semua', 'guru'])
                ->orWhere(fn ($q) => $q->where('target', 'kelas')->whereIn('kelas_id', $daftarKelas->pluck('id'))))
            ->latest()
            ->paginate(config('lms.per_page'));

        return view('guru.pengumuman.index', compact('pengumuman', 'daftarKelas'));
    }

    public function store(PengumumanRequest $request): RedirectResponse
    {
        $pengumuman = Pengumuman::create($request->validated() + [
            'pembuat_user_id' => $request->user()->id,
            'target' => 'kelas',
        ]);
        $pengumuman->load('kelas');

        $siswa = User::whereHas('siswa.kelasSiswa', fn ($q) => $q->where('kelas_id', $pengumuman->kelas_id))->get();
        Notification::send($siswa, new Pemberitahuan("Pengumuman {$pengumuman->kelas->nama_kelas}: {$pengumuman->judul}"));

        ActivityLogger::log('tambah_pengumuman', "Pengumuman \"{$pengumuman->judul}\" untuk {$pengumuman->kelas->nama_kelas}");

        return back()->with('success', 'Pengumuman diterbitkan.');
    }

    public function destroy(Request $request, Pengumuman $pengumuman): RedirectResponse
    {
        abort_unless($pengumuman->pembuat_user_id === $request->user()->id, 403, 'Anda hanya dapat menghapus pengumuman buatan sendiri.');

        $pengumuman->delete();

        return back()->with('success', 'Pengumuman dihapus.');
    }
}
