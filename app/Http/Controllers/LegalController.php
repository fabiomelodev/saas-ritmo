<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

/**
 * Termos de uso e Política de privacidade (rascunhos para revisão jurídica).
 * Edite os textos aqui; a página é renderizada por resources/views/legal/page.blade.php.
 */
class LegalController extends Controller
{
    private const UPDATED = '5 de novembro de 2026';

    public function terms(): View
    {
        $app = config('app.name');

        return view('legal.page', [
            'title' => 'Termos de uso',
            'updated' => self::UPDATED,
            'sections' => [
                'O que é o '.$app => "O {$app} é uma plataforma em que treinadores montam planos de corrida e de força e acompanham os treinos registrados pelos seus alunos.",
                'Contas' => [
                    'O treinador cria a própria conta. O aluno se cadastra pelo link de convite enviado pelo treinador e fica vinculado a ele.',
                    'Você é responsável pelos dados informados e por manter sua senha em segurança.',
                ],
                'Responsabilidade pelos treinos' => [
                    'Os planos são elaborados pelo treinador, que responde pela orientação técnica.',
                    'Procure avaliação médica antes de iniciar atividades físicas e interrompa o treino em caso de dor ou mal-estar.',
                ],
                'Uso adequado' => 'Não use a plataforma para fins ilegais, para enviar conteúdo ofensivo ou para acessar dados de outras pessoas sem autorização.',
                'Encerramento' => 'Você pode excluir sua conta a qualquer momento em Conta › Privacidade e termos. O treinador pode arquivar o vínculo com um aluno.',
                'Contato' => '[Inclua aqui o e-mail de contato do responsável pela plataforma.]',
            ],
        ]);
    }

    public function privacy(): View
    {
        return view('legal.page', [
            'title' => 'Política de privacidade',
            'updated' => self::UPDATED,
            'sections' => [
                'Dados que coletamos' => [
                    'Cadastro: nome, e-mail, WhatsApp e foto (opcional).',
                    'Perfil para o treino: data de nascimento e sexo, usados pelo treinador para personalizar o plano.',
                    'Treinos: distância, tempo, ritmo, esforço percebido (PSE), comentários e recordes.',
                    'Treinador: CREF, cidade e apresentação, exibidos aos seus alunos.',
                ],
                'Para que usamos' => 'Exclusivamente para montar, executar e acompanhar os treinos entre aluno e treinador. Não vendemos dados nem os usamos para publicidade.',
                'Quem vê seus dados' => 'Os dados do aluno são visíveis apenas para ele e para o treinador vinculado. As anotações privadas do treinador não são exibidas ao aluno.',
                'Seus direitos (LGPD)' => [
                    'Você pode acessar, corrigir e baixar seus dados e excluir sua conta em Conta › Privacidade e termos.',
                    'A exclusão apaga a conta e os registros de treino de forma definitiva.',
                ],
                'Segurança' => 'As senhas são armazenadas com criptografia e o acesso é feito por conexão segura (HTTPS).',
                'Encarregado (DPO)' => '[Inclua aqui o nome e o e-mail do encarregado pelo tratamento de dados.]',
            ],
        ]);
    }
}
