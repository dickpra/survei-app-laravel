<div class="question-block">
    <label class="question-label" for="question-{{ $question->id }}">
        {{-- Menampilkan nomor urut yang dikirim dari view utama --}}
        {{ $questionNumber }}. {{ $question->content }}
    </label>

    @switch($question->type)
        @case('isian pendek')
            <textarea
                name="answers[{{ $question->content }}]"
                id="question-{{ $question->id }}"
                rows="3"
                class="w-full p-3 border border-gray-300 rounded resize-y focus:border-indigo-600 focus:shadow-outline"
                required
            >{{ old('answers.' . $question->content) }}</textarea>
            @break

        @case('pilihan ganda')
            <div class="radio-group">
                @foreach ($question->options as $option)
                    <div class="radio-item">
                        <input 
                            type="radio" 
                            name="answers[{{ $question->content }}]" 
                            value="{{ $option }}" 
                            id="option-{{ $question->id }}-{{ $loop->index }}" 
                            required
                        >
                        <label for="option-{{ $question->id }}-{{ $loop->index }}">{{ $option }}</label>
                    </div>
                @endforeach
            </div>
            @break

        {{-- @case('skala likert')
            <div class="likert-group">
                @for ($i = 1; $i <= 5; $i++)
                    <div class="likert-item">
                        <label for="likert-{{ $question->id }}-{{ $i }}">{{ $i }}</label>
                        <input 
                            type="radio" 
                            name="answers[{{ $question->content }}]" 
                            value="{{ $i }}" 
                            id="likert-{{ $question->id }}-{{ $i }}" 
                            required
                        >
                    </div>
                @endfor
            </div>
            @break --}}

            @case('skala likert')
            <div class="likert-group">
                {{-- Cek apakah admin menyediakan opsi label custom --}}
                @if(!empty($question->options) && count($question->options) > 0)
                    {{-- Jika ya, loop melalui label custom tersebut --}}
                    @foreach($question->options as $index => $label)
                        <div class="likert-item">
                            <label for="likert-{{ $question->id }}-{{ $index }}">{{ $label }}</label>
                            {{-- Nilai yang disimpan tetap angka (1, 2, 3, ...) --}}
                            <input type="radio" name="answers[{{ $question->content }}]" value="{{ $index + 1 }}" id="likert-{{ $question->id }}-{{ $index }}" required>
                        </div>
                    @endforeach
                @else
                    {{-- Jika tidak, kembali ke tampilan angka 1-5 --}}
                    @for ($i = 1; $i <= 5; $i++)
                        <div class="likert-item">
                            <label for="likert-{{ $question->id }}-{{ $i }}">{{ $i }}</label>
                            <input type="radio" name="answers[{{ $question->content }}]" value="{{ $i }}" id="likert-{{ $question->id }}-{{ $i }}" required>
                        </div>
                    @endfor
                @endif
            </div>
            @break
            
        @case('dropdown')
            <select 
                name="answers[{{ $question->content }}]" 
                id="question-{{ $question->id }}" 
                required
            >
                <option value="" disabled selected>-- Pilih salah satu --</option>
                @foreach ($question->options as $option)
                    <option value="{{ $option }}">{{ $option }}</option>
                @endforeach
            </select>
            @break

    @endswitch
</div>