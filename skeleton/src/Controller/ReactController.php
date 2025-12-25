<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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
        string $planilhaId, 
        int $dia): Response
    {
        $dadosPlanilhaBrutos = $googleSheetsService->getSheetData($planilhaId, "A2:AI100");
        $bombeiros = $conversorPlanilhasBombeiro->convertePlanilhaParaObjetosDeBombeiros($dadosPlanilhaBrutos);

        // Exibe o total de bombeiros
        echo "<h3>Total de bombeiros: " . count($bombeiros) . "</h3>";

        // Adicionar os bombeiros ao serviço
        $servico = new CalculadorDePontos($calculadorDeAntiguidade);
        foreach ($bombeiros as $bombeiro) {
            $servico->adicionarBombeiro($bombeiro);
        }
        
        $servico->computarPontuacaoBombeiros(true);

        echo "<h3>Turnos do dia " . $dia . "</h3>";
        $servico->print_turnos_do_mes($dia);

        $turnosParaMes = $servico->distribuirTurnosParaMes();
        $turnosParaDia = $turnosParaMes[$dia];

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
            if ($bombeiro->getDiasAdquiridos() == 0) {
                echo $bombeiro->getNome() . '<br>';
            }
        }

        echo "<h3>Serviços recebidos por BC</h3>";

        foreach ($calculadorDePontos->ordenaBombeirosPorPercentualDeServicosAceitos($bombeiros) as $bombeiro) {
            echo $bombeiro->getNome() . ' - ' . 
            $bombeiro->getDiasAdquiridos() . ' de ' . $bombeiro->getDiasSolicitados() . ' dias - ' . 
            round($bombeiro->getPercentualDeServicosAceitos()) . '%<br>';
            
        }
        return new Response("");
    }
} 