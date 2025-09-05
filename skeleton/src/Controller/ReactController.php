<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Servico;
use App\Service\GoogleSheetsService;
use App\Service\WriteSheetsService;
class ReactController extends AbstractController
{
    #[Route('/react', name: 'app_react')]
    public function index(): Response
    {
        return $this->render('index.html.twig');
    }

    #[Route('/consulta_escala_por_dia/{planilhaId}/{dia}', name: 'consulta_escala_por_dia')]
    public function consultaEscalaPorDia(GoogleSheetsService $googleSheetsService, WriteSheetsService $writeSheetsService, string $planilhaId, int $dia): Response
    {
        $dadosPlanilhaBrutos = $googleSheetsService->getSheetData($planilhaId, "A1:C100");
        $bombeiros = $writeSheetsService->convertePlanilhaParaObjetosDeBombeiros($dadosPlanilhaBrutos);

        // Exibe o total de bombeiros
        echo "<h3>Total de bombeiros: " . count($bombeiros) . "</h3>";

        // Adicionar os bombeiros ao serviço
        $servico = new Servico();
        foreach ($bombeiros as $bombeiro) {
            $servico->adicionarBombeiro($bombeiro);
        }

        // Computar os turnos
        $servico->computarTurnos();

        echo "<h3>Turnos do dia " . $dia . "</h3>";
        $servico->print_turnos_do_mes($dia);

        // Resolver os conflitos
        // $servico->resolverConflitos();

        // Exibir os conflitos
        echo "<h3>Conflitos</h3>";
        foreach ($servico->getConflitos() as $conflito) {
            echo "Dia: {$conflito['dia']}, Turno: {$conflito['turno']}";
            echo "<br>";
        }

        return new Response("");
    }
} 