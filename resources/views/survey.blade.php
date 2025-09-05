<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Survei: {{ $survey->title }}</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; background-color: #f3f4f6; color: #1f2937; line-height: 1.6; display: flex; justify-content: center; padding: 2rem 1rem; }
        .container { width: 100%; max-width: 800px; background: #fff; padding: 2.5rem; border-radius: 0.75rem; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1), 0 4px 6px -2px rgba(0,0,0,0.05); }
        h1, h2 { color: #111827; font-weight: 700; }
        h1 { font-size: 1.875rem; text-align: center; margin-bottom: 0.75rem; }
        h2 { font-size: 1.25rem; border-bottom: 1px solid #e5e7eb; padding-bottom: 0.5rem; margin-top: 2rem; margin-bottom: 1rem; }
        p.desc { text-align: center; color: #6b7280; margin-bottom: 0.5rem; }
        hr { border: none; height: 1px; background-color: #e5e7eb; margin: 1.25rem 0 1.5rem; }
        .question-block { margin-bottom: 1.25rem; }
        .question-label { font-weight: 600; display: block; margin-bottom: 0.5rem; color: #374151; }
        input[type="text"], select { width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 0.375rem; box-sizing: border-box; }
        .likert-group { display: flex; justify-content: space-between; gap: 8px; flex-wrap: wrap; text-align: center; }
        .likert-item { display: flex; flex-direction: column; align-items: center; width: calc(20% - 8px); min-width: 100px; }
        .likert-label-text { font-size: 0.9rem; color: #4b5563; margin-bottom: 0.25rem; }
        .submit-btn { display: inline-block; width: 100%; background-color: #4f46e5; color: white; padding: 0.875rem 1.5rem; border: none; border-radius: 0.375rem; cursor: pointer; font-size: 1rem; font-weight: 600; transition: background-color 0.2s; margin-top: 1.5rem; }
        .submit-btn:hover { background-color: #4338ca; }
    </style>
</head>
<body>
<div class="container">
    <h1>{{ $survey->title }}</h1>
    @if(!empty($survey->questionnaireTemplate->description))
        <p class="desc">{{ $survey->questionnaireTemplate->description }}</p>
    @endif
    <hr>

    <form action="{{ route('survey.store', ['unique_code' => $survey->unique_code]) }}" method="POST">
        @csrf

        @php $questionNumber = 1; @endphp

        {{-- ================= DEMOGRAFIS ================= --}}
        @if(!empty($survey->questionnaireTemplate->demographic_questions))
            <h2>{{ $survey->questionnaireTemplate->demographic_title }}</h2>
            @foreach ($survey->questionnaireTemplate->demographic_questions as $index => $question)
                <div class="question-block">
                    <label class="question-label" for="demographic_{{ $index }}">
                        {{ $questionNumber++ }}. {{ $question['question_text'] }}
                    </label>

                    @if (($question['type'] ?? '') === 'isian')
                        <input type="text" name="answers[demographic][{{ $index }}][answer]" id="demographic_{{ $index }}" required>
                    @elseif (($question['type'] ?? '') === 'dropdown')
                        <select name="answers[demographic][{{ $index }}][answer]" id="demographic_{{ $index }}" required>
                            <option value="">-- Pilih Jawaban --</option>
                            @foreach (($question['options'] ?? []) as $option)
                                <option value="{{ $option }}">{{ $option }}</option>
                            @endforeach
                        </select>
                    @endif

                    <input type="hidden" name="answers[demographic][{{ $index }}][question_text]" value="{{ $question['question_text'] }}">
                </div>
            @endforeach
        @endif

        {{-- ================= LIKERT ================= --}}
        @if(!empty($survey->questionnaireTemplate->likert_questions))
            <h2>{{ $survey->questionnaireTemplate->likert_title }}</h2>

            @foreach ($survey->questionnaireTemplate->likert_questions as $index => $question)
                @php
                    $scale  = max(1, (int)($question['likert_scale'] ?? 5));
                    $manner = strtolower($question['manner'] ?? 'positive');
                    $options = range(1, $scale); // label SELALU 1..N

                    // label standar untuk skala 3..7
                    $labelsMap = [
                        3 => [1 => 'Tidak Setuju', 2 => 'Netral', 3 => 'Setuju'],
                        4 => [1 => 'Sangat Tidak Setuju', 2 => 'Tidak Setuju', 3 => 'Setuju', 4 => 'Sangat Setuju'],
                        5 => [1 => 'Sangat Tidak Setuju', 2 => 'Tidak Setuju', 3 => 'Netral', 4 => 'Setuju', 5 => 'Sangat Setuju'],
                        6 => [1 => 'Sangat Tidak Setuju', 2 => 'Tidak Setuju', 3 => 'Agak Tidak Setuju', 4 => 'Agak Setuju', 5 => 'Setuju', 6 => 'Sangat Setuju'],
                        7 => [1 => 'Sangat Tidak Setuju', 2 => 'Tidak Setuju', 3 => 'Agak Tidak Setuju', 4 => 'Netral', 5 => 'Agak Setuju', 6 => 'Setuju', 7 => 'Sangat Setuju'],
                    ];
                    $currentLabels = $labelsMap[$scale] ?? array_combine($options, array_map(fn($s) => "Skor $s", $options));
                @endphp

                <div class="question-block">
                    <p class="question-label">{{ $questionNumber++ }}. {{ $question['question_text'] }}</p>

                    <div class="likert-group">
                        @foreach ($options as $visibleValue)
                            @php
                                // HANYA NILAI yang dibalik jika pertanyaan negatif
                                $valueToSubmit = ($manner === 'negative')
                                    ? ($scale + 1) - $visibleValue
                                    : $visibleValue;
                            @endphp
                            <div class="likert-item">
                                <label for="likert_{{ $index }}_val{{ $visibleValue }}" class="likert-label-text">
                                    {{ $currentLabels[$visibleValue] ?? $visibleValue }}
                                </label>
                                <input
                                    type="radio"
                                    name="answers[likert][{{ $index }}][answer]"
                                    id="likert_{{ $index }}_val{{ $visibleValue }}"
                                    value="{{ $valueToSubmit }}"
                                    required
                                >
                            </div>
                        @endforeach
                    </div>

                    {{-- meta --}}
                    <input type="hidden" name="answers[likert][{{ $index }}][question_text]" value="{{ $question['question_text'] }}">
                    <input type="hidden" name="answers[likert][{{ $index }}][manner]" value="{{ $question['manner'] }}">
                    <input type="hidden" name="answers[likert][{{ $index }}][scale]" value="{{ $question['likert_scale'] }}">
                    <input type="hidden" name="answers[likert][{{ $index }}][_flipped]" value="{{ $manner === 'negative' ? 1 : 0 }}">
                </div>
            @endforeach
        @endif

        <button type="submit" class="submit-btn">Kirim Jawaban</button>
    </form>
</div>
</body>
</html>
