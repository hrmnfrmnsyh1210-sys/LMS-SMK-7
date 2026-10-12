<?php

namespace App\Services;

use App\Exceptions\AiGagalException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Klien LLM berformat OpenAI chat completions (dipakai untuk SumoPod).
 * Murni HTTP sehingga aman untuk shared hosting.
 */
class AiService
{
    public function aktif(): bool
    {
        return filled(config('services.ai.key'));
    }

    /**
     * Kirim percakapan dan kembalikan teks balasan.
     *
     * @param  list<array{role: string, content: string}>  $pesan
     *
     * @throws AiGagalException
     */
    public function chat(array $pesan, float $suhu = 0.4, int $maksToken = 1500): string
    {
        if (! $this->aktif()) {
            throw new AiGagalException('Fitur AI belum diaktifkan. Hubungi operator untuk mengisi API key AI.');
        }

        try {
            $respons = Http::baseUrl(rtrim(config('services.ai.base_url'), '/'))
                ->withToken(config('services.ai.key'))
                ->acceptJson()
                ->timeout(config('services.ai.timeout'))
                ->post('chat/completions', [
                    'model' => config('services.ai.model'),
                    'messages' => $pesan,
                    'temperature' => $suhu,
                    'max_tokens' => $maksToken,
                ]);
        } catch (ConnectionException $e) {
            Log::warning('AI tidak dapat dihubungi', ['error' => $e->getMessage()]);

            throw new AiGagalException('Layanan AI tidak dapat dihubungi. Coba lagi beberapa saat lagi.');
        }

        if ($respons->failed()) {
            Log::warning('AI mengembalikan error', ['status' => $respons->status(), 'body' => mb_substr($respons->body(), 0, 500)]);

            throw new AiGagalException(match ($respons->status()) {
                401, 403 => 'API key AI tidak valid. Hubungi operator.',
                402, 429 => 'Kuota atau batas permintaan AI sedang habis. Coba lagi nanti.',
                default => 'Layanan AI sedang bermasalah. Coba lagi beberapa saat lagi.',
            });
        }

        $teks = trim((string) $respons->json('choices.0.message.content'));
        if ($teks === '') {
            throw new AiGagalException('AI tidak memberikan jawaban. Coba lagi.');
        }

        return $teks;
    }

    /**
     * Minta balasan berupa JSON lalu decode. Blok ```json ... ``` dibersihkan.
     *
     * @return array<mixed>
     *
     * @throws AiGagalException
     */
    public function json(string $sistem, string $pengguna, int $maksToken = 3000): array
    {
        $teks = $this->chat([
            ['role' => 'system', 'content' => $sistem."\nBalas HANYA dengan JSON valid tanpa teks lain."],
            ['role' => 'user', 'content' => $pengguna],
        ], 0.3, $maksToken);

        $teks = preg_replace('/^```(?:json)?\s*|\s*```$/i', '', trim($teks));
        $data = json_decode($teks, true);

        if (! is_array($data)) {
            // Beberapa model menambahkan kalimat pembuka: ambil objek JSON pertama.
            if (preg_match('/\{.*\}/s', $teks, $cocok)) {
                $data = json_decode($cocok[0], true);
            }
        }

        if (! is_array($data)) {
            throw new AiGagalException('Format jawaban AI tidak dapat dibaca. Coba lagi.');
        }

        return $data;
    }
}
