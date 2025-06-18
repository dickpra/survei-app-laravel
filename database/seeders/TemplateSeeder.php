<?php

namespace Database\Seeders;

use App\Models\QuestionnaireTemplate;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TemplateSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Template ITAIE (Lengkap)
        QuestionnaireTemplate::create([
            'title' => 'ITAIE Assessment Method',
            'description' => 'Metode Penilaian Sikap Guru terhadap Siswa Berkebutuhan Khusus dalam Pendidikan Inklusi.',
            'content_blocks' => [
                [
                    'type' => 'section_block',
                    'data' => [
                        'section_title' => 'Bagian A: Data Demografi',
                        'questions' => [
                            ['content' => 'Jenis Kelamin', 'type' => 'pilihan ganda', 'options' => ['Laki-laki', 'Perempuan']],
                            ['content' => 'Usia (Tahun)', 'type' => 'isian pendek'],
                            ['content' => 'Provinsi', 'type' => 'isian pendek'],
                            ['content' => 'Jenis Sekolah', 'type' => 'pilihan ganda', 'options' => ['Sekolah Inklusi', 'Sekolah Luar Biasa', 'Sekolah Reguler']],
                            ['content' => 'Jenjang Sekolah', 'type' => 'pilihan ganda', 'options' => ['Sekolah Dasar/sederajat', 'Sekolah Menengah Pertama/sederajat', 'Sekolah Menengah Atas/sederajat']],
                            ['content' => 'Latar Belakang Pendidikan', 'type' => 'pilihan ganda', 'options' => ['Sarjana', 'Magister', 'Doktor']],
                            ['content' => 'Mata Pelajaran yang Diampu', 'type' => 'isian pendek'],
                            ['content' => 'Pengalaman Mengajar (Tahun)', 'type' => 'isian pendek'],
                            ['content' => 'Pengalaman Mengajar di Sekolah Inklusi (Tahun)', 'type' => 'isian pendek'],
                            ['content' => 'Pelatihan dalam Bidang Pendidikan Inklusi', 'type' => 'pilihan ganda', 'options' => ['Pernah', 'Belum Pernah']],
                            ['content' => 'Interaksi dengan Siswa Berkebutuhan Khusus', 'type' => 'pilihan ganda', 'options' => ['Pernah', 'Belum Pernah']],
                        ],
                    ],
                ],
                [
                    'type' => 'section_block',
                    'data' => [
                        'section_title' => 'Bagian B: Pernyataan Sikap',
                        'questions' => [
                            ['content' => 'Penataan ruang kelas reguler dapat menciptakan lingkungan kelas yang ramah bagi semua siswa, termasuk siswa dengan berkebutuhan khusus.', 'type' => 'skala likert'],
                            ['content' => 'Jarang terjadi siswa berkebutuhan khusus tidak dapat menyelesaikan pendidikan dari kelas reguler untuk memenuhi kebutuhan pendidikannya.', 'type' => 'skala likert'],
                            // ... tambahkan 20 pertanyaan skala ITAIE lainnya di sini ...
                        ],
                    ],
                ],
            ],
        ]);

        // 2. Template Kustom Contoh
        QuestionnaireTemplate::create([
            'title' => 'Survei Kepuasan Pelanggan',
            'description' => 'Formulir untuk mengukur tingkat kepuasan pelanggan terhadap layanan kami.',
            'content_blocks' => [
                [
                    'type' => 'section_block',
                    'data' => [
                        'section_title' => 'Penilaian Produk',
                        'questions' => [
                            ['content' => 'Seberapa sering Anda menggunakan produk kami?', 'type' => 'pilihan ganda', 'options' => ['Setiap Hari', 'Beberapa Kali Seminggu', 'Sebulan Sekali', 'Jarang Sekali']],
                            ['content' => 'Fitur apa yang paling Anda sukai?', 'type' => 'isian pendek'],
                            ['content' => 'Beri nilai dari 1-5 untuk kemudahan penggunaan produk.', 'type' => 'skala likert'],
                        ],
                    ],
                ],
                [
                    'type' => 'section_block',
                    'data' => [
                        'section_title' => 'Layanan Pelanggan',
                        'questions' => [
                            ['content' => 'Bagaimana Anda menilai kecepatan respons tim layanan pelanggan kami?', 'type' => 'dropdown', 'options' => ['Sangat Cepat', 'Cepat', 'Normal', 'Lambat', 'Sangat Lambat']],
                            ['content' => 'Apakah masalah Anda terselesaikan dengan baik?', 'type' => 'pilihan ganda', 'options' => ['Ya, sepenuhnya', 'Sebagian besar', 'Tidak sama sekali']],
                        ],
                    ],
                ],
            ]
        ]);
    }
}