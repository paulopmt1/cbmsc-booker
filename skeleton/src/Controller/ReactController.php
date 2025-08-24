<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Bombeiro;
use App\Entity\Disponibilidade;
use App\Entity\Servico;

class ReactController extends AbstractController
{
    #[Route('/react', name: 'app_react')]
    public function index(): Response
    {
        return $this->render('index.html.twig');
    }

    #[Route('/posttest')]
    public function poooTest(): Response
    {
        // Bombeiro 1 - João (Videira, com carteira de ambulância, 3 anos experiência)
        $bombeiro1 = new Bombeiro("João", "12345678901", 3, true, "Videira");
        $bombeiro1->adicionarDisponibilidade(new Disponibilidade(5, 8, "DIURNO"));    // Conflito com Maria
        $bombeiro1->adicionarDisponibilidade(new Disponibilidade(10, 8, "NOTURNO"));  // Conflito com Pedro
        $bombeiro1->adicionarDisponibilidade(new Disponibilidade(15, 8, "INTEGRAL")); // Conflito com ambos

        // Bombeiro 2 - Maria (Fraiburgo, sem carteira de ambulância, 7 anos experiência)
        $bombeiro2 = new Bombeiro("Maria Silva", "98765432100", 7, false, "Fraiburgo");
        $bombeiro2->adicionarDisponibilidade(new Disponibilidade(5, 8, "DIURNO"));    // Conflito com João
        $bombeiro2->adicionarDisponibilidade(new Disponibilidade(12, 8, "NOTURNO"));  // Conflito com Pedro
        $bombeiro2->adicionarDisponibilidade(new Disponibilidade(15, 8, "INTEGRAL")); // Conflito com ambos

        // Bombeiro 3 - Pedro (Caçador, com carteira de ambulância, 1 ano experiência)
        $bombeiro3 = new Bombeiro("Pedro Santos", "11122233344", 1, true, "Caçador");
        $bombeiro3->adicionarDisponibilidade(new Disponibilidade(10, 8, "NOTURNO"));  // Conflito com João
        $bombeiro3->adicionarDisponibilidade(new Disponibilidade(12, 8, "NOTURNO"));  // Conflito com Maria
        $bombeiro3->adicionarDisponibilidade(new Disponibilidade(15, 8, "INTEGRAL")); // Conflito com ambos

        // Exibir disponibilidades de todos os bombeiros
        echo "<h3>Bombeiro 1 - " . $bombeiro1->getNome() . " (" . $bombeiro1->getCidadeOrigem() . ") - " . $bombeiro1->getAntiguidade() . "</h3>";
        $bombeiro1->print_disponibilidade();
        
        echo "<h3>Bombeiro 2 - " . $bombeiro2->getNome() . " (" . $bombeiro2->getCidadeOrigem() . ") - " . $bombeiro2->getAntiguidade() . "</h3>";
        $bombeiro2->print_disponibilidade();
        
        echo "<h3>Bombeiro 3 - " . $bombeiro3->getNome() . " (" . $bombeiro3->getCidadeOrigem() . ") - " . $bombeiro3->getAntiguidade() . "</h3>";
        $bombeiro3->print_disponibilidade();


        // Adicionar os bombeiros ao serviço
        $servico = new Servico();
        $servico->adicionarBombeiro($bombeiro1);
        $servico->adicionarBombeiro($bombeiro2);
        $servico->adicionarBombeiro($bombeiro3);

        // Computar os turnos
        $servico->computarTurnos();

        echo "<h3>Turnos do mês</h3>";
        $servico->print_turnos_do_mes(5);

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