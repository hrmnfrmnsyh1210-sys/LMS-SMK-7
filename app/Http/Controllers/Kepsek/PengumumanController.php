<?php

namespace App\Http\Controllers\Kepsek;

use App\Http\Controllers\Controller;
use App\Http\Requests\Kepsek\PengumumanRequest;
use App\Models\Pengumuman;
use App\Models\User;
use App\Notifications\Pemberitahuan;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\View\View;

/** Pengumuman sekolah oleh kepala sekolah. */
class PengumumanController extends Controller
{
    public function index(): View
    {
        $pengumuman = Pengumuman::with(['pembuat.guru', 'pembuat.kepsek', 'pembuat.operator', 'kelas'])
            ->latest()
            ->paginate(config('lms.per_page'));

        return view('kepsek.pengumuman', compact('pengumuman'));
    }

    public function store(PengumumanRequest $request): RedirectResponse
    {
        $pengumuman = Pengumuman::create($request->validated() + ['pembuat_user_id' => $request->user()->id]);

        $roles = $pengumuman->target === 'semua' ? ['guru', 'siswa', 'ortu'] : [$pengumuman->target];
        $penerima = User::whereIn('role', $roles)->where('status', 'aktif')->get();
        Notification::send($penerima, new Pemberitahuan("Pengumuman sekolah: {$pengumuman->judul}"));

        ActivityLogger::log('tambah_pengumuman', "Pengumuman sekolah \"{$pengumuman->judul}\" untuk {$pengumuman->target}");

        return back()->with('success', 'Pengumuman diterbitkan.');
    }

    public function destroy(Request $request, Pengumuman $pengumuman): RedirectResponse
    {
        abort_unless($pengumuman->pembuat_user_id === $request->user()->id, 403, 'Anda hanya dapat menghapus pengumuman buatan sendiri.');

        $pengumuman->delete();

        return back()->with('success', 'Pengumuman dihapus.');
    }
}
