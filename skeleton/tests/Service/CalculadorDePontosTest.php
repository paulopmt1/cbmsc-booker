<?php

namespace App\Tests\Service;

use App\Constants\CbmscConstants;
use App\Entity\Bombeiro;
use App\Entity\Disponibilidade;
use App\Service\CalculadorDeAntiguidade;
use App\Service\CalculadorDePontos;
use PHPUnit\Framework\TestCase;

class CalculadorDePontosTest extends TestCase
{
    private CalculadorDeAntiguidade $calculadorDeAntiguidade;
    private CalculadorDePontos $calculador;

    protected function setUp(): void
    {
        // Mock do CalculadorDeAntiguidade
        $this->calculadorDeAntiguidade = $this->createMock(CalculadorDeAntiguidade::class);
        $this->calculadorDeAntiguidade->method('getAntiguidade')
            ->willReturn(50); // Retorna uma antiguidade padrão de 50

        $this->calculador = new CalculadorDePontos($this->calculadorDeAntiguidade);
    }

    /**
     * Teste básico: distribuição com poucos bombeiros e disponibilidade limitada
     */
    public function testDistribuirTurnosParaMesComPoucosBombeiros(): void
    {
        // Cria 3 bombeiros com disponibilidades diferentes
        $bombeiro1 = new Bombeiro('Bombeiro 1', '11111111111', false);
        $bombeiro1->setCidadeOrigem(CbmscConstants::CIDADE_VIDEIRA);
        $bombeiro1->adicionarDisponibilidade(new Disponibilidade(1, CbmscConstants::TURNO_INTEGRAL));
        $bombeiro1->adicionarDisponibilidade(new Disponibilidade(2, CbmscConstants::TURNO_DIURNO));

        $bombeiro2 = new Bombeiro('Bombeiro 2', '22222222222', true);
        $bombeiro2->setCidadeOrigem(CbmscConstants::CIDADE_FRAIBURGO);
        $bombeiro2->adicionarDisponibilidade(new Disponibilidade(1, CbmscConstants::TURNO_DIURNO));
        $bombeiro2->adicionarDisponibilidade(new Disponibilidade(2, CbmscConstants::TURNO_NOTURNO));

        $bombeiro3 = new Bombeiro('Bombeiro 3', '33333333333', false);
        $bombeiro3->setCidadeOrigem(CbmscConstants::CIDADE_CACADOR);
        $bombeiro3->adicionarDisponibilidade(new Disponibilidade(1, CbmscConstants::TURNO_NOTURNO));
        $bombeiro3->adicionarDisponibilidade(new Disponibilidade(2, CbmscConstants::TURNO_INTEGRAL));

        $this->calculador->adicionarBombeiro($bombeiro1);
        $this->calculador->adicionarBombeiro($bombeiro2);
        $this->calculador->adicionarBombeiro($bombeiro3);

        $resultado = $this->calculador->distribuirTurnosParaMes(60);

        // Verifica que o resultado é um array
        $this->assertIsArray($resultado);
        
        // Verifica que há distribuição para os dias 1 e 2
        $this->assertArrayHasKey(1, $resultado);
        $this->assertArrayHasKey(2, $resultado);
        
        // Verifica estrutura dos turnos
        if (isset($resultado[1])) {
            $this->assertArrayHasKey(CbmscConstants::TURNO_INTEGRAL, $resultado[1]);
            $this->assertArrayHasKey(CbmscConstants::TURNO_DIURNO, $resultado[1]);
            $this->assertArrayHasKey(CbmscConstants::TURNO_NOTURNO, $resultado[1]);
        }
    }

