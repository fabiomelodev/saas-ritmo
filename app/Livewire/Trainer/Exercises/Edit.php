<?php

namespace App\Livewire\Trainer\Exercises;

use App\Livewire\Concerns\ChecksTrainerOwnership;
use App\Models\Category;
use App\Models\Exercise;
use App\Models\ExerciseImage;
use App\Models\WorkoutSheet;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
#[Title('Editar exercício')]
class Edit extends Component
{
    use ChecksTrainerOwnership;
    use WithFileUploads;

    public const MAX_IMAGES = 4;

    #[Locked]
    public int $exerciseId;

    public string $name = '';

    /** id da categoria como texto ('' = sem categoria), como vem do <select> */
    public string $category_id = '';

    /** Campo "+ Nova categoria" */
    public string $newCategory = '';

    public bool $creatingCategory = false;

    public string $instructions = '';

    public string $youtube_url = '';

    /** "0:42" — a partir de que ponto o vídeo começa */
    public string $video_start = '';

    /** @var array<int, \Livewire\Features\SupportFileUploads\TemporaryUploadedFile> */
    public array $photos = [];

    /** @var array<int, string> legendas por id da imagem */
    public array $captions = [];

    public function mount(Exercise $exercise): void
    {
        $this->ensureOwns($exercise);

        $this->exerciseId = $exercise->id;
        $this->name = $exercise->name;
        $this->category_id = (string) $exercise->category_id;
        $this->instructions = (string) $exercise->instructions;
        $this->youtube_url = (string) $exercise->youtube_url;
        $this->video_start = $exercise->video_start_seconds !== null
            ? intdiv($exercise->video_start_seconds, 60).':'.str_pad((string) ($exercise->video_start_seconds % 60), 2, '0', STR_PAD_LEFT)
            : '';
        $this->captions = $exercise->images->pluck('caption', 'id')->map(fn ($c) => (string) $c)->all();
    }

    #[Computed]
    public function exercise(): Exercise
    {
        return Exercise::with('images')->findOrFail($this->exerciseId);
    }

    /** Fichas em que o exercício aparece */
    #[Computed]
    public function sheets()
    {
        return WorkoutSheet::where('trainer_id', auth()->id())
            ->whereHas('blocks.items', fn ($q) => $q->where('exercise_id', $this->exerciseId))
            ->orderBy('name')
            ->get();
    }

    protected function rules(): array
    {
        $remaining = max(self::MAX_IMAGES - $this->exercise->images->count(), 0);

        return [
            'name' => ['required', 'string', 'max:120'],
            'category_id' => ['nullable', Rule::exists('categories', 'id')->where('trainer_id', auth()->id())],
            'instructions' => ['nullable', 'string', 'max:2000'],
            'youtube_url' => ['nullable', 'url', 'regex:~(youtube\.com|youtu\.be)~i'],
            'video_start' => ['nullable', 'regex:/^\d{1,3}(:[0-5]\d)?$/'],
            'photos' => ['array', 'max:'.$remaining],
            'photos.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'captions.*' => ['nullable', 'string', 'max:120'],
        ];
    }

    protected function messages(): array
    {
        return [
            'youtube_url.regex' => 'Use um link do YouTube.',
            'video_start.regex' => 'Use o formato minutos:segundos, ex.: 0:42.',
            'photos.max' => 'Cada exercício aceita até '.self::MAX_IMAGES.' fotos.',
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'name' => 'nome',
            'category_id' => 'categoria',
            'youtube_url' => 'link do vídeo',
            'video_start' => 'início do vídeo',
            'photos.*' => 'foto',
        ];
    }

    public function save(): void
    {
        $this->validate();

        $exercise = $this->exercise;

        $exercise->update([
            'name' => $this->name,
            'category_id' => $this->category_id !== '' ? (int) $this->category_id : null,
            'instructions' => $this->instructions ?: null,
            'youtube_url' => $this->youtube_url ?: null,
            'video_start_seconds' => $this->parseStart($this->video_start),
        ]);

        foreach ($exercise->images as $image) {
            $image->update(['caption' => ($this->captions[$image->id] ?? null) ?: null]);
        }

        $position = (int) $exercise->images->max('position') + 1;

        foreach ($this->photos as $photo) {
            $exercise->images()->create([
                'path' => $photo->store("exercises/{$exercise->id}", 'public'),
                'position' => $position++,
            ]);
        }

        $this->photos = [];
        unset($this->exercise);
        $this->captions = $this->exercise->images->pluck('caption', 'id')->map(fn ($c) => (string) $c)->all();

        session()->now('saved', 'Exercício salvo.');
    }

    #[Computed]
    public function categories()
    {
        return auth()->user()->categories()->get(['id', 'name']);
    }

    /** Cria a categoria sem sair da tela e já seleciona */
    public function addCategory(): void
    {
        $this->newCategory = trim($this->newCategory);

        $this->validateOnly('newCategory', [
            'newCategory' => ['required', 'string', 'max:50', Rule::unique('categories', 'name')->where('trainer_id', auth()->id())],
        ], [], ['newCategory' => 'categoria']);

        $category = Category::create([
            'trainer_id' => auth()->id(),
            'name' => $this->newCategory,
            'position' => (int) Category::where('trainer_id', auth()->id())->max('position') + 1,
        ]);

        $this->category_id = (string) $category->id;
        $this->reset('newCategory', 'creatingCategory');
        unset($this->categories);
    }

    public function removeImage(int $id): void
    {
        $image = ExerciseImage::where('exercise_id', $this->exerciseId)->findOrFail($id);

        Storage::disk('public')->delete($image->path);
        $image->delete();

        unset($this->captions[$id], $this->exercise);
    }

    public function removePhoto(int $index): void
    {
        unset($this->photos[$index]);
        $this->photos = array_values($this->photos);
    }

    private function parseStart(string $value): ?int
    {
        if (blank($value)) {
            return null;
        }

        $parts = array_map('intval', explode(':', $value));

        return count($parts) === 2 ? $parts[0] * 60 + $parts[1] : $parts[0];
    }

    public function render()
    {
        return view('livewire.trainer.exercises.edit');
    }
}
