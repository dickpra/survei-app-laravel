<div class="p-4 space-y-8 bg-gray-50 rounded-lg">
    <style>
        /* Add some styling to make disabled fields look better */
        input:disabled, textarea:disabled, select:disabled {
            cursor: not-allowed;
            background-color: #f3f4f6;
            color: #6b7280;
        }
        .likert-radio:disabled + label {
            cursor: not-allowed;
            opacity: 0.7;
        }
    </style>

    {{-- Demographic Questions --}}
    @if(!empty($template->demographic_questions))
        <section class="p-6 bg-white rounded-lg shadow">
            <h2 class="text-xl font-bold mb-4">{{ $template->demographic_title }}</h2>
            <div class="space-y-6">
                @foreach($template->demographic_questions as $index => $question)
                    <div>
                        <label class="block text-sm font-medium text-gray-700">
                            {{ $loop->iteration }}. {{ $question['question_text'] }}
                        </label>

                        @if(($question['type'] ?? 'isian') === 'dropdown')
                            {{-- [PERBAIKAN] Logic untuk menangani Options kosong / Negara --}}
                            @php
                                // 1. Ambil opsi manual jika ada, jika null ganti array kosong
                                $currentOptions = $question['options'] ?? [];

                                // 2. Deteksi apakah ini pertanyaan Negara
                                $qText = strtolower($question['question_text'] ?? '');
                                $isCountry = \Illuminate\Support\Str::contains($qText, ['negara', 'country', 'origin']);

                                // 3. Jika Negara & Opsi kosong, panggil list negara dari Resource
                                if ($isCountry && empty($currentOptions)) {
                                    // Pastikan path class ini sesuai dengan file Resource Anda
                                    $currentOptions = \App\Filament\Admin\Resources\QuestionnaireTemplateResource::getCountries();
                                }
                            @endphp

                            <select disabled class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                                <option>-- Pilihan --</option>
                                {{-- Loop data yang sudah aman --}}
                                @foreach($currentOptions as $val => $label)
                                    {{-- Handle jika array asosiatif (Negara) atau array biasa (Manual) --}}
                                    <option>{{ is_string($val) ? $label : $label }}</option>
                                @endforeach
                            </select>
                        @else
                            <textarea disabled rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"></textarea>
                        @endif
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    {{-- Likert Scale Questions --}}
    @if(!empty($template->likert_questions))
        <section class="p-6 bg-white rounded-lg shadow">
            <h2 class="text-xl font-bold mb-4">{{ $template->likert_title }}</h2>
            <div class="space-y-6">
                @php
                    // $likertLabels = [
                    //     3 => [1 => 'Tidak Setuju', 2 => 'Netral', 3 => 'Setuju'],
                    //     4 => [1 => 'Sangat Tidak Setuju', 2 => 'Tidak Setuju', 3 => 'Setuju', 4 => 'Sangat Setuju'],
                    //     5 => [1 => 'Sangat Tidak Setuju', 2 => 'Tidak Setuju', 3 => 'Netral', 4 => 'Setuju', 5 => 'Sangat Setuju'],
                    //     6 => [1 => 'Sangat Tidak Setuju', 2 => 'Tidak Setuju', 3 => 'Agak Tidak Setuju', 4 => 'Agak Setuju', 5 => 'Setuju', 6 => 'Sangat Setuju'],
                    //     7 => [1 => 'Sangat Tidak Setuju', 2 => 'Tidak Setuju', 3 => 'Agak Tidak Setuju', 4 => 'Netral', 5 => 'Agak Setuju', 6 => 'Setuju', 7 => 'Sangat Setuju'],
                    // ];
                    $likertLabels = [
                        3 => [
                            1 => 'Disagree',
                            2 => 'Neutral',
                            3 => 'Agree',
                        ],
                        4 => [
                            1 => 'Strongly Disagree',
                            2 => 'Disagree',
                            3 => 'Agree',
                            4 => 'Strongly Agree',
                        ],
                        5 => [
                            1 => 'Strongly Disagree',
                            2 => 'Disagree',
                            3 => 'Neutral',
                            4 => 'Agree',
                            5 => 'Strongly Agree',
                        ],
                        6 => [
                            1 => 'Strongly Disagree',
                            2 => 'Disagree',
                            3 => 'Somewhat Disagree',
                            4 => 'Somewhat Agree',
                            5 => 'Agree',
                            6 => 'Strongly Agree',
                        ],
                        7 => [
                            1 => 'Strongly Disagree',
                            2 => 'Disagree',
                            3 => 'Somewhat Disagree',
                            4 => 'Neutral',
                            5 => 'Somewhat Agree',
                            6 => 'Agree',
                            7 => 'Strongly Agree',
                        ],
                    ];
                @endphp
                @foreach($template->likert_questions as $index => $question)
                    <div>
                        <p class="text-sm font-medium text-gray-700 mb-2">
                            {{ $loop->iteration }}. {{ $question['question_text'] }}
                        </p>
                        @php
                            $scale = $question['likert_scale'] ?? 5;
                            $labels = $likertLabels[$scale] ?? range(1, $scale);
                        @endphp
                        <div class="flex justify-between items-center text-center text-xs text-gray-500 px-2">
                            @foreach($labels as $label)
                                <span>{{ $label }}</span>
                            @endforeach
                        </div>
                        <div class="flex justify-between items-center mt-1">
                            @for ($i = 1; $i <= $scale; $i++)
                                <input type="radio" disabled name="likert_{{ $index }}" id="likert_{{ $index }}_{{ $i }}" class="likert-radio">
                                <label for="likert_{{ $index }}_{{ $i }}" class="w-full text-center p-2"></label>
                            @endfor
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
    @endif
</div>