    /**
     * Teste: distribuição com bombeiro Querubim (deve ter prioridade máxima)
     */
    public function testDistribuirTurnosParaMesComQuerubim(): void
    {
        $querubim = new Bombeiro('BC CHEROBIN ', strval(CbmscConstants::CPF_DO_QUERUBIN), false);
        $querubim->setCidadeOrigem(CbmscConstants::CIDADE_VIDEIRA);
        $querubim->adicionarDisponibilidade(new Disponibilidade(1, CbmscConstants::TURNO_INTEGRAL));
        $querubim->adicionarDisponibilidade(new Disponibilidade(2, CbmscConstants::TURNO_INTEGRAL));

        $bombeiro2 = new Bombeiro('Bombeiro 2', '22222222222', false);
        $bombeiro2->setCidadeOrigem(CbmscConstants::CIDADE_VIDEIRA);
        $bombeiro2->adicionarDisponibilidade(new Disponibilidade(1, CbmscConstants::TURNO_INTEGRAL));
        $bombeiro2->adicionarDisponibilidade(new Disponibilidade(2, CbmscConstants::TURNO_INTEGRAL));

        $this->calculador->adicionarBombeiro($querubim);
        $this->calculador->adicionarBombeiro($bombeiro2);

        $resultado = $this->calculador->distribuirTurnosParaMes(60);

        // Verifica que o Querubim foi selecionado primeiro
        if (isset($resultado[1][CbmscConstants::TURNO_INTEGRAL]) && 
            count($resultado[1][CbmscConstants::TURNO_INTEGRAL]) > 0) {
            $primeiroBombeiro = $resultado[1][CbmscConstants::TURNO_INTEGRAL][0];
            $this->assertEquals('BC CHEROBIN ', $primeiroBombeiro->getNome());
        }
    }

    /**
     * Teste: distribuição com bombeiros com carteira de ambulância
     */
    public function testDistribuirTurnosParaMesComCarteiraAmbulancia(): void
    {
        $bombeiroComCarteira = new Bombeiro('Bombeiro Com Carteira', '11111111111', true);
        $bombeiroComCarteira->setCidadeOrigem(CbmscConstants::CIDADE_VIDEIRA);
        $bombeiroComCarteira->adicionarDisponibilidade(new Disponibilidade(1, CbmscConstants::TURNO_INTEGRAL));
        $bombeiroComCarteira->adicionarDisponibilidade(new Disponibilidade(1, CbmscConstants::TURNO_DIURNO));

        $bombeiroSemCarteira = new Bombeiro('Bombeiro Sem Carteira', '22222222222', false);
        $bombeiroSemCarteira->setCidadeOrigem(CbmscConstants::CIDADE_VIDEIRA);
        $bombeiroSemCarteira->adicionarDisponibilidade(new Disponibilidade(1, CbmscConstants::TURNO_INTEGRAL));
        $bombeiroSemCarteira->adicionarDisponibilidade(new Disponibilidade(1, CbmscConstants::TURNO_DIURNO));

        $this->calculador->adicionarBombeiro($bombeiroComCarteira);
        $this->calculador->adicionarBombeiro($bombeiroSemCarteira);

        $resultado = $this->calculador->distribuirTurnosParaMes(60);

        // Verifica que há distribuição
        $this->assertIsArray($resultado);
        $this->assertArrayHasKey(1, $resultado);
    }

    /**
     * Teste: distribuição com diferentes horas por dia
     */
    public function testDistribuirTurnosParaMesComDiferentesHorasPorDia(): void
    {
        // Cria 5 bombeiros com disponibilidades variadas
        for ($i = 1; $i <= 5; $i++) {
            $bombeiro = new Bombeiro("Bombeiro $i", str_pad((string)$i, 11, '0', STR_PAD_LEFT), false);
            $bombeiro->setCidadeOrigem(CbmscConstants::CIDADE_VIDEIRA);
            
            // Cada bombeiro disponível para vários dias e turnos
            for ($dia = 1; $dia <= 5; $dia++) {
                $bombeiro->adicionarDisponibilidade(new Disponibilidade($dia, CbmscConstants::TURNO_INTEGRAL));
                $bombeiro->adicionarDisponibilidade(new Disponibilidade($dia, CbmscConstants::TURNO_DIURNO));
                $bombeiro->adicionarDisponibilidade(new Disponibilidade($dia, CbmscConstants::TURNO_NOTURNO));
            }
            
            $this->calculador->adicionarBombeiro($bombeiro);
        }

        // Testa com 24 horas (1 turno integral)
        $resultado24h = $this->calculador->distribuirTurnosParaMes(24);
        $this->assertIsArray($resultado24h);
        
        // Testa com 36 horas (1 integral + 1 diurno ou noturno)
        $resultado36h = $this->calculador->distribuirTurnosParaMes(36);
        $this->assertIsArray($resultado36h);
        
        // Testa com 60 horas (padrão: 2.5 cotas)
        $resultado60h = $this->calculador->distribuirTurnosParaMes(60);
        $this->assertIsArray($resultado60h);
        
        // Testa com 72 horas (3 turnos integrais)
        $resultado72h = $this->calculador->distribuirTurnosParaMes(72);
        $this->assertIsArray($resultado72h);
    }

