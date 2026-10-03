<?php

namespace App\Controller;

use App\Constants\CbmscConstants;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\CalculadorDeAntiguidade;
use App\Service\CalculadorDePontos;
use App\Service\ConversorPlanilhasBombeiro;
use App\Service\GoogleSheetsService;
class ReactController extends AbstractController
{
    #[Route('/react', name: 'app_react')]
    public function index(): Response
    {
        return $this->render('index.html.twig');
    }

    #[Route('/consulta_escala_por_dia/{planilhaId}/{dia}', name: 'consulta_escala_por_dia')]
    public function consultaEscalaPorDia(
        GoogleSheetsService $googleSheetsService, 
        ConversorPlanilhasBombeiro $conversorPlanilhasBombeiro,
        CalculadorDeAntiguidade $calculadorDeAntiguidade,
        CalculadorDePontos $calculadorDePontos,
        Request $request,
        string $planilhaId, 
        int $dia): Response
    {
        $withCache = $request->query->get('withCache', false);
        $diasSelecionados = $request->query->get('diasSelecionados', null);
        $cotasPorDiaFloat = floatval($request->query->get('cotasPorDia', 2.5));

        if ($withCache && file_exists('/tmp/dadosPlanilhaBrutos.json')) {
            $dadosPlanilhaBrutos = json_decode(file_get_contents('/tmp/dadosPlanilhaBrutos.json'), true);
        } else {
            $dadosPlanilhaBrutos = $googleSheetsService->getSheetData($planilhaId, CbmscConstants::PLANILHA_HORARIOS_COLUNA_DATA_INITIAL . ":" . CbmscConstants::PLANILHA_HORARIOS_COLUNA_DATA_FINAL);
            if ($withCache) {
                file_put_contents('/tmp/dadosPlanilhaBrutos.json', json_encode($dadosPlanilhaBrutos));
            }
        }

        $bombeiros = $conversorPlanilhasBombeiro->convertePlanilhaParaObjetosDeBombeiros($dadosPlanilhaBrutos);

        // Exibe o total de bombeiros
        echo "<h3>Total de bombeiros: " . count($bombeiros) . "</h3>";

        // Exibe as regras de distribuição de turnos
        echo "<h3>Regras de distribuição de turnos</h3>";
        echo "<ul>";
        echo "<li>2.5 Quotas ou 60 horas por dia. Opções possíveis:</li>";
            echo "<ul>";
                echo "<li>2 integral + 1 meia cota (24h * 2 + 12h = 60h)</li>";
                echo "<li>1 integral + 3 meias cotas (24h + 12h * 3 = 60h)</li>";
                echo "<li>5 meias cotas (12h * 5 = 60h)</li>";
            echo "</ul>";
        
        echo "<li>Equilibramos a distribuição de turnos para o diurno e noturno ter quantidade de cotas similares</li>";
        echo "<li>BCs que possuem carteira de ambulância recebem prioridade nos dias que precisamos de motorista adicional</li>";
        echo "<li>Dias que precisam de motorista adicional são processados primeiro para garantir que se houver motorista disponível, esse seja alocado</li>";
        echo "<li>BCs que possuem mais antiguidade recebem prioridade</li>";
        echo "<li>BCs efetivos de Videira recebem prioridade sobre outras cidades</li>";
        echo "<li>Cada BC pode adquirir no máximo " . CbmscConstants::COTAS_INTEGRAIS_POR_MES . " turnos integrais por mês</li>";
        echo "<li>Todos os BCs que solicitaram serviço, recebem pelo menos 1 turno por mês</li>";
        echo "<li>Garante que se houver um BC com carteira disponível no dia que precisamos de motorista adicional, esse seja alocado mesmo que ele já tenha atingido o limite de turnos integrais (falta implementar)</li>";
        echo "<li>Priorizamos ordem de BC e convertemos cota integral em meia cota se necessário (falta implementar)</li>";
        echo "<li>Se tivermos apenas 3 cotas integrais disponíveis, convertemos uma delas em meia cota se necessário para manter o limite de 2.5 cotas por dia (falta implementar)</li>";


        echo "</ul>";

        // Adicionar os bombeiros ao serviço
        $servico = new CalculadorDePontos($calculadorDeAntiguidade);
        foreach ($bombeiros as $bombeiro) {
            $servico->adicionarBombeiro($bombeiro);
        }
        
        $servico->computarPontuacaoBombeiros(true);
        // $diasSelecionados virá como numerico separado por virgula
        $diasSelecionadosArray = explode(',', $diasSelecionados);

        $horasPorDia = $cotasPorDiaFloat * 24;
        $turnosParaMes = $servico->distribuirTurnosParaMes($horasPorDia , $diasSelecionadosArray);
        $turnosParaDia = $turnosParaMes[$dia];

        echo "<h3>Turnos do dia " . $dia . "</h3>";
        $servico->print_turnos_do_mes($dia, $turnosParaMes);

        // Exibir dos turnos aprovados
        echo "<h3>Turnos aprovados</h3>";
        
        foreach ($turnosParaDia as $key => $conflito) {
            echo '<strong>' . $key . '</strong><br>';
            foreach ($conflito as $bombeiro) {
                echo $bombeiro->getNome() . ' - Pontuação: ' . $bombeiro->getPontuacao() . '<br>';
            }
            echo "<br>";
        }

        echo "<h3>BCs sem horário</h3>";
        foreach ($bombeiros as $bombeiro) {
            if (count($bombeiro->getTurnosAdquiridos()) == 0) {
                echo $bombeiro->getNome() . '<br>';
            }
        }

        echo "<h3 style='padding-top: 20px; clear: both;'>Serviços recebidos por BC</h3>";

        foreach ($calculadorDePontos->ordenaBombeirosPorPercentualDeServicosAceitos($bombeiros) as $bombeiro) {
            echo $bombeiro->getNome() . ' - ' . 
            count($bombeiro->getTurnosAdquiridos()) . ' de ' . $bombeiro->getDiasSolicitados() . ' dias - ' . 
            round($bombeiro->getPercentualDeServicosAceitos()) . '% - Dias adquiridos: ' . count($bombeiro->getTurnosAdquiridos()) . '<br><small>(';
            foreach ($bombeiro->getTurnosAdquiridos() as $turno) {
                echo $turno->getDia() . ' - ' . $turno->getTurno() . ' | ';
            }
            echo ')</small><br><br>';
        }
        return new Response("");
    }
} 