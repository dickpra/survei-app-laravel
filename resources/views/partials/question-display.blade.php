<div class="question-block">
    <label class="question-label" for="question-{{ $question->id }}">
        {{-- Menampilkan nomor urut yang dikirim dari view utama --}}
        {{ $questionNumber }}. {{ $question->content }}
    </label>

    @switch($question->type)
        @case('isian pendek')
            <input 
                type="text" 
                name="answers[{{ $question->content }}]" 
                id="question-{{ $question->id }}" 
                required
            >
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

        @case('skala likert')
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