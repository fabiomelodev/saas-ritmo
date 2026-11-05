<?php

namespace App\Livewire\Trainer;

use App\Models\Category;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/** Criar, renomear, reordenar e excluir as categorias da biblioteca */
#[Layout('layouts.app')]
#[Title('Categorias')]
class Categories extends Component
{
    public string $newName = '';

    /** @var array<int, string> nomes editados por id */
    public array $names = [];

    public function mount(): void
    {
        $this->loadNames();
    }

    #[Computed]
    public function categories()
    {
        return auth()->user()->categories()->withCount('exercises')->get();
    }

    #[Computed]
    public function uncategorizedCount(): int
    {
        return auth()->user()->exercises()->whereNull('category_id')->count();
    }

    public function create(): void
    {
        $this->newName = trim($this->newName);

        $this->validate([
            'newName' => ['required', 'string', 'max:50', Rule::unique('categories', 'name')->where('trainer_id', auth()->id())],
        ], [], ['newName' => 'categoria']);

        Category::create([
            'trainer_id' => auth()->id(),
            'name' => $this->newName,
            'position' => (int) Category::where('trainer_id', auth()->id())->max('position') + 1,
        ]);

        $this->reset('newName');
        $this->refresh();
    }

    /** Renomeia ao sair do campo */
    public function rename(int $id): void
    {
        $category = $this->find($id);
        $this->names[$id] = trim($this->names[$id] ?? '');

        $this->validate([
            "names.$id" => ['required', 'string', 'max:50', Rule::unique('categories', 'name')->where('trainer_id', auth()->id())->ignore($id)],
        ], [], ["names.$id" => 'categoria']);

        $category->update(['name' => $this->names[$id]]);
        $this->refresh();
    }

    public function move(int $id, int $direction): void
    {
        $list = $this->categories->values();
        $index = $list->search(fn ($c) => $c->id === $id);
        $target = $index + $direction;

        if ($index === false || ! isset($list[$target])) {
            return;
        }

        // Regrava a ordem inteira para não depender de posições repetidas
        $ordered = $list->all();
        [$ordered[$index], $ordered[$target]] = [$ordered[$target], $ordered[$index]];

        foreach ($ordered as $position => $category) {
            $category->update(['position' => $position]);
        }

        $this->refresh();
    }

    /** Os exercícios da categoria ficam "Sem categoria" (nullOnDelete) */
    public function delete(int $id): void
    {
        $this->find($id)->delete();
        $this->refresh();
    }

    private function find(int $id): Category
    {
        return Category::where('trainer_id', auth()->id())->findOrFail($id);
    }

    private function refresh(): void
    {
        unset($this->categories, $this->uncategorizedCount);
        $this->resetValidation();
        $this->loadNames();
    }

    private function loadNames(): void
    {
        $this->names = $this->categories->pluck('name', 'id')->all();
    }

    public function render()
    {
        return view('livewire.trainer.categories');
    }
}
