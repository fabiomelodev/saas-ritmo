<?php

namespace App\Livewire\Student;

use App\Livewire\Concerns\LoadsStudentSession;
use App\Models\BlockExercise;
use App\Models\PlanSession;
use App\Models\SessionLog;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

/** Detalhe do exercício: vídeo (YouTube), fotos, séries e instruções */
#[Layout('layouts.app', ['hideNav' => true])]
#[Title('Exercício')]
class Exercise extends Component
{
    use LoadsStudentSession;

    #[Locked]
    public int $sessionId;

    #[Locked]
    public int $itemId;

    public function mount(PlanSession $session, BlockExercise $item): void
    {
        $session = $this->loadStudentSession($session->id);
        $item->loadMissing('block');

        // O exercício precisa ser da ficha deste dia
        abort_unless($session->workout_sheet_id && (int) $item->block->workout_sheet_id === (int) $session->workout_sheet_id, 404);

        $this->sessionId = $session->id;
        $this->itemId = $item->id;
    }

    #[Computed]
    public function session(): PlanSession
    {
        return $this->loadStudentSession($this->sessionId);
    }

    #[Computed]
    public function item(): BlockExercise
    {
        return BlockExercise::with(['block.items', 'exercise.images'])->findOrFail($this->itemId);
    }

    /** Ordem de todos os exercícios da ficha, para o "Próximo" */
    #[Computed]
    public function sequence()
    {
        return $this->session->workoutSheet->blocks()->with('items.exercise')->get()->flatMap->items->values();
    }

    #[Computed]
    public function next(): ?BlockExercise
    {
        $index = $this->sequence->search(fn ($i) => $i->id === $this->itemId);

        return $index === false ? null : $this->sequence->get($index + 1);
    }

    /** ids marcados na ficha deste dia */
    #[Computed]
    public function checkedIds(): array
    {
        return array_map('intval', $this->session->log?->checked_items ?? []);
    }

    #[Computed]
    public function isDone(): bool
    {
        return in_array($this->itemId, array_map('intval', $this->session->log?->checked_items ?? []), true);
    }

    /** Marca como feito e volta para a ficha */
    public function markDone(): void
    {
        abort_unless($this->session->canBeLogged(), 403);

        $log = $this->session->log;
        $checked = array_map('intval', $log?->checked_items ?? []);

        if (! in_array($this->itemId, $checked, true)) {
            $checked[] = $this->itemId;
        }

        SessionLog::updateOrCreate(
            ['plan_session_id' => $this->sessionId],
            [
                'student_id' => auth()->id(),
                'status' => $log?->status === 'done' ? 'done' : 'in_progress',
                'checked_items' => $checked,
            ],
        );

        $this->redirectRoute('student.session', $this->sessionId, navigate: true);
    }

    public function render()
    {
        return view('livewire.student.exercise')->title($this->item->exercise->name);
    }
}
