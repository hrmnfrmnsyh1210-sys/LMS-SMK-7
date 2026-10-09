<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Backup & restore database MySQL murni lewat PHP/PDO,
 * karena shared hosting umumnya tidak menyediakan mysqldump.
 */
class BackupService
{
    /** Tabel yang hanya disalin strukturnya (datanya sementara). */
    private const TANPA_DATA = ['sessions', 'cache', 'cache_locks', 'jobs', 'job_batches', 'failed_jobs'];

    private const BARIS_PER_INSERT = 200;

    /** Tulis dump SQL ke stream (mis. php://output). */
    public function dump($stream): void
    {
        $pdo = DB::connection()->getPdo();
        $database = DB::connection()->getDatabaseName();

        $this->tulis($stream, "-- Backup database `{$database}`\n");
        $this->tulis($stream, '-- Dibuat: '.now()->format('Y-m-d H:i:s')." (LMS SMK)\n\n");
        $this->tulis($stream, "SET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\n\n");

        foreach ($this->daftarTabel() as $tabel) {
            $create = DB::selectOne("SHOW CREATE TABLE `{$tabel}`");
            $sqlCreate = $create->{'Create Table'};

            $this->tulis($stream, "-- Tabel `{$tabel}`\n");
            $this->tulis($stream, "DROP TABLE IF EXISTS `{$tabel}`;\n{$sqlCreate};\n\n");

            if (in_array($tabel, self::TANPA_DATA, true)) {
                continue;
            }

            $kolom = null;
            $buffer = [];
            foreach (DB::table($tabel)->cursor() as $baris) {
                $baris = (array) $baris;
                $kolom ??= '`'.implode('`, `', array_keys($baris)).'`';
                $buffer[] = '('.implode(', ', array_map(fn ($v) => $this->nilai($pdo, $v), $baris)).')';

                if (count($buffer) >= self::BARIS_PER_INSERT) {
                    $this->tulis($stream, "INSERT INTO `{$tabel}` ({$kolom}) VALUES ".implode(', ', $buffer).";\n");
                    $buffer = [];
                }
            }
            if ($buffer) {
                $this->tulis($stream, "INSERT INTO `{$tabel}` ({$kolom}) VALUES ".implode(', ', $buffer).";\n");
            }
            $this->tulis($stream, "\n");
        }

        $this->tulis($stream, "SET FOREIGN_KEY_CHECKS=1;\n");
    }

    /**
     * Jalankan file .sql hasil dump(). Statement dipisah per baris yang
     * diakhiri ';' (INSERT hasil dump selalu satu baris).
     *
     * @return int jumlah statement yang dijalankan
     */
    public function restore(string $path): int
    {
        $handle = fopen($path, 'r');
        if (! $handle) {
            throw new RuntimeException('File backup tidak dapat dibaca.');
        }

        $jumlah = 0;
        $statement = '';

        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        try {
            while (($baris = fgets($handle)) !== false) {
                $trim = trim($baris);
                if ($statement === '' && ($trim === '' || str_starts_with($trim, '--') || str_starts_with($trim, '/*'))) {
                    continue;
                }

                $statement .= $baris;
                if (str_ends_with($trim, ';')) {
                    DB::unprepared($statement);
                    $statement = '';
                    $jumlah++;
                }
            }

            if (trim($statement) !== '') {
                DB::unprepared($statement);
                $jumlah++;
            }
        } finally {
            fclose($handle);
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }

        return $jumlah;
    }

    /** Validasi sederhana bahwa file adalah dump SQL yang wajar. */
    public function tampakValid(string $path): bool
    {
        $awal = (string) file_get_contents($path, false, null, 0, 4096);

        return str_contains($awal, 'CREATE TABLE') || str_contains($awal, 'DROP TABLE') || str_contains($awal, 'INSERT INTO');
    }

    /** @return list<string> */
    private function daftarTabel(): array
    {
        return array_map(
            fn ($baris) => array_values((array) $baris)[0],
            DB::select("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'")
        );
    }

    private function nilai(\PDO $pdo, mixed $nilai): string
    {
        return match (true) {
            $nilai === null => 'NULL',
            is_bool($nilai) => $nilai ? '1' : '0',
            is_int($nilai), is_float($nilai) => (string) $nilai,
            default => $pdo->quote((string) $nilai),
        };
    }

    private function tulis($stream, string $teks): void
    {
        fwrite($stream, $teks);
    }
}
