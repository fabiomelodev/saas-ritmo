<?php

namespace App\Livewire\Student;

use App\Livewire\Concerns\LoadsStudentSession;
use App\Models\PlanSession;
use App\Models\SessionLog;
use App\Models\WorkoutSheet;
use App\Notifications\StudentMessaged;
use App\Notifications\TrainingLogged;
use App\Support\Effort;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Execução de uma sessão pelo aluno.
 * - Corrida: registrar distância, tempo, PSE e comentário (ou "não fiz")
 * - Força: checklist da ficha, com acesso ao vídeo/fotos de cada exercício
 */
#[Layout('layouts.app', ['hideNav' => true])]
#[Title('Treino')]
class Session extends Component
{
    use LoadsStudentSession;

    #[Locked]
    public int $sessionId;

    public string $distance = '';

    public string $duration = '';

    public ?int $rpe = null;

    public string $comment = '';

    /** Mensagem para o treinador depois do registro */
    public string $chat = '';

    /** @var array<int, int> ids dos exercícios (block_exercises) marcados */
    public array $checked = [];

    public function mount(PlanSession $session): void
    {
        $session = $this->loadStudentSession($session->id);
        $this->sessionId = $session->id;

        if ($session->isRest()) {
            $this->redirectRoute('student.plan', navigate: true);

            return;
        }

        if ($log = $session->log) {
            $this->distance = $log->distance_km ? str_replace('.', ',', (string) $log->distance_km) : '';
            $this->duration = (string) Effort::formatDuration($log->duration_seconds);
            $this->rpe = $log->rpe;
            $this->comment = (string) $log->comment;
            $this->checked = array_map('intval', $log->checked_items ?? []);
        } elseif ($session->distance_km) {
            // Já sugere a distância planejada
            $this->distance = str_replace('.', ',', (string) $session->distance_km);
        }
    }

    #[Computed]
    public function session(): PlanSession
    {
        return $this->loadStudentSession($this->sessionId);
    }

    #[Computed]
    public function sheet(): ?WorkoutSheet
    {
        return $this->session->workoutSheet?->load('blocks.items.exercise.images');
    }

    /** Ritmo e velocidade calculados enquanto o aluno digita */
    #[Computed]
    public function pace(): ?array
    {
        return Effort::paceAndSpeed(Effort::parseKm($this->distance), Effort::parseDuration($this->duration));
    }

    #[Computed]
    public function itemIds(): array
    {
        return $this->sheet?->blocks->flatMap->items->pluck('id')->all() ?? [];
    }

    /** Marca/desmarca um exercício da ficha e já salva o progresso */
    public function toggle(int $itemId): void
    {
        abort_unless(in_array($itemId, $this->itemIds, true), 404);
        $this->ensureCanLog();

        $this->checked = in_array($itemId, $this->checked, true)
            ? array_values(array_diff($this->checked, [$itemId]))
            : [...$this->checked, $itemId];

        $log = $this->session->log;

        SessionLog::updateOrCreate(
            ['plan_session_id' => $this->sessionId],
            [
                'student_id' => auth()->id(),
                'status' => $log?->status === 'done' ? 'done' : 'in_progress',
                'checked_items' => $this->checked,
            ],
        );

        unset($this->session);
    }

    protected function rules(): array
    {
        $isRun = $this->session->type->value === 'run';

        return [
            'distance' => [$isRun ? 'required' : 'nullable', 'regex:/^\d{1,3}([.,]\d{1,2})?$/'],
            'duration' => [$isRun ? 'required' : 'nullable', 'regex:/^\d{1,3}(:\d{1,2}){0,2}$/'],
            'rpe' => [$isRun ? 'required' : 'nullable', 'integer', 'between:1,10'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ];
    }

    protected function messages(): array
    {
        return [
            'distance.regex' => 'Use só números, ex.: 5 ou 5,2.',
            'duration.regex' => 'Use minutos:segundos (43:00) ou horas:minutos:segundos (1:15:30).',
            'rpe.required' => 'Escolha de 1 a 10 o quanto o treino foi puxado.',
        ];
    }

    protected function validationAttributes(): array
    {
        return ['distance' => 'distância', 'duration' => 'tempo', 'rpe' => 'esforço', 'comment' => 'comentário'];
    }

    /** Concluir: corrida (com tempo/distância) ou ficha de força */
    public function complete(): void
    {
        $this->ensureCanLog();
        $this->validate();

        $isRun = $this->session->type->value === 'run';
        $wasFinal = $this->isFinal();

        $log = SessionLog::updateOrCreate(
            ['plan_session_id' => $this->sessionId],
            [
                'student_id' => auth()->id(),
                'status' => 'done',
                'distance_km' => $isRun ? Effort::parseKm($this->distance) : null,
                'duration_seconds' => $isRun ? Effort::parseDuration($this->duration) : null,
                'rpe' => $this->rpe,
                'comment' => $this->comment ?: null,
                'checked_items' => $isRun ? null : $this->checked,
                'completed_at' => now(),
                'seen_by_trainer_at' => null, // treinador vê como novo
            ],
        );

        if (! $wasFinal) {
            $this->notifyTrainer($log);
        }

        session()->flash('saved', 'Treino registrado. Seu treinador já pode ver.');
        $this->redirectRoute('student.home', navigate: true);
    }

    /** "Não fiz": registra o motivo para o treinador */
    public function skip(): void
    {
        $this->ensureCanLog();
        $this->validateOnly('comment');

        $wasFinal = $this->isFinal();

        $log = SessionLog::updateOrCreate(
            ['plan_session_id' => $this->sessionId],
            [
                'student_id' => auth()->id(),
                'status' => 'skipped',
                'distance_km' => null,
                'duration_seconds' => null,
                'rpe' => null,
                'comment' => $this->comment ?: null,
                'completed_at' => now(),
                'seen_by_trainer_at' => null,
            ],
        );

        if (! $wasFinal) {
            $this->notifyTrainer($log);
        }

        session()->flash('saved', 'Tudo bem! Avisamos seu treinador.');
        $this->redirectRoute('student.home', navigate: true);
    }

    /** Conversa sobre o treino (comentário + respostas) */
    #[Computed]
    public function replies()
    {
        return $this->session->log?->replies()->with('author')->get() ?? collect();
    }

    public function sendMessage(): void
    {
        $log = $this->session->log;
        abort_unless($log && in_array($log->status, ['done', 'skipped'], true), 403);

        $this->validate(['chat' => ['required', 'string', 'max:1000']], [], ['chat' => 'mensagem']);

        $reply = $log->replies()->create(['user_id' => auth()->id(), 'body' => trim($this->chat)]);
        $log->session->week->plan->trainer?->notify(new StudentMessaged($reply->load('author', 'log.session')));
        $log->update(['seen_by_trainer_at' => null]); // volta como novo para o treinador

        $this->reset('chat');
        unset($this->replies, $this->session);
    }

    private function isFinal(): bool
    {
        return in_array($this->session->log?->status, ['done', 'skipped'], true);
    }

    /** Avisa o treinador só na primeira vez (ajustes depois não geram novo aviso) */
    private function notifyTrainer(SessionLog $log): void
    {
        $this->session->week->plan->trainer?->notify(new TrainingLogged($log->load('student', 'session')));
    }

    private function ensureCanLog(): void
    {
        abort_unless($this->session->canBeLogged(), 403, 'Este treino ainda não chegou.');
    }

    public function render()
    {
        return view('livewire.student.session', [
            'effortLabels' => Effort::LABELS,
        ])->title($this->session->title);
    }
}
