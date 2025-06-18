<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\QuestionnaireTemplate;

class SoalSurveySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Buat satu template kuesioner utama
        $template = QuestionnaireTemplate::create([
            'title' => 'ITAIE Assessment Method',
            'description' => 'Metode Penilaian Sikap Guru terhadap Siswa Berkebutuhan Khusus dalam Pendidikan Inklusi.'
        ]);

        // Definisikan 11 pertanyaan demografi
        $demographicQuestions = [
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
        ];

        // Definisikan 22 pertanyaan skala ITAIE
        $itaieScaleQuestions = [
            // Component: Creating an accepting environment for all students
            ['content' => 'Penataan ruang kelas reguler dapat menciptakan lingkungan kelas yang ramah bagi semua siswa, termasuk siswa dengan berkebutuhan khusus.', 'type' => 'skala likert'],
            ['content' => 'Jarang terjadi siswa berkebutuhan khusus tidak dapat menyelesaikan pendidikan dari kelas reguler untuk memenuhi kebutuhan pendidikannya.', 'type' => 'skala likert'],
            ['content' => 'Melibatkan siswa dengan kebutuhan khusus di kelas reguler adalah efektif karena mereka dapat mempelajari keterampilan sosial yang diperlukan untuk sukses.', 'type' => 'skala likert'],
            // Component: Problem of students with SEN in the inclusive classroom
            ['content' => 'Sulit menjaga kedisiplinan di kelas reguler yang terdapat siswa berkebutuhan khusus didalamnya.', 'type' => 'skala likert'],
            ['content' => 'Siswa dengan kebutuhan khusus cenderung membuat kebingungan di kelas reguler.', 'type' => 'skala likert'],
            ['content' => 'Perilaku siswa berkebutuhan khusus memberikan contoh yang buruk bagi siswa lainnya.', 'type' => 'skala likert'],
            ['content' => 'Sebagian besar siswa dengan kebutuhan khusus tidak melakukan upaya yang memadai untuk menyelesaikan tugasnya.', 'type' => 'skala likert'],
            // Component: Professional responsibilities in the inclusive education
            ['content' => 'Saya merasa frustasi ketika saya kesulitan berkomunikasi dengan siswa berkebutuhan khusus.', 'type' => 'skala likert'],
            ['content' => 'Saya merasa kurang nyaman ketika siswa dengan kebutuhan khusus tidak dapat mengikuti pelajaran di kelas saya.', 'type' => 'skala likert'],
            ['content' => 'Saya merasa kurang nyaman ketika saya tidak dapat memahami siswa dengan kebutuhan khusus.', 'type' => 'skala likert'],
            ['content' => 'Saya merasa frustrasi ketika harus menyesuaikan pelajaran untuk memenuhi kebutuhan individu semua siswa.', 'type' => 'skala likert'],
            // Component: Professional knowledge about inclusive education
            ['content' => 'Saya harus belajar lebih banyak tentang pengaruh kelas inklusif sebelum kelas inklusif diadakan dalam skala besar.', 'type' => 'skala likert'],
            ['content' => 'Siswa dengan kebutuhan khusus mungkin akan mengembangkan keterampilan akademik lebih cepat di kelas khusus yang terpisah daripada di kelas inklusif.', 'type' => 'skala likert'],
            ['content' => 'Pada pelaksanaan Pendidikan Inklusif untuk Semua Siswa memerlukan pelatihan ulang ekstensif bagi guru kelas reguler.', 'type' => 'skala likert'],
            // Component: The implication of inclusive education & Inclusive education perspective in Indonesia
            ['content' => 'Siswa dengan kebutuhan khusus memonopoli waktu guru di kelas yang inklusif.', 'type' => 'skala likert'],
            ['content' => 'Beban kerja saya akan bertambah jika saya memiliki siswa dengan kebutuhan khusus di kelas saya.', 'type' => 'skala likert'],
            ['content' => 'Saya akan lebih stres jika ada siswa dengan kebutuhan khusus di kelas saya.', 'type' => 'skala likert'],
            ['content' => 'Saya tidak akan menerima insentif yang cukup (misalnya, remunerasi atau tunjangan tambahan) untuk mengintegrasikan siswa dengan kebutuhan khusus di kelas reguler.', 'type' => 'skala likert'],
            ['content' => 'Guru khusus yang tersedia untuk mendukung Pendidikan Inklusif tidak memadai.', 'type' => 'skala likert'],
            ['content' => 'Sekolah saya tidak akan memiliki bahan ajar dan alat bantu pengajaran pendidikan khusus yang memadai, misalnya Braille.', 'type' => 'skala likert'],
            ['content' => 'Siswa berkebutuhan khusus tidak akan diterima di sekolah reguler karena tidak lolos seleksi siswa baru.', 'type' => 'skala likert'],
            ['content' => 'Negara belum memiliki kurikulum pendidikan inklusif, sehingga belum dapat diterapkan dengan baik.', 'type' => 'skala likert'],
        ];

        // Gabungkan kedua jenis pertanyaan menjadi satu array
        $allQuestions = array_merge($demographicQuestions, $itaieScaleQuestions);

        // Masukkan semua pertanyaan ke database dan hubungkan dengan template
        foreach ($allQuestions as $question) {
            $template->questions()->create($question);
        }
    }
}