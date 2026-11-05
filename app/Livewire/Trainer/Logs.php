<?php

namespace App\Livewire\Trainer;

use App\Models\LogReply;
use App\Models\SessionLog;
use App\Notifications\TrainerReplied;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/** Registros dos alunos: o que fizeram, PSE, comentário e respostas */
#[Layout('layouts.app')]
#[Title('Registros')]
class Logs extends Component
{
    use WithPagination;

    public const QUICK_REPLIES = ['Mandou bem!', 'Segure o ritmo', 'Vamos conversar'];

    public const HARD_RPE = 8;

    /** pending = sem resposta · all · skipped = não fez · hard = PSE alta */
    #[Url(as: 'filtro', except: 'pending')]
    public string $filter = 'pending';

    #[Url(as: 'aluno', except: '')]
    public string $student = '';

    /** @var array<int, string> rascunho de resposta por registro */
    public array $drafts = [];

    public function updatedFilter(): void
    {
        $this->resetPage();
    }

    public function updatedStudent(): void
    {
        $this->resetPage();
    }

    private function baseQuery()
    {
        return SessionLog::forTrainer(auth()->id())
            ->when(ctype_digit($this->student), fn ($q) => $q->where('student_id', (int) $this->student));
    }

    #[Computed]
    public function counts(): array
    {
        return [
            'pending' => $this->baseQuery()->awaitingReply(auth()->id())->count(),
            'all' => $this->baseQuery()->count(),
            'skipped' => $this->baseQuery()->where('status', 'skipped')->count(),
            'hard' => $this->baseQuery()->where('rpe', '>=', self::HARD_RPE)->count(),
        ];
    }

    #[Computed]
    public function logs()
    {
        return $this->baseQuery()
            ->with(['student', 'session.week.plan', 'session.workoutSheet', 'replies.author'])
            ->when($this->filter === 'pending', fn ($q) => $q->awaitingReply(auth()->id()))
            ->when($this->filter === 'skipped', fn ($q) => $q->where('status', 'skipped'))
            ->when($this->filter === 'hard', fn ($q) => $q->where('rpe', '>=', self::HARD_RPE))
            ->latest('completed_at')
            ->latest('id')
            ->paginate(15);
    }

    #[Computed]
    public function students()
    {
        return auth()->user()->students()->orderBy('name')->get(['users.id', 'users.name']);
    }

    public function reply(int $logId, ?string $quick = null): void
    {
        $log = $this->findLog($logId);

        $body = trim($quick ?? ($this->drafts[$logId] ?? ''));

        $this->validateOnly("drafts.$logId", ["drafts.$logId" => ['nullable', 'string', 'max:1000']], [], ["drafts.$logId" => 'resposta']);

        if ($body === '') {
            $this->addError("drafts.$logId", 'Escreva uma resposta.');

            return;
        }

        $reply = LogReply::create(['session_log_id' => $log->id, 'user_id' => auth()->id(), 'body' => $body]);
        $log->student->notify(new TrainerReplied($reply->load('author', 'log.session')));
        $log->update(['seen_by_trainer_at' => $log->seen_by_trainer_at ?? now()]);

        unset($this->drafts[$logId], $this->logs, $this->counts);
    }

    public function markSeen(int $logId): void
    {
        $this->findLog($logId)->update(['seen_by_trainer_at' => now()]);
        unset($this->logs);
    }

    public function markAllSeen(): void
    {
        $this->baseQuery()->unseen()->update(['seen_by_trainer_at' => now()]);
        unset($this->logs);
    }

    private function findLog(int $id): SessionLog
    {
        return SessionLog::forTrainer(auth()->id())->findOrFail($id);
    }

    public function render()
    {
        return view('livewire.trainer.logs', [
            'quickReplies' => self::QUICK_REPLIES,
            'hardRpe' => self::HARD_RPE,
        ]);
    }
}
