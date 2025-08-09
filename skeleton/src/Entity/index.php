
<?php

    require_once 'skeleton/src/Entity/classServico.php';
    require_once 'skeleton/src/Entity/classBombeiro.php';

    $bombeiro = [];

    $bombeiro[0] = new Bombeiro("João", "12345678901", "999999999", 3, true, "Videira");
    $bombeiro[1] = new Bombeiro("Maria", "23456789012", "888888888", 5, false, "Fraiburgo");
    $bombeiro[2] = new Bombeiro("Pedro", "34567890123", "777777777", 1, true, "Caçador");
    $bombeiro[3] = new Bombeiro("Ana", "45678901234", "666666666", 7, false, "Curitibanos");

    $bombeiro[0]->adicionarDisponibilidadeServico("Janeiro", 1, "manhã");
    $bombeiro[1]->adicionarDisponibilidadeServico("Janeiro", 1, "manhã");
?>
