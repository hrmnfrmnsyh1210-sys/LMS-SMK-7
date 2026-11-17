<?php

namespace App\Imports;

use App\Models\Kuis;
use App\Models\Soal;
use DOMDocument;
use DOMXPath;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use RuntimeException;
use ZipArchive;

/** Import bank soal dari dokumen Microsoft Word (.docx). */
class SoalImport
{
    public int $berhasil = 0;

    /** @var list<array{baris: int, alasan: list<string>}> */
    public array $gagal = [];

    public function __construct(private Kuis $kuis) {}

    /**
     * Menghasilkan file dokumen template Word (.docx) untuk diunduh guru.
     */
    public static function buatTemplateDocx(): string
    {
        $zip = new ZipArchive;
        $tmpPath = tempnam(sys_get_temp_dir(), 'tmpl_soal_').'.docx';
        if ($zip->open($tmpPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Gagal membuat file template docx.');
        }

        $contentTypes = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
  <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
  <Default Extension="xml" ContentType="application/xml"/>
  <Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>
</Types>';

        $rels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/>
</Relationships>';

        $headers = ['No', 'Tipe (pg/esai)', 'Pertanyaan', 'Opsi A', 'Opsi B', 'Opsi C', 'Opsi D', 'Kunci (A/B/C/D)', 'Bobot'];
        $sampleRows = [
            ['1', 'pg', 'Ibu kota negara Indonesia saat ini adalah ...', 'Bandung', 'Jakarta', 'Surabaya', 'Medan', 'B', '1'],
            ['2', 'pg', 'Perangkat keras komputer yang berfungsi memproses data utama adalah ...', 'Harddisk', 'RAM', 'Processor (CPU)', 'Power Supply', 'C', '1'],
            ['3', 'esai', 'Jelaskan perbedaan mendasar antara jaringan LAN, MAN, dan WAN!', '', '', '', '', '', '5'],
        ];

        $tblXml = '<w:tbl>';
        $tblXml .= '<w:tblPr>
            <w:tblW w:w="5000" w:type="pct"/>
            <w:tblBorders>
                <w:top w:val="single" w:sz="6" w:space="0" w:color="CBD5E1"/>
                <w:left w:val="single" w:sz="6" w:space="0" w:color="CBD5E1"/>
                <w:bottom w:val="single" w:sz="6" w:space="0" w:color="CBD5E1"/>
                <w:right w:val="single" w:sz="6" w:space="0" w:color="CBD5E1"/>
                <w:insideH w:val="single" w:sz="4" w:space="0" w:color="E2E8F0"/>
                <w:insideV w:val="single" w:sz="4" w:space="0" w:color="E2E8F0"/>
            </w:tblBorders>
        </w:tblPr>';

        // Baris Header
        $tblXml .= '<w:tr><w:trPr><w:tblHeader/></w:trPr>';
        foreach ($headers as $h) {
            $tblXml .= '<w:tc>
                <w:tcPr>
                    <w:shd w:val="clear" w:color="auto" w:fill="2563EB"/>
                    <w:tcMar><w:top w:w="120" w:type="dxa"/><w:bottom w:w="120" w:type="dxa"/><w:left w:w="120" w:type="dxa"/><w:right w:w="120" w:type="dxa"/></w:tcMar>
                </w:tcPr>
                <w:p>
                    <w:r><w:rPr><w:b/><w:color w:val="FFFFFF"/><w:sz w:val="20"/></w:rPr><w:t>'.htmlspecialchars($h, ENT_XML1).'</w:t></w:r>
                </w:p>
            </w:tc>';
        }
        $tblXml .= '</w:tr>';

        // Baris Data Contoh
        foreach ($sampleRows as $row) {
            $tblXml .= '<w:tr>';
            foreach ($row as $cell) {
                $tblXml .= '<w:tc>
                    <w:tcPr>
                        <w:tcMar><w:top w:w="100" w:type="dxa"/><w:bottom w:w="100" w:type="dxa"/><w:left w:w="100" w:type="dxa"/><w:right w:w="100" w:type="dxa"/></w:tcMar>
                    </w:tcPr>
                    <w:p>
                        <w:r><w:rPr><w:sz w:val="20"/><w:color w:val="1E293B"/></w:rPr><w:t>'.htmlspecialchars($cell, ENT_XML1).'</w:t></w:r>
                    </w:p>
                </w:tc>';
            }
            $tblXml .= '</w:tr>';
        }
        $tblXml .= '</w:tbl>';

        $docXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">
  <w:body>
    <w:p>
      <w:pPr><w:jc w:val="center"/></w:pPr>
      <w:r><w:rPr><w:b/><w:sz w:val="32"/><w:color w:val="1E3A8A"/></w:rPr><w:t>TEMPLATE IMPORT SOAL - LMS SMK</w:t></w:r>
    </w:p>
    <w:p>
      <w:pPr><w:spacing w:after="200"/></w:pPr>
      <w:r><w:rPr><w:i/><w:color w:val="64748B"/><w:sz w:val="18"/></w:rPr><w:t>Petunjuk: Isi tabel di bawah ini. Tambah baris baru sesuai jumlah soal yang diinginkan. Untuk soal pilihan ganda (pg), isi Opsi A–D dan Kunci (A/B/C/D). Untuk esai, kosongkan Opsi dan Kunci.</w:t></w:r>
    </w:p>
    '.$tblXml.'
  </w:body>
</w:document>';

        $zip->addFromString('[Content_Types].xml', $contentTypes);
        $zip->addFromString('_rels/.rels', $rels);
        $zip->addFromString('word/document.xml', $docXml);
        $zip->close();

        return $tmpPath;
    }

    /**
     * Memproses file docx dan menyimpannya ke database.
     */
    public function import(string|UploadedFile $file): void
    {
        $path = $file instanceof UploadedFile ? $file->getRealPath() : $file;

        $items = $this->ekstrakSoalDariDocx($path);

        foreach ($items as $index => $data) {
            $data['tipe'] = strtolower((string) ($data['tipe'] ?? 'pg')) ?: 'pg';
            $data['kunci_jawaban'] = $data['kunci_jawaban'] !== null && $data['kunci_jawaban'] !== '' ? strtolower((string) $data['kunci_jawaban']) : null;
            $data['bobot'] = is_numeric($data['bobot'] ?? null) ? (int) $data['bobot'] : 1;
            $pg = $data['tipe'] === 'pg';

            $validator = Validator::make($data, [
                'tipe' => ['required', Rule::in(['pg', 'esai'])],
                'pertanyaan' => ['required', 'string'],
                'opsi_a' => [Rule::requiredIf($pg)],
                'opsi_b' => [Rule::requiredIf($pg)],
                'opsi_c' => [Rule::requiredIf($pg)],
                'opsi_d' => [Rule::requiredIf($pg)],
                'kunci_jawaban' => [Rule::requiredIf($pg), 'nullable', Rule::in(Soal::OPSI)],
                'bobot' => ['required', 'integer', 'min:1', 'max:100'],
            ], [], [
                'kunci_jawaban' => 'kunci jawaban (a/b/c/d)',
                'tipe' => 'tipe (pg/esai)',
            ]);

            if ($validator->fails()) {
                $nomorBaris = $data['baris'] ?? ($index + 1);
                $this->gagal[] = ['baris' => $nomorBaris, 'alasan' => $validator->errors()->all()];

                continue;
            }

            if (! $pg) {
                $data['opsi_a'] = null;
                $data['opsi_b'] = null;
                $data['opsi_c'] = null;
                $data['opsi_d'] = null;
                $data['kunci_jawaban'] = null;
            }

            $this->kuis->soal()->create([
                'tipe' => $data['tipe'],
                'pertanyaan' => $data['pertanyaan'],
                'opsi_a' => $data['opsi_a'],
                'opsi_b' => $data['opsi_b'],
                'opsi_c' => $data['opsi_c'],
                'opsi_d' => $data['opsi_d'],
                'kunci_jawaban' => $data['kunci_jawaban'],
                'bobot' => $data['bobot'],
            ]);

            $this->berhasil++;
        }
    }

    /**
     * Mengekstrak data soal dari dokumen Word (.docx).
     *
     * @return list<array{baris?: int, tipe: string, pertanyaan: string, opsi_a: ?string, opsi_b: ?string, opsi_c: ?string, opsi_d: ?string, kunci_jawaban: ?string, bobot: int|string}>
     */
    public function ekstrakSoalDariDocx(string $filePath): array
    {
        $zip = new ZipArchive;
        if ($zip->open($filePath) !== true) {
            throw new RuntimeException('File tidak dapat dibuka sebagai format dokumen Word (.docx).');
        }

        $xmlContent = $zip->getFromName('word/document.xml');
        $zip->close();

        if (! $xmlContent) {
            throw new RuntimeException('Dokumen Word tidak berisi konten teks yang valid.');
        }

        $dom = new DOMDocument;
        @$dom->loadXML($xmlContent, LIBXML_NOERROR | LIBXML_NOWARNING);
        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');

        // 1. Ekstraksi dari tabel Word jika tersedia
        $tables = $xpath->query('//w:tbl');
        if ($tables && $tables->length > 0) {
            $soalDariTabel = $this->ekstrakDariTabel($xpath, $tables);
            if (! empty($soalDariTabel)) {
                return $soalDariTabel;
            }
        }

        // 2. Ekstraksi dari paragraf teks berurutan
        return $this->ekstrakDariParagraf($xpath);
    }

    private function ekstrakDariTabel(DOMXPath $xpath, \DOMNodeList $tables): array
    {
        $hasil = [];

        foreach ($tables as $tbl) {
            $trNodes = $xpath->query('.//w:tr', $tbl);
            if ($trNodes->length < 2) {
                continue;
            }

            // Baca header (baris pertama)
            $headerCells = $xpath->query('.//w:tc', $trNodes->item(0));
            $headers = [];
            foreach ($headerCells as $tc) {
                $textNodes = $xpath->query('.//w:t', $tc);
                $cellText = '';
                foreach ($textNodes as $t) {
                    $cellText .= $t->nodeValue;
                }
                $headers[] = strtolower(trim($cellText));
            }

            $kolom = $this->petakanKolomHeader($headers);

            for ($i = 1; $i < $trNodes->length; $i++) {
                $tr = $trNodes->item($i);
                $tcNodes = $xpath->query('.//w:tc', $tr);
                $rowValues = [];
                foreach ($tcNodes as $tc) {
                    $textNodes = $xpath->query('.//w:t', $tc);
                    $cellText = '';
                    foreach ($textNodes as $t) {
                        $cellText .= $t->nodeValue;
                    }
                    $rowValues[] = trim($cellText);
                }

                if (empty(array_filter($rowValues, fn ($v) => $v !== ''))) {
                    continue;
                }

                $item = [
                    'baris' => $i + 1,
                    'tipe' => $rowValues[$kolom['tipe']] ?? 'pg',
                    'pertanyaan' => $rowValues[$kolom['pertanyaan']] ?? null,
                    'opsi_a' => $rowValues[$kolom['opsi_a']] ?? null,
                    'opsi_b' => $rowValues[$kolom['opsi_b']] ?? null,
                    'opsi_c' => $rowValues[$kolom['opsi_c']] ?? null,
                    'opsi_d' => $rowValues[$kolom['opsi_d']] ?? null,
                    'kunci_jawaban' => $rowValues[$kolom['kunci_jawaban']] ?? null,
                    'bobot' => $rowValues[$kolom['bobot']] ?? 1,
                ];

                if (! empty($item['pertanyaan'])) {
                    $hasil[] = $item;
                }
            }
        }

        return $hasil;
    }

    private function petakanKolomHeader(array $headers): array
    {
        $mapping = [
            'tipe' => null,
            'pertanyaan' => null,
            'opsi_a' => null,
            'opsi_b' => null,
            'opsi_c' => null,
            'opsi_d' => null,
            'kunci_jawaban' => null,
            'bobot' => null,
        ];

        foreach ($headers as $idx => $header) {
            $h = strtolower($header);
            if (str_contains($h, 'tipe') || str_contains($h, 'jenis')) {
                $mapping['tipe'] = $idx;
            } elseif (str_contains($h, 'pertanyaan') || str_contains($h, 'soal')) {
                $mapping['pertanyaan'] = $idx;
            } elseif (str_contains($h, 'opsi a') || str_contains($h, 'pilihan a') || $h === 'opsi_a' || $h === 'a') {
                $mapping['opsi_a'] = $idx;
            } elseif (str_contains($h, 'opsi b') || str_contains($h, 'pilihan b') || $h === 'opsi_b' || $h === 'b') {
                $mapping['opsi_b'] = $idx;
            } elseif (str_contains($h, 'opsi c') || str_contains($h, 'pilihan c') || $h === 'opsi_c' || $h === 'c') {
                $mapping['opsi_c'] = $idx;
            } elseif (str_contains($h, 'opsi d') || str_contains($h, 'pilihan d') || $h === 'opsi_d' || $h === 'd') {
                $mapping['opsi_d'] = $idx;
            } elseif (str_contains($h, 'kunci') || str_contains($h, 'jawaban')) {
                $mapping['kunci_jawaban'] = $idx;
            } elseif (str_contains($h, 'bobot') || str_contains($h, 'skor') || str_contains($h, 'poin')) {
                $mapping['bobot'] = $idx;
            }
        }

        if ($mapping['pertanyaan'] === null) {
            if (count($headers) >= 9) {
                return [
                    'tipe' => 1,
                    'pertanyaan' => 2,
                    'opsi_a' => 3,
                    'opsi_b' => 4,
                    'opsi_c' => 5,
                    'opsi_d' => 6,
                    'kunci_jawaban' => 7,
                    'bobot' => 8,
                ];
            }

            return [
                'tipe' => 0,
                'pertanyaan' => 1,
                'opsi_a' => 2,
                'opsi_b' => 3,
                'opsi_c' => 4,
                'opsi_d' => 5,
                'kunci_jawaban' => 6,
                'bobot' => 7,
            ];
        }

        return $mapping;
    }

    private function ekstrakDariParagraf(DOMXPath $xpath): array
    {
        $pNodes = $xpath->query('//w:body/w:p');
        $lines = [];
        foreach ($pNodes as $p) {
            $textNodes = $xpath->query('.//w:t', $p);
            $t = '';
            foreach ($textNodes as $node) {
                $t .= $node->nodeValue;
            }
            $trimmed = trim($t);
            if ($trimmed !== '') {
                $lines[] = $trimmed;
            }
        }

        $items = [];
        $curr = null;
        $barisHitung = 0;

        foreach ($lines as $line) {
            $barisHitung++;

            if (preg_match('/^(?:soal\s*)?(\d+)[\.\)]\s*(.*)$/ui', $line, $m)) {
                if ($curr) {
                    $items[] = $curr;
                }
                $pertanyaan = trim($m[2]);
                $tipe = 'pg';
                if (preg_match('/\[(?:esai|essay)\]/ui', $pertanyaan)) {
                    $tipe = 'esai';
                    $pertanyaan = trim(preg_replace('/\[(?:esai|essay)\]/ui', '', $pertanyaan));
                }

                $curr = [
                    'baris' => $barisHitung,
                    'tipe' => $tipe,
                    'pertanyaan' => $pertanyaan,
                    'opsi_a' => null,
                    'opsi_b' => null,
                    'opsi_c' => null,
                    'opsi_d' => null,
                    'kunci_jawaban' => null,
                    'bobot' => 1,
                ];

                continue;
            }

            if (! $curr) {
                continue;
            }

            if (preg_match('/^[aA][\.\)]\s*(.*)$/u', $line, $m)) {
                $curr['opsi_a'] = trim($m[1]);
            } elseif (preg_match('/^[bB][\.\)]\s*(.*)$/u', $line, $m)) {
                $curr['opsi_b'] = trim($m[1]);
            } elseif (preg_match('/^[cC][\.\)]\s*(.*)$/u', $line, $m)) {
                $curr['opsi_c'] = trim($m[1]);
            } elseif (preg_match('/^[dD][\.\)]\s*(.*)$/u', $line, $m)) {
                $curr['opsi_d'] = trim($m[1]);
            } elseif (preg_match('/^(?:kunci|jawaban|kunci jawaban|ans|key)\s*[:=]\s*([a-dA-D])/iu', $line, $m)) {
                $curr['kunci_jawaban'] = strtolower($m[1]);
            } elseif (preg_match('/^(?:bobot|skor|poin)\s*[:=]\s*(\d+)/iu', $line, $m)) {
                $curr['bobot'] = (int) $m[1];
            } elseif (preg_match('/^(?:tipe|jenis)\s*[:=]\s*(pg|esai)/iu', $line, $m)) {
                $curr['tipe'] = strtolower($m[1]);
            } else {
                $curr['pertanyaan'] .= "\n".$line;
            }
        }

        if ($curr) {
            $items[] = $curr;
        }

        return $items;
    }
}
