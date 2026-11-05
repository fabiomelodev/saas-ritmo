<?php

namespace App\Http\Controllers;

use App\Models\SessionLog;
use App\Support\Effort;
use Illuminate\Http\Response;

/** LGPD: "Baixar uma cópia dos meus dados" (arquivo JSON) */
class DataExportController extends Controller
{
    public function __invoke(): Response
    {
        $user = auth()->user();

        $data = [
            'exportado_em' => now()->toIso8601String(),
            'conta' => [
                'papel' => $user->role->label(),
                'nome' => $user->name,
                'email' => $user->email,
                'whatsapp' => $user->whatsapp,
                'data_de_nascimento' => $user->birth_date?->toDateString(),
                'sexo' => $user->sex?->label(),
                'termos_aceitos_em' => $user->terms_accepted_at?->toIso8601String(),
                'criada_em' => $user->created_at?->toIso8601String(),
            ],
        ];

        if ($user->isTrainer()) {
            $profile = $user->trainerProfile;
            $data['perfil_profissional'] = $profile?->only(['cref', 'city', 'state', 'bio']);
            $data['alunos'] = $user->students()->get()->map(fn ($s) => [
                'nome' => $s->name,
                'status' => $s->pivot->status,
                'objetivo' => $s->pivot->goal,
                'nivel' => $s->pivot->level,
                'anotacoes' => $s->pivot->private_notes,
            ]);
            $data['fichas'] = $user->workoutSheets()->with('blocks.items.exercise')->get()->map(fn ($sheet) => [
                'nome' => $sheet->name,
                'blocos' => $sheet->blocks->map(fn ($b) => [
                    'secao' => $b->section,
                    'series' => $b->sets,
                    'exercicios' => $b->items->map(fn ($i) => ['nome' => $i->exercise?->name, 'prescricao' => $i->prescription, 'obs' => $i->notes]),
                ]),
            ]);
        } else {
            $data['treinadores'] = $user->trainers()->get()->map(fn ($t) => ['nome' => $t->name, 'status' => $t->pivot->status]);
            $data['registros'] = SessionLog::where('student_id', $user->id)->with(['session', 'replies.author'])->get()->map(fn ($log) => [
                'treino' => $log->session?->title,
                'status' => $log->status,
                'distancia_km' => $log->distance_km,
                'tempo' => Effort::formatDuration($log->duration_seconds),
                'pse' => $log->rpe,
                'comentario' => $log->comment,
                'registrado_em' => $log->completed_at?->toIso8601String(),
                'conversa' => $log->replies->map(fn ($r) => ['de' => $r->author?->name, 'mensagem' => $r->body, 'em' => $r->created_at?->toIso8601String()]),
            ]);
            $data['recordes'] = $user->personalRecords()->get()->map(fn ($r) => [
                'prova' => $r->label,
                'tempo' => $r->timeLabel(),
                'data' => $r->achieved_on?->toDateString(),
            ]);
        }

        $filename = 'meus-dados-'.str(config('app.name'))->slug().'-'.now()->format('Y-m-d').'.json';

        return response(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), 200, [
            'Content-Type' => 'application/json; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }
}
