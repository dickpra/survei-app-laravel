<div class="p-4 space-y-8 bg-gray-50 rounded-lg">
    <style>
        /* Add some styling to make disabled fields look better */
        input:disabled, textarea:disabled, select:disabled {
            cursor: not-allowed;
            background-color: #f3f4f6;
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
                        @if($question['type'] === 'dropdown')
                            <select disabled class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                                <option>-- Pilihan --</option>
                                @foreach($question['options'] as $option)
                                    <option>{{ $option }}</option>
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
                    $likertLabels = [
                        5 => ['Sangat Tidak Setuju', 'Tidak Setuju', 'Netral', 'Setuju', 'Sangat Setuju'],
                        // Add other scales as needed
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