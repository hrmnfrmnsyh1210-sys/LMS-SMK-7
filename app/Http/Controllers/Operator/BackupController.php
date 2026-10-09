<?php

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Services\ActivityLogger;
use App\Services\BackupService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class BackupController extends Controller
{
    public function __construct(private BackupService $backup) {}

    public function index(): View
    {
        return view('operator.backup.index');
    }

    public function download(): StreamedResponse
    {
        @set_time_limit(300);
        ActivityLogger::log('backup', 'Mengunduh backup database');

        $nama = 'backup_lms_'.now()->format('Ymd_His').'.sql';

        return response()->streamDownload(function () {
            $stream = fopen('php://output', 'w');
            $this->backup->dump($stream);
            fclose($stream);
        }, $nama, ['Content-Type' => 'application/sql']);
    }

    /** Restore dengan konfirmasi ganda: ketik "RESTORE" + password akun. */
    public function restore(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'max:51200'],
            'konfirmasi_teks' => ['required', 'in:RESTORE'],
            'password' => ['required', 'current_password'],
        ], [
            'konfirmasi_teks.in' => 'Ketik RESTORE (huruf kapital) untuk konfirmasi.',
        ], ['file' => 'file backup', 'konfirmasi_teks' => 'teks konfirmasi']);

        $file = $request->file('file');
        if (strtolower($file->getClientOriginalExtension()) !== 'sql' || ! $this->backup->tampakValid($file->getRealPath())) {
            return back()->withErrors(['file' => 'File harus berupa backup .sql yang valid.']);
        }

        $username = $request->user()->username;
        @set_time_limit(600);

        try {
            $jumlah = $this->backup->restore($file->getRealPath());
        } catch (Throwable $e) {
            report($e);

            return back()->with('error', 'Restore gagal: '.$e->getMessage());
        }

        // Data user bisa berubah setelah restore; paksa login ulang.
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        ActivityLogger::log('restore', "Restore database oleh {$username} ({$jumlah} perintah SQL)");

        return redirect()->route('login')->with('status', 'Restore database berhasil. Silakan login kembali.');
    }
}
