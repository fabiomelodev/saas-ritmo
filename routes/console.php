<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
| Lembretes de treino (horário de Brasília).
| Em produção, rode o agendador a cada minuto: * * * * * php artisan schedule:run
| No Laragon/desenvolvimento: php artisan schedule:work
*/
Schedule::command('ritmo:lembretes hoje')->dailyAt('07:00')->timezone('America/Sao_Paulo');
Schedule::command('ritmo:lembretes pendentes')->dailyAt('20:00')->timezone('America/Sao_Paulo');

/*
| Fila (envio dos pushes) sem processo rodando o tempo todo — funciona em hospedagem
| compartilhada: a cada minuto processa o que houver e encerra.
*/
Schedule::command('queue:work --stop-when-empty --max-time=55 --tries=3')
    ->everyMinute()
    ->withoutOverlapping(5);
