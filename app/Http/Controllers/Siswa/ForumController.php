<?php

namespace App\Http\Controllers\Siswa;

use App\Http\Controllers\Controller;
use App\Http\Requests\Guru\ForumRequest;
use App\Models\ForumBalasan;
use App\Models\ForumTopik;
use App\Models\Pengampu;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Forum diskusi kelas-mapel. Siswa hanya dapat menghapus postingan miliknya sendiri. */
class ForumController extends Controller
{
    public function index(Request $request, Pengampu $pengampu): View
    {
        $cari = $request->string('q')->trim()->value();

        $topik = $pengampu->forumTopik()
            ->with(['user.guru', 'user.siswa'])
            ->withCount('balasan')
            ->when($cari, fn ($q) => $q->where('judul', 'like', "%{$cari}%"))
            ->latest()
            ->paginate(config('lms.per_page'))
            ->withQueryString();

        return view('siswa.forum.index', compact('pengampu', 'topik', 'cari'));
    }

    public function store(ForumRequest $request, Pengampu $pengampu): RedirectResponse
    {
        $topik = $pengampu->forumTopik()->create($request->validated() + ['user_id' => $request->user()->id]);

        return redirect()->route('siswa.forum.show', [$pengampu, $topik])->with('success', 'Topik diskusi dibuat.');
    }

    public function show(Pengampu $pengampu, ForumTopik $topik): View
    {
        $topik->load(['user.guru', 'user.siswa', 'balasan.user.guru', 'balasan.user.siswa']);

        return view('siswa.forum.show', compact('pengampu', 'topik'));
    }

    public function balas(ForumRequest $request, Pengampu $pengampu, ForumTopik $topik): RedirectResponse
    {
        $topik->balasan()->create($request->validated() + ['user_id' => $request->user()->id]);

        return back()->with('success', 'Balasan terkirim.');
    }

    public function destroy(Request $request, Pengampu $pengampu, ForumTopik $topik): RedirectResponse
    {
        abort_unless($topik->user_id === $request->user()->id, 403, 'Anda hanya dapat menghapus topik buatan sendiri.');

        $topik->delete();

        return redirect()->route('siswa.forum.index', $pengampu)->with('success', 'Topik dihapus.');
    }

    public function hapusBalasan(Request $request, Pengampu $pengampu, ForumTopik $topik, ForumBalasan $balasan): RedirectResponse
    {
        abort_unless($balasan->user_id === $request->user()->id, 403, 'Anda hanya dapat menghapus balasan sendiri.');

        $balasan->delete();

        return back()->with('success', 'Balasan dihapus.');
    }
}
