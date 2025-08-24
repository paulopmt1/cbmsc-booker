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
        $bombeiro1 = new Bombeiro("João", "12345678901", "999999999", 3, true, "Videira");
        $bombeiro1->adicionarDisponibilidade(new Disponibilidade(1, 8, "NOTURNO"));
        $bombeiro1->adicionarDisponibilidade(new Disponibilidade(2, 8, "NOTURNO"));
        $bombeiro1->adicionarDisponibilidade(new Disponibilidade(4, 8, "DIURNO"));
        $bombeiro1->adicionarDisponibilidade(new Disponibilidade(5, 8, "DIURNO"));
        $bombeiro1->adicionarDisponibilidade(new Disponibilidade(6, 8, "INTEGRAL"));
        $bombeiro1->print_disponibilidade();

        return new Response("Teste de instâncias do objeto");
    }
} 