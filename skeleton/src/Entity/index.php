<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teste</title>
</head>
<body>
    <h1>Bem-vindos</h1>
    <pre>
        <?php

        require_once 'skeleton/src/Entity/classServico.php';
        require_once 'skeleton/src/Entity/classBombeiro.php';

        $bombeiro = [];

        $bombeiro[0] = new Bombeiro("João", "12345678901", "999999999", 3, true, "Videira");
        $bombeiro[1] = new Bombeiro("Maria", "23456789012", "888888888", 5, false, "Fraiburgo");
        $bombeiro[2] = new Bombeiro("Pedro", "34567890123", "777777777", 1, true, "Caçador");
        $bombeiro[3] = new Bombeiro("Ana", "45678901234", "666666666", 7, false, "Curitibanos");

        $diaDeServico = new Servico();

        $diaDeServico->escolherDiaServico($bombeiro[0]->getNome(), 15, "Janeiro", "manhã");
        $diaDeServico->escolherDiaServico($bombeiro[1]->getNome(), 15, "Janeiro", "tarde");
        $diaDeServico->escolherDiaServico($bombeiro[2]->getNome(), 15, "Janeiro", "noite");
        $diaDeServico->escolherDiaServico($bombeiro[3]->getNome(), 15, "Janeiro", "integral");
        

        ?>
    </pre>
</body>
</html>
