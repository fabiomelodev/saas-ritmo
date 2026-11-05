<?php

namespace App\Livewire\Concerns;

use Illuminate\Database\Eloquent\Model;

trait ChecksTrainerOwnership
{
    /** Um treinador só mexe nos próprios planos, fichas e exercícios */
    protected function ensureOwns(Model $model, string $column = 'trainer_id'): void
    {
        abort_unless((int) $model->{$column} === (int) auth()->id(), 403);
    }
}
