<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Survei: {{ $survey->title }}</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; background-color: #f3f4f6; color: #1f2937; line-height: 1.6; display: flex; justify-content: center; padding: 2rem 1rem; }
        .container { width: 100%; max-width: 800px; background: #fff; padding: 2.5rem; border-radius: 0.75rem; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1), 0 4px 6px -2px rgba(0,0,0,0.05); }
        h1, h2, h3 { color: #111827; font-weight: 700; }
        h1 { font-size: 1.875rem; text-align: center; margin-bottom: 1rem; }
        h2 { font-size: 1.5rem; border-bottom: 1px solid #e5e7eb; padding-bottom: 0.5rem; margin-top: 2.5rem; margin-bottom: 1.5rem; }
        hr { border: none; height: 1px; background-color: #e5e7eb; margin: 2rem 0; }
        .question-block { margin-bottom: 1.75rem; padding: 1.25rem; border-radius: 0.5rem; border: 1px solid #e5e7eb; transition: box-shadow 0.2s; }
        .question-block:focus-within { border-color: #4f46e5; box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.2); }
        .question-label { font-weight: 600; display: block; margin-bottom: 0.75rem; color: #374151; }
        input[type="text"], input[type="number"], input[type="date"], select, 
        textarea { width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 0.375rem; box-sizing: border-box; transition: border-color 0.2s, box-shadow 0.2s; }
        input:focus, select:focus, textarea:focus { border-color: #4f46e5; outline: none; box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.2); }
        .radio-group label { font-weight: normal; margin-left: 0.5rem; }
        .radio-item { display: flex; align-items: center; margin-bottom: 0.5rem; }
        .likert-group { display: flex; justify-content: space-around; padding: 0.5rem 0; flex-wrap: wrap; }
        .likert-item { text-align: center; margin: 5px 10px; }
        .submit-btn { display: inline-block; width: 100%; background-color: #4f46e5; color: white; padding: 0.875rem 1.5rem; border: none; border-radius: 0.375rem; cursor: pointer; font-size: 1rem; font-weight: 600; transition: background-color 0.2s; }
        .submit-btn:hover { background-color: #4338ca; }
    </style>
</head>
<body>
    <div class="container">
        <h1>{{ $survey->title }}</h1>
        <p style="text-align: center; color: #6b7280;">{{ $survey->questionnaireTemplate->description }}</p>
        <hr>

        <form action="{{ route('survey.store', ['unique_code' => $survey->unique_code]) }}" method="POST">
            @csrf

            {{-- Inisialisasi penghitung nomor pertanyaan --}}
            @php $questionNumber = 1; @endphp

            {{-- Logika baru: Kelompokkan pertanyaan berdasarkan kolom 'section' --}}
            @foreach ($survey->questionnaireTemplate->content_blocks as $block)
                
                @if($block['type'] === 'section_block')
                    {{-- Tampilkan judul bagian HANYA JIKA sectionName tidak kosong --}}
                    @if($block['data']['section_title'])
                        <h2>{{ $block['data']['section_title'] }}</h2>
                    @endif

                    {{-- Loop dan tampilkan semua pertanyaan di dalam bagian ini --}}
                    @foreach ($block['data']['questions'] as $questionData)
                        @php
                            // Buat objek sementara agar partial view tetap bekerja
                            $question = (object) $questionData;
                            // Buat ID unik sementara untuk atribut 'for' pada label
                            $question->id = $loop->parent->index . '-' . $loop->index;
                        @endphp
                        
                        {{-- Kirim nomor saat ini ke partial view --}}
                        @include('partials.question-display', [
                            'question' => $question, 
                            'questionNumber' => $questionNumber
                        ])
                        
                        {{-- Naikkan hitungan untuk pertanyaan berikutnya --}}
                        @php $questionNumber++; @endphp
                    @endforeach
                @endif

            @endforeach

            <button type="submit" class="submit-btn">Kirim Jawaban</button>
        </form>
    </div>
</body>
</html>