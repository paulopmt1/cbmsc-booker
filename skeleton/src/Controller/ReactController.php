<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Bombeiro;
use App\Entity\Disponibilidade;

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

        return new Response("Teste de instâncias de múltiplos bombeiros concluído!");
    }
} 