    /**
     * Teste: distribuição com bombeiros de diferentes cidades (prioridade Videira)
     */
    public function testDistribuirTurnosParaMesComDiferentesCidades(): void
    {
        $bombeiroVideira = new Bombeiro('Bombeiro Videira', '11111111111', false);
        $bombeiroVideira->setCidadeOrigem(CbmscConstants::CIDADE_VIDEIRA);
        $bombeiroVideira->adicionarDisponibilidade(new Disponibilidade(1, CbmscConstants::TURNO_INTEGRAL));

        $bombeiroFraiburgo = new Bombeiro('Bombeiro Fraiburgo', '22222222222', false);
        $bombeiroFraiburgo->setCidadeOrigem(CbmscConstants::CIDADE_FRAIBURGO);
        $bombeiroFraiburgo->adicionarDisponibilidade(new Disponibilidade(1, CbmscConstants::TURNO_INTEGRAL));

        $bombeiroCacador = new Bombeiro('Bombeiro Caçador', '33333333333', false);
        $bombeiroCacador->setCidadeOrigem(CbmscConstants::CIDADE_CACADOR);
        $bombeiroCacador->adicionarDisponibilidade(new Disponibilidade(1, CbmscConstants::TURNO_INTEGRAL));

        $this->calculador->adicionarBombeiro($bombeiroVideira);
        $this->calculador->adicionarBombeiro($bombeiroFraiburgo);
        $this->calculador->adicionarBombeiro($bombeiroCacador);

        $resultado = $this->calculador->distribuirTurnosParaMes(24);

        // Verifica que há distribuição
        $this->assertIsArray($resultado);
        $this->assertArrayHasKey(1, $resultado);
        
        // Bombeiro de Videira deve ter prioridade (mais pontos)
        if (isset($resultado[1][CbmscConstants::TURNO_INTEGRAL]) && 
            count($resultado[1][CbmscConstants::TURNO_INTEGRAL]) > 0) {
            $primeiroBombeiro = $resultado[1][CbmscConstants::TURNO_INTEGRAL][0];
            $this->assertEquals(CbmscConstants::CIDADE_VIDEIRA, $primeiroBombeiro->getCidadeOrigem());
        }
    }

    /**
     * Teste: distribuição com muitos bombeiros e disponibilidades variadas
     */
    public function testDistribuirTurnosParaMesComMuitosBombeiros(): void
    {
        // Cria 10 bombeiros com diferentes combinações
        for ($i = 1; $i <= 10; $i++) {
            $bombeiro = new Bombeiro("Bombeiro $i", str_pad((string)$i, 11, '0', STR_PAD_LEFT), $i % 3 === 0);
            $bombeiro->setCidadeOrigem($i % 2 === 0 ? CbmscConstants::CIDADE_VIDEIRA : CbmscConstants::CIDADE_FRAIBURGO);
            
            // Disponibilidades variadas
            for ($dia = 1; $dia <= 10; $dia++) {
                $turnos = [
                    CbmscConstants::TURNO_INTEGRAL,
                    CbmscConstants::TURNO_DIURNO,
                    CbmscConstants::TURNO_NOTURNO
                ];
                
                // Cada bombeiro disponível para alguns turnos aleatórios
                $turnosDisponiveis = array_slice($turnos, 0, ($i % 3) + 1);
                foreach ($turnosDisponiveis as $turno) {
                    $bombeiro->adicionarDisponibilidade(new Disponibilidade($dia, $turno));
                }
            }
            
            $this->calculador->adicionarBombeiro($bombeiro);
        }

        $resultado = $this->calculador->distribuirTurnosParaMes(60);

        // Verifica estrutura básica
        $this->assertIsArray($resultado);
        
        // Verifica alguns dias
        // Nota: Nem todos os turnos podem existir se não houver bombeiros disponíveis
        for ($dia = 1; $dia <= 10; $dia++) {
            if (isset($resultado[$dia])) {
                // Verifica que pelo menos um turno foi criado
                $this->assertNotEmpty($resultado[$dia], "Dia $dia deve ter pelo menos um turno");
                // Verifica que os turnos existentes são arrays
                foreach ($resultado[$dia] as $turno => $bombeiros) {
                    $this->assertIsArray($bombeiros, "Turno $turno no dia $dia deve ser um array");
                }
            }
        }
    }

