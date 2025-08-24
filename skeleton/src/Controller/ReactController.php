<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Bombeiro;

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
        $bombeiro1->adicionarDisponibilidadeServico("1", "1", "NOTURNO");
        $bombeiro1->adicionarDisponibilidadeServico("1", "2", "NOTURNO");
        $bombeiro1->adicionarDisponibilidadeServico("1", "4", "DIURNO");
        $bombeiro1->adicionarDisponibilidadeServico("1", "5", "DIURNO");
        $bombeiro1->adicionarDisponibilidadeServico("1", "6", "DIURNO");
        
        return $this->json(['message' => 'Teste de instâncias do objeto', 'bombeiro' => $bombeiro1]);
    }
} 