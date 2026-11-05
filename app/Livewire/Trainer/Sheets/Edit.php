<?php

namespace App\Livewire\Trainer\Sheets;

use App\Livewire\Concerns\ChecksTrainerOwnership;
use App\Models\Category;
use App\Models\Exercise;
use App\Models\WorkoutSheet;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Editor da ficha: blocos (exercícios que dividem as mesmas séries) + mobilidade.
 * O formulário guarda tudo em $blocks e grava de uma vez em save().
 */
#[Layout('layouts.app')]
#[Title('Editar ficha')]
class Edit extends Component
{
    use ChecksTrainerOwnership;

    #[Locked]
    public int $sheetId;

    public string $name = '';

    /** @var array<int, array{section: string, sets: ?int, items: array<int, array{exercise_id: ?int, exercise_name: string, prescription: ?string, notes: ?string}>}> */
    public array $blocks = [];

    public function mount(WorkoutSheet $sheet): void
    {
        $this->ensureOwns($sheet);

        $this->sheetId = $sheet->id;
        $this->name = $sheet->name;
        $this->blocks = $sheet->blocks()->with('items.exercise')->get()->map(fn ($block) => [
            'section' => $block->section,
            'sets' => $block->sets,
            'items' => $block->items->map(fn ($item) => [
                'exercise_id' => $item->exercise_id,
                'exercise_name' => $item->exercise?->name ?? '',
                'prescription' => $item->prescription,
                'notes' => $item->notes,
            ])->all(),
        ])->all();

        if (! $this->blocks) {
            $this->addBlock('strength');
        }
    }

    /** Nomes da biblioteca para o autocompletar */
    #[Computed]
    public function library()
    {
        return auth()->user()->exercises()->orderBy('name')->pluck('name');
    }

    public function addBlock(string $section = 'strength'): void
    {
        $this->blocks[] = [
            'section' => $section === 'mobility' ? 'mobility' : 'strength',
            'sets' => $section === 'mobility' ? 1 : 3,
            'items' => [$this->emptyItem()],
        ];
    }

    public function removeBlock(int $index): void
    {
        unset($this->blocks[$index]);
        $this->blocks = array_values($this->blocks);
    }

    public function moveBlock(int $index, int $direction): void
    {
        $target = $index + $direction;

        if (! isset($this->blocks[$index], $this->blocks[$target])) {
            return;
        }

        [$this->blocks[$index], $this->blocks[$target]] = [$this->blocks[$target], $this->blocks[$index]];
    }

    public function addItem(int $blockIndex): void
    {
        $this->blocks[$blockIndex]['items'][] = $this->emptyItem();
    }

    public function removeItem(int $blockIndex, int $itemIndex): void
    {
        unset($this->blocks[$blockIndex]['items'][$itemIndex]);
        $this->blocks[$blockIndex]['items'] = array_values($this->blocks[$blockIndex]['items']);
    }

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:60'],
            'blocks' => ['array'],
            'blocks.*.section' => ['required', 'in:strength,mobility'],
            'blocks.*.sets' => ['nullable', 'integer', 'min:1', 'max:20'],
            'blocks.*.items' => ['array'],
            'blocks.*.items.*.exercise_name' => ['nullable', 'string', 'max:120'],
            'blocks.*.items.*.prescription' => ['nullable', 'string', 'max:50'],
            'blocks.*.items.*.notes' => ['nullable', 'string', 'max:255'],
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'name' => 'nome da ficha',
            'blocks.*.sets' => 'séries',
            'blocks.*.items.*.exercise_name' => 'exercício',
            'blocks.*.items.*.prescription' => 'repetições',
        ];
    }

    public function save(): void
    {
        $this->validate();

        $sheet = WorkoutSheet::findOrFail($this->sheetId);
        $this->ensureOwns($sheet);

        DB::transaction(function () use ($sheet) {
            $sheet->update(['name' => $this->name]);

            // Regrava os blocos na ordem da tela
            $sheet->blocks()->delete();

            foreach (array_values($this->blocks) as $position => $block) {
                $items = collect($block['items'])->filter(fn ($i) => filled(trim($i['exercise_name'] ?? '')));

                if ($items->isEmpty()) {
                    continue;
                }

                $newBlock = $sheet->blocks()->create([
                    'section' => $block['section'],
                    'sets' => $block['sets'] ?: null,
                    'position' => $position,
                ]);

                foreach ($items->values() as $itemPosition => $item) {
                    // Exercício novo num bloco de mobilidade já entra na categoria "Mobilidade"
                    $exercise = Exercise::firstOrCreate(
                        ['trainer_id' => auth()->id(), 'name' => trim($item['exercise_name'])],
                        ['category_id' => $block['section'] === 'mobility' ? Category::mobilityFor(auth()->id())->id : null],
                    );

                    $newBlock->items()->create([
                        'exercise_id' => $exercise->id,
                        'prescription' => $item['prescription'] ?: null,
                        'notes' => $item['notes'] ?: null,
                        'position' => $itemPosition,
                    ]);
                }
            }
        });

        session()->flash('saved', 'Ficha salva.');

        $this->redirectRoute('trainer.sheets.edit', $sheet, navigate: true);
    }

    /** Liga o nome digitado ao exercício da biblioteca (para o link "vídeo e fotos") */
    public function exerciseIdFor(string $name): ?int
    {
        return Exercise::where('trainer_id', auth()->id())->where('name', trim($name))->value('id');
    }

    private function emptyItem(): array
    {
        return ['exercise_id' => null, 'exercise_name' => '', 'prescription' => null, 'notes' => null];
    }

    public function render()
    {
        return view('livewire.trainer.sheets.edit');
    }
}