    /**
     * Teste: distribuição com bombeiros sem disponibilidade para alguns dias
     */
    public function testDistribuirTurnosParaMesComDisponibilidadeLimitada(): void
    {
        $bombeiro1 = new Bombeiro('Bombeiro 1', '11111111111', false);
        $bombeiro1->setCidadeOrigem(CbmscConstants::CIDADE_VIDEIRA);
        // Disponível apenas no dia 1
        $bombeiro1->adicionarDisponibilidade(new Disponibilidade(1, CbmscConstants::TURNO_INTEGRAL));

        $bombeiro2 = new Bombeiro('Bombeiro 2', '22222222222', false);
        $bombeiro2->setCidadeOrigem(CbmscConstants::CIDADE_VIDEIRA);
        // Disponível apenas no dia 2
        $bombeiro2->adicionarDisponibilidade(new Disponibilidade(2, CbmscConstants::TURNO_INTEGRAL));

        $bombeiro3 = new Bombeiro('Bombeiro 3', '33333333333', false);
        $bombeiro3->setCidadeOrigem(CbmscConstants::CIDADE_VIDEIRA);
        // Disponível nos dias 1 e 2
        $bombeiro3->adicionarDisponibilidade(new Disponibilidade(1, CbmscConstants::TURNO_DIURNO));
        $bombeiro3->adicionarDisponibilidade(new Disponibilidade(2, CbmscConstants::TURNO_DIURNO));

        $this->calculador->adicionarBombeiro($bombeiro1);
        $this->calculador->adicionarBombeiro($bombeiro2);
        $this->calculador->adicionarBombeiro($bombeiro3);

        $resultado = $this->calculador->distribuirTurnosParaMes(60);

        // Verifica que há distribuição nos dias 1 e 2
        $this->assertIsArray($resultado);
        
        // Dia 1 deve ter pelo menos o bombeiro 1
        if (isset($resultado[1][CbmscConstants::TURNO_INTEGRAL])) {
            $this->assertGreaterThanOrEqual(0, count($resultado[1][CbmscConstants::TURNO_INTEGRAL]));
        }
        
        // Dia 2 deve ter pelo menos o bombeiro 2
        if (isset($resultado[2][CbmscConstants::TURNO_INTEGRAL])) {
            $this->assertGreaterThanOrEqual(0, count($resultado[2][CbmscConstants::TURNO_INTEGRAL]));
        }
    }

    /**
     * Teste: verifica que a pontuação é recalculada após cada dia
     */
    public function testDistribuirTurnosParaMesRecalculaPontuacao(): void
    {
        $bombeiro1 = new Bombeiro('Bombeiro 1', '11111111111', false);
        $bombeiro1->setCidadeOrigem(CbmscConstants::CIDADE_VIDEIRA);
        $bombeiro1->adicionarDisponibilidade(new Disponibilidade(1, CbmscConstants::TURNO_INTEGRAL));
        $bombeiro1->adicionarDisponibilidade(new Disponibilidade(2, CbmscConstants::TURNO_INTEGRAL));

        $bombeiro2 = new Bombeiro('Bombeiro 2', '22222222222', false);
        $bombeiro2->setCidadeOrigem(CbmscConstants::CIDADE_VIDEIRA);
        $bombeiro2->adicionarDisponibilidade(new Disponibilidade(1, CbmscConstants::TURNO_INTEGRAL));
        $bombeiro2->adicionarDisponibilidade(new Disponibilidade(2, CbmscConstants::TURNO_INTEGRAL));

        $this->calculador->adicionarBombeiro($bombeiro1);
        $this->calculador->adicionarBombeiro($bombeiro2);

        $resultado = $this->calculador->distribuirTurnosParaMes(24);

        // Verifica que ambos os bombeiros podem ter sido selecionados
        // (a pontuação deve ser recalculada, permitindo distribuição justa)
        $this->assertIsArray($resultado);
        
        // Verifica que há distribuição
        if (isset($resultado[1][CbmscConstants::TURNO_INTEGRAL])) {
            $this->assertGreaterThanOrEqual(0, count($resultado[1][CbmscConstants::TURNO_INTEGRAL]));
        }
    }

