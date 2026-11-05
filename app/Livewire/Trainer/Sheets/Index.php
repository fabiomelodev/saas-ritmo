<?php

namespace App\Livewire\Trainer\Sheets;

use App\Livewire\Concerns\ChecksTrainerOwnership;
use App\Models\WorkoutSheet;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Fichas de força')]
class Index extends Component
{
    use ChecksTrainerOwnership;

    #[Validate('required|string|max:60', as: 'nome da ficha')]
    public string $newName = '';

    // Filtros da biblioteca (ficam na URL para poder voltar à mesma lista)
    #[Url(as: 'busca', except: '')]
    public string $search = '';

    /** id da categoria, "none" = sem categoria, "" = todas */
    #[Url(as: 'categoria', except: '')]
    public string $category = '';

    /** "" = todos, "with" = com vídeo ou foto, "without" = sem mídia */
    #[Url(as: 'midia', except: '')]
    public string $media = '';

    #[Computed]
    public function sheets()
    {
        return auth()->user()->workoutSheets()
            ->with('blocks.items.exercise')
            ->withCount(['blocks'])
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function categories()
    {
        return auth()->user()->categories()->withCount('exercises')->get();
    }

    #[Computed]
    public function exercises()
    {
        return auth()->user()->exercises()
            ->with('category')
            ->withCount('images')
            ->when($this->search, fn ($q) => $q->where('name', 'like', '%'.$this->search.'%'))
            ->when($this->category === 'none', fn ($q) => $q->whereNull('category_id'))
            ->when(ctype_digit($this->category), fn ($q) => $q->where('category_id', (int) $this->category))
            ->when($this->media === 'with', fn ($q) => $q->withMedia())
            ->when($this->media === 'without', fn ($q) => $q->withoutMedia())
            ->orderBy('name')
            ->get();
    }

    /** Atalho "X exercícios sem mídia" (independe dos filtros) */
    #[Computed]
    public function withoutMediaCount(): int
    {
        return auth()->user()->exercises()->withoutMedia()->count();
    }

    #[Computed]
    public function totalExercises(): int
    {
        return auth()->user()->exercises()->count();
    }

    public function hasFilters(): bool
    {
        return $this->search !== '' || $this->category !== '' || $this->media !== '';
    }

    public function clearFilters(): void
    {
        $this->reset('search', 'category', 'media');
    }

    public function create(): void
    {
        $this->validate();

        $sheet = WorkoutSheet::create(['trainer_id' => auth()->id(), 'name' => $this->newName]);

        $this->redirectRoute('trainer.sheets.edit', $sheet, navigate: true);
    }

    public function duplicate(int $id): void
    {
        $sheet = WorkoutSheet::with('blocks.items')->findOrFail($id);
        $this->ensureOwns($sheet);

        DB::transaction(function () use ($sheet) {
            $copy = $sheet->replicate();
            $copy->name = $sheet->name.' (cópia)';
            $copy->save();

            foreach ($sheet->blocks as $block) {
                $newBlock = $copy->blocks()->create($block->only(['section', 'sets', 'position']));
                foreach ($block->items as $item) {
                    $newBlock->items()->create($item->only(['exercise_id', 'prescription', 'notes', 'position']));
                }
            }
        });

        unset($this->sheets);
    }

    public function delete(int $id): void
    {
        $sheet = WorkoutSheet::findOrFail($id);
        $this->ensureOwns($sheet);

        // Sessões que usavam a ficha ficam sem ficha (nullOnDelete)
        $sheet->delete();

        unset($this->sheets);
    }

    public function render()
    {
        return view('livewire.trainer.sheets.index', [
            'withMedia' => fn (WorkoutSheet $sheet) => $sheet->blocks
                ->flatMap->items
                ->filter(fn ($item) => $item->exercise && (filled($item->exercise->youtube_url) || $item->exercise->images()->exists()))
                ->count(),
        ]);
    }
}
