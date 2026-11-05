<?php

namespace App\Support;

use Carbon\CarbonInterface;

/** Rótulos de período para semanas e planos (sempre com as datas reais do calendário) */
class WeekRange
{
    /** "09–15/nov" ou, virando o mês, "30/nov–06/dez" */
    public static function short(CarbonInterface $start, CarbonInterface $end): string
    {
        $month = fn (CarbonInterface $date) => mb_strtolower(rtrim($date->translatedFormat('M'), '.'));

        return $start->month === $end->month
            ? $start->format('d').'–'.$end->format('d').'/'.$month($end)
            : $start->format('d').'/'.$month($start).'–'.$end->format('d').'/'.$month($end);
    }

    /** "09/11 a 06/12" (com o ano quando o período vira o ano) */
    public static function period(CarbonInterface $start, CarbonInterface $end): string
    {
        return $start->year === $end->year
            ? $start->format('d/m').' a '.$end->format('d/m')
            : $start->format('d/m/Y').' a '.$end->format('d/m/Y');
    }
}