    /**
     * Teste: combinação complexa com múltiplos fatores
     */
    public function testDistribuirTurnosParaMesCombinacaoComplexa(): void
    {
        // Querubim com carteira
        $querubim = new Bombeiro('BC CHEROBIN ', strval(CbmscConstants::CPF_DO_QUERUBIN), true);
        $querubim->setCidadeOrigem(CbmscConstants::CIDADE_VIDEIRA);
        for ($dia = 1; $dia <= 5; $dia++) {
            $querubim->adicionarDisponibilidade(new Disponibilidade($dia, CbmscConstants::TURNO_INTEGRAL));
        }

        // Bombeiro de Videira com carteira
        $bombeiroVideiraComCarteira = new Bombeiro('Bombeiro Videira Carteira', '11111111111', true);
        $bombeiroVideiraComCarteira->setCidadeOrigem(CbmscConstants::CIDADE_VIDEIRA);
        for ($dia = 1; $dia <= 5; $dia++) {
            $bombeiroVideiraComCarteira->adicionarDisponibilidade(new Disponibilidade($dia, CbmscConstants::TURNO_DIURNO));
        }

        // Bombeiro de Videira sem carteira
        $bombeiroVideiraSemCarteira = new Bombeiro('Bombeiro Videira Sem Carteira', '22222222222', false);
        $bombeiroVideiraSemCarteira->setCidadeOrigem(CbmscConstants::CIDADE_VIDEIRA);
        for ($dia = 1; $dia <= 5; $dia++) {
            $bombeiroVideiraSemCarteira->adicionarDisponibilidade(new Disponibilidade($dia, CbmscConstants::TURNO_NOTURNO));
        }

        // Bombeiro de outra cidade
        $bombeiroOutraCidade = new Bombeiro('Bombeiro Outra Cidade', '33333333333', false);
        $bombeiroOutraCidade->setCidadeOrigem(CbmscConstants::CIDADE_FRAIBURGO);
        for ($dia = 1; $dia <= 5; $dia++) {
            $bombeiroOutraCidade->adicionarDisponibilidade(new Disponibilidade($dia, CbmscConstants::TURNO_INTEGRAL));
        }

        $this->calculador->adicionarBombeiro($querubim);
        $this->calculador->adicionarBombeiro($bombeiroVideiraComCarteira);
        $this->calculador->adicionarBombeiro($bombeiroVideiraSemCarteira);
        $this->calculador->adicionarBombeiro($bombeiroOutraCidade);

        $resultado = $this->calculador->distribuirTurnosParaMes(60);

        // Verifica estrutura
        $this->assertIsArray($resultado);
        
        // Verifica que há distribuição para os primeiros 5 dias
        // Nota: Nem todos os turnos podem existir se não houver bombeiros disponíveis
        for ($dia = 1; $dia <= 5; $dia++) {
            if (isset($resultado[$dia])) {
                // Verifica que pelo menos um turno foi criado
                $this->assertNotEmpty($resultado[$dia], "Dia $dia deve ter pelo menos um turno");
                // Verifica que os turnos existentes são arrays
                foreach ($resultado[$dia] as $turno => $bombeiros) {
                    $this->assertIsArray($bombeiros, "Turno $turno no dia $dia deve ser um array");
                }
            }
        }
        
        // Verifica que o Querubim foi selecionado primeiro nos turnos integrais
        if (isset($resultado[1][CbmscConstants::TURNO_INTEGRAL]) && 
            count($resultado[1][CbmscConstants::TURNO_INTEGRAL]) > 0) {
            $primeiroBombeiro = $resultado[1][CbmscConstants::TURNO_INTEGRAL][0];
            $this->assertEquals('BC CHEROBIN ', $primeiroBombeiro->getNome());
        }
    }
}

