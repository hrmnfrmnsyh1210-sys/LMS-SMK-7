<?php

namespace Tests\Feature\Guru;

use App\Models\ForumTopik;
use App\Models\Kelas;
use App\Models\Pengumuman;
use App\Notifications\Pemberitahuan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ForumPengumumanTest extends TestCase
{
    use RefreshDatabase, SiapkanKelasAjar;

    public function test_guru_membuat_topik_membalas_dan_menghapus(): void
    {
        [$pengampu] = $this->kelasAjar();

        $this->post(route('guru.forum.store', $pengampu), ['judul' => 'Diskusi Bab 1', 'isi' => 'Silakan bertanya.'])->assertSessionHasNoErrors();
        $topik = ForumTopik::firstOrFail();
        $this->assertSame($pengampu->id, $topik->pengampu_id);

        $this->post(route('guru.forum.balas', [$pengampu, $topik]), ['isi' => ''])->assertSessionHasErrors('isi');
        $this->post(route('guru.forum.balas', [$pengampu, $topik]), ['isi' => 'Balasan guru'])->assertSessionHasNoErrors();
        $this->get(route('guru.forum.show', [$pengampu, $topik]))->assertOk()->assertSee('Balasan guru');

        $this->delete(route('guru.forum.destroy', [$pengampu, $topik]))->assertRedirect(route('guru.forum.index', $pengampu));
        $this->assertDatabaseCount('forum_balasan', 0);
    }

    public function test_pengumuman_guru_hanya_untuk_kelas_yang_diajar_dan_memberi_tahu_siswa(): void
    {
        Notification::fake();
        [$pengampu, $siswa] = $this->kelasAjar(2);
        $kelasLain = Kelas::factory()->create();

        $this->post(route('guru.pengumuman.store'), ['judul' => 'X', 'isi' => 'Y', 'kelas_id' => $kelasLain->id])
            ->assertSessionHasErrors('kelas_id');

        $this->post(route('guru.pengumuman.store'), ['judul' => 'Ulangan besok', 'isi' => 'Bab 1–2', 'kelas_id' => $pengampu->kelas_id])
            ->assertSessionHas('success');

        $p = Pengumuman::firstOrFail();
        $this->assertSame('kelas', $p->target);
        Notification::assertSentTo($siswa[0]->user, Pemberitahuan::class);
    }

    public function test_guru_tidak_dapat_menghapus_pengumuman_orang_lain(): void
    {
        $this->kelasAjar();
        $pengumuman = Pengumuman::create(['pembuat_user_id' => $this->operator()->id, 'judul' => 'Libur', 'isi' => '-', 'target' => 'guru']);

        $this->delete(route('guru.pengumuman.destroy', $pengumuman))->assertForbidden();
        $this->assertModelExists($pengumuman);
    }
}
