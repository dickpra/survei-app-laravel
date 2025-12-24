<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Survei: {{ $survey->title }}</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css" />

    <style>
        body {
            font-family: Inter, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif;
            background: linear-gradient(180deg, #f9fafb, #f3f4f6);
            color: #1f2937;
            display: flex;
            justify-content: center;
            padding: 2rem 1rem;
        }

        .container {
            width: 100%;
            max-width: 820px;
            background: #fff;
            padding: 2.75rem;
            border-radius: 1rem;
            box-shadow: 0 20px 25px -5px rgba(0,0,0,0.08);
        }

        h1 { font-size: 2rem; font-weight: 700; text-align: center; margin-bottom: .5rem; }
        p.desc { text-align: center; color: #6b7280; margin-bottom: 1.25rem; }
        h2 { font-size: 1.25rem; font-weight: 600; border-bottom: 1px solid #e5e7eb; padding-bottom: .5rem; margin-top: 2.5rem; margin-bottom: 1.25rem; }
        
        .question-block { margin-bottom: 1.5rem; position: relative; }
        .question-label { font-weight: 600; margin-bottom: .5rem; display: block; color: #374151; }

        input[type="text"], select {
            width: 100%; padding: .75rem .9rem; border: 1px solid #d1d5db; border-radius: .5rem; font-size: .95rem;
        }
        input:focus, select:focus { outline: none; border-color: #4f46e5; box-shadow: 0 0 0 2px rgba(79,70,229,.15); }

        .likert-group { display: grid; grid-template-columns: repeat(auto-fit, minmax(100px, 1fr)); gap: .75rem; margin-top: .75rem; }
        .likert-item { background: #f9fafb; border: 1px solid #e5e7eb; border-radius: .5rem; padding: .75rem .5rem; text-align: center; transition: all .2s; cursor: pointer; }
        .likert-item:hover { border-color: #6366f1; background: #eef2ff; }
        .likert-label-text { font-size: .85rem; margin-bottom: .25rem; display: block; color: #4b5563; cursor: pointer; }

        .submit-btn { width: 100%; background: linear-gradient(90deg, #4f46e5, #6366f1); color: #fff; padding: 1rem; border-radius: .6rem; border: none; font-size: 1rem; font-weight: 600; cursor: pointer; margin-top: 2rem; }
        .submit-btn:hover { opacity: .95; }

        /* --- STYLING ERROR BARU --- */
        .is-invalid input[type="text"], 
        .is-invalid .choices__inner,
        .is-invalid .likert-item {
            border-color: #ef4444 !important;
            background-color: #fef2f2 !important;
        }
        .error-msg {
            color: #ef4444;
            font-size: 0.85rem;
            margin-top: 0.25rem;
            display: none;
            font-weight: 500;
        }
        .is-invalid .error-msg { display: block; }
    </style>
</head>
<script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.js-searchable').forEach(function (el) {
            new Choices(el, {
                searchEnabled: true,
                shouldSort: false,
                placeholder: true,
                searchPlaceholderValue: 'Type to search...',
                itemSelectText: '',
                allowHTML: false,
            });
        });
    });
</script>

<body>
<div class="container">
    <h1>{{ $survey->title }}</h1>
    @if(!empty($survey->questionnaireTemplate->description))
        <p class="desc">{{ $survey->questionnaireTemplate->description }}</p>
    @endif
    <hr>

    <form id="surveyForm" action="{{ route('survey.store', ['unique_code' => $survey->unique_code]) }}" method="POST" novalidate>
        @csrf

        @php $questionNumber = 1; @endphp

        {{-- ================= DEMOGRAFIS ================= --}}
        @if(!empty($survey->questionnaireTemplate->demographic_questions))
            <h2>{{ $survey->questionnaireTemplate->demographic_title }}</h2>
            
            @php
                $rawDemographic = $survey->questionnaireTemplate->demographic_questions ?? [];
                $demographicQuestions = collect($rawDemographic)->values(); 
                $globalCountries = Config::get('countries');
            @endphp

            @foreach ($demographicQuestions as $index => $question)
                <div class="question-block" id="block_demographic_{{ $index }}">
                    <label class="question-label" for="demographic_{{ $index }}">
                        {{ $questionNumber++ }}. {{ $question['question_text'] }} <span class="text-red-500"></span>
                    </label>

                    @php
                        $inputType = $question['type'] ?? '';
                        $questionTextLower = strtolower($question['question_text'] ?? '');
                        $isCountryQuestion = Str::contains($questionTextLower, ['negara', 'country', 'origin', 'nationality']);
                    @endphp

                    @if ($inputType === 'isian')
                        <input type="text" class="validate-input" name="answers[demographic][{{ $index }}][answer]" id="demographic_{{ $index }}">
                    
                    @elseif ($inputType === 'dropdown')
                        <select
                            name="answers[demographic][{{ $index }}][answer]"
                            id="demographic_{{ $index }}"
                            class="js-searchable validate-select"
                            data-placeholder="Chose...">  
                            <option value="">-- Chose answer --</option>
                            @if ($isCountryQuestion)
                                @foreach ($globalCountries as $code => $countryName)
                                    <option value="{{ $countryName }}">{{ $countryName }}</option>
                                @endforeach
                            @else
                                @foreach (($question['options'] ?? []) as $option)
                                    <option value="{{ $option }}">{{ $option }}</option>
                                @endforeach
                            @endif
                        </select>
                    @endif

                    <input type="hidden" name="answers[demographic][{{ $index }}][question_text]" value="{{ $question['question_text'] }}">
                    
                    {{-- Pesan Error --}}
                    <div class="error-msg">Wajib diisi / This field is required.</div>
                </div>
            @endforeach
        @endif

        {{-- ================= LIKERT ================= --}}
        @if(!empty($survey->questionnaireTemplate->likert_questions))
            <h2>{{ $survey->questionnaireTemplate->likert_title }}</h2>
            
            @php
                $rawLikert = $survey->questionnaireTemplate->likert_questions ?? [];
                $likertQuestions = collect($rawLikert)->values();
            @endphp

            @foreach ($likertQuestions as $index => $question)
                @php
                    $scale  = max(1, (int)($question['likert_scale'] ?? 5));
                    $manner = strtolower($question['manner'] ?? 'positive');
                    $options = range(1, $scale);

                    $labelsMap = [
                        3 => [1 => 'Disagree', 2 => 'Neutral', 3 => 'Agree'],
                        4 => [1 => 'Strongly Disagree', 2 => 'Disagree', 3 => 'Agree', 4 => 'Strongly Agree'],
                        5 => [1 => 'Strongly Disagree', 2 => 'Disagree', 3 => 'Neutral', 4 => 'Agree', 5 => 'Strongly Agree'],
                        6 => [1 => 'Strongly Disagree', 2 => 'Disagree', 3 => 'Somewhat Disagree', 4 => 'Somewhat Agree', 5 => 'Agree', 6 => 'Strongly Agree'],
                        7 => [1 => 'Strongly Disagree', 2 => 'Disagree', 3 => 'Somewhat Disagree', 4 => 'Neutral', 5 => 'Somewhat Agree', 6 => 'Agree', 7 => 'Strongly Agree'],
                    ];
                    $currentLabels = $labelsMap[$scale] ?? array_combine($options, array_map(fn($s) => "Skor $s", $options));
                @endphp

                <div class="question-block validate-radio-group" id="block_likert_{{ $index }}">
                    <p class="question-label">
                        {{ $questionNumber++ }}. {{ $question['question_text'] }} <span class="text-red-500"></span>
                    </p>

                    <div class="likert-group">
                        @foreach ($options as $visibleValue)
                            @php
                                $valueToSubmit = ($manner === 'negative') ? ($scale + 1) - $visibleValue : $visibleValue;
                            @endphp
                            <div class="likert-item" onclick="document.getElementById('likert_{{ $index }}_val{{ $visibleValue }}').click()">
                                <label for="likert_{{ $index }}_val{{ $visibleValue }}" class="likert-label-text">
                                    {{ $currentLabels[$visibleValue] ?? $visibleValue }}
                                </label>
                                <input
                                    type="radio"
                                    name="answers[likert][{{ $index }}][answer]"
                                    id="likert_{{ $index }}_val{{ $visibleValue }}"
                                    value="{{ $valueToSubmit }}"
                                    class="hidden-radio" 
                                    style="margin-top:5px;"
                                >
                            </div>
                        @endforeach
                    </div>

                    <input type="hidden" name="answers[likert][{{ $index }}][question_text]" value="{{ $question['question_text'] }}">
                    <input type="hidden" name="answers[likert][{{ $index }}][manner]" value="{{ $question['manner'] }}">
                    <input type="hidden" name="answers[likert][{{ $index }}][scale]" value="{{ $question['likert_scale'] }}">
                    
                    {{-- Pesan Error --}}
                    <div class="error-msg">Mohon pilih salah satu / Please select an option.</div>
                </div>
            @endforeach
        @endif

        <button type="submit" class="submit-btn" id="btnSubmit">Submit Answer</button>
    </form>
</div>

{{-- SCRIPT VALIDASI KUSTOM --}}
<script>
    document.getElementById('surveyForm').addEventListener('submit', function(e) {
        let isValid = true;
        let firstErrorElement = null;

        // 1. Reset semua error state
        document.querySelectorAll('.question-block').forEach(el => el.classList.remove('is-invalid'));

        // 2. Validasi Isian Teks (Demografi)
        document.querySelectorAll('.validate-input').forEach(input => {
            if (!input.value.trim()) {
                isValid = false;
                const block = input.closest('.question-block');
                block.classList.add('is-invalid');
                if (!firstErrorElement) firstErrorElement = block;
            }
        });

        // 3. Validasi Select / Dropdown (Demografi)
        document.querySelectorAll('.validate-select').forEach(select => {
            if (!select.value) {
                isValid = false;
                const block = select.closest('.question-block');
                block.classList.add('is-invalid');
                if (!firstErrorElement) firstErrorElement = block;
            }
        });

        // 4. Validasi Radio Buttons (Likert)
        // Kita loop per blok pertanyaan likert
        document.querySelectorAll('.validate-radio-group').forEach(group => {
            // Cari semua radio button di dalam grup ini
            const radios = group.querySelectorAll('input[type="radio"]');
            let isChecked = false;
            radios.forEach(r => {
                if (r.checked) isChecked = true;
            });

            if (!isChecked) {
                isValid = false;
                group.classList.add('is-invalid');
                if (!firstErrorElement) firstErrorElement = group;
            }
        });

        // 5. Eksekusi
        if (!isValid) {
            e.preventDefault(); // Stop submit
            // Scroll ke error pertama
            if (firstErrorElement) {
                firstErrorElement.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
            // Optional: Alert sederhana
            // alert('Mohon lengkapi semua pertanyaan yang ditandai merah.');
        } else {
            // Mencegah double submit
            const btn = document.getElementById('btnSubmit');
            btn.innerHTML = 'Sending...';
            btn.disabled = true;
        }
    });

    // Tambahan UX: Klik item likert langsung pilih radio
    // (Sudah dihandle di inline onclick HTML di atas, tapi ini backup logic CSS active state)
    const likertRadios = document.querySelectorAll('input[type="radio"]');
    likertRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            // Reset background color in this group
            const group = this.closest('.likert-group');
            group.querySelectorAll('.likert-item').forEach(item => {
                item.style.backgroundColor = '#f9fafb';
                item.style.borderColor = '#e5e7eb';
            });
            
            // Highlight selected
            const parentItem = this.closest('.likert-item');
            if(this.checked) {
                parentItem.style.backgroundColor = '#eef2ff'; // indigo-50
                parentItem.style.borderColor = '#6366f1'; // indigo-500
                
                // Hapus error jika user sudah memilih
                this.closest('.question-block').classList.remove('is-invalid');
            }
        });
    });
</script>

</body>
</html>