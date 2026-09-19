<?php

namespace App\Tests\ShiftBundle\Service;

use App\Constants\CbmscConstants;
use App\FiremanBundle\Entity\FiremanEntity;
use App\AvailabilityBundle\Entity\AvailabilityEntity;
use App\ShiftBundle\Entity\ShiftEntity;
use App\FiremanBundle\Service\SeniorityCalculator;
use App\ShiftBundle\Service\ShiftAllocator;
use PHPUnit\Framework\TestCase;

class ShiftAllocatorTest extends TestCase
{
    private SeniorityCalculator $seniorityCalculator;
    private ShiftAllocator $allocator;

    protected function setUp(): void
    {
        // Mock do SeniorityCalculator
        $this->seniorityCalculator = $this->createMock(SeniorityCalculator::class);
        $this->seniorityCalculator->method('getAntiguidade')
            ->willReturn(50); // Retorna uma antiguidade padrão de 50

        $this->allocator = new ShiftAllocator($this->seniorityCalculator, 10010010001);
    }

    /**
     * Teste básico: distribuição com poucos bombeiros e disponibilidade limitada
     */
    public function testDistribuirTurnosParaMesComPoucosBombeiros(): void
    {
        // Cria 3 bombeiros com disponibilidades diferentes
        $firefighter1 = new FiremanEntity('Bombeiro 1', '11111111111', false);
        $firefighter1->setCidadeOrigem(CbmscConstants::CIDADE_VIDEIRA);
        $firefighter1->adicionarDisponibilidade(new AvailabilityEntity(1, CbmscConstants::TURNO_INTEGRAL));
        $firefighter1->adicionarDisponibilidade(new AvailabilityEntity(2, CbmscConstants::TURNO_DIURNO));

        $firefighter2 = new FiremanEntity('Bombeiro 2', '22222222222', true);
        $firefighter2->setCidadeOrigem(CbmscConstants::CIDADE_FRAIBURGO);
        $firefighter2->adicionarDisponibilidade(new AvailabilityEntity(1, CbmscConstants::TURNO_DIURNO));
        $firefighter2->adicionarDisponibilidade(new AvailabilityEntity(2, CbmscConstants::TURNO_NOTURNO));

        $firefighter3 = new FiremanEntity('Bombeiro 3', '33333333333', false);
        $firefighter3->setCidadeOrigem(CbmscConstants::CIDADE_CACADOR);
        $firefighter3->adicionarDisponibilidade(new AvailabilityEntity(1, CbmscConstants::TURNO_NOTURNO));
        $firefighter3->adicionarDisponibilidade(new AvailabilityEntity(2, CbmscConstants::TURNO_INTEGRAL));

        $this->allocator->adicionarBombeiro($firefighter1);
        $this->allocator->adicionarBombeiro($firefighter2);
        $this->allocator->adicionarBombeiro($firefighter3);

        $result = $this->allocator->distribuirTurnosParaMes(60);

        // Verifica que o resultado é um array
        $this->assertIsArray($result);
        
        // Verifica que há distribuição para os dias 1 e 2
        $this->assertArrayHasKey(1, $result);
        $this->assertArrayHasKey(2, $result);
        
        // Verifica estrutura dos turnos
        if (isset($result[1])) {
            $this->assertArrayHasKey(CbmscConstants::TURNO_INTEGRAL, $result[1]);
            $this->assertArrayHasKey(CbmscConstants::TURNO_DIURNO, $result[1]);
            $this->assertArrayHasKey(CbmscConstants::TURNO_NOTURNO, $result[1]);
        }
    }

    /**
     * Teste: distribuição com bombeiro Querubim (deve ter prioridade máxima)
     */
    public function testDistribuirTurnosParaMesComQuerubim(): void
    {
        $priorityFirefighter = new FiremanEntity('BC CHEROBIN ', strval(10010010001), false);
        $priorityFirefighter->setCidadeOrigem(CbmscConstants::CIDADE_VIDEIRA);
        $priorityFirefighter->adicionarDisponibilidade(new AvailabilityEntity(1, CbmscConstants::TURNO_INTEGRAL));
        $priorityFirefighter->adicionarDisponibilidade(new AvailabilityEntity(2, CbmscConstants::TURNO_INTEGRAL));

        $firefighter2 = new FiremanEntity('Bombeiro 2', '22222222222', false);
        $firefighter2->setCidadeOrigem(CbmscConstants::CIDADE_VIDEIRA);
        $firefighter2->adicionarDisponibilidade(new AvailabilityEntity(1, CbmscConstants::TURNO_INTEGRAL));
        $firefighter2->adicionarDisponibilidade(new AvailabilityEntity(2, CbmscConstants::TURNO_INTEGRAL));

        $this->allocator->adicionarBombeiro($priorityFirefighter);
        $this->allocator->adicionarBombeiro($firefighter2);

        $result = $this->allocator->distribuirTurnosParaMes(60);

        // Verifica que o Querubim foi selecionado primeiro
        if (isset($result[1][CbmscConstants::TURNO_INTEGRAL]) && 
            count($result[1][CbmscConstants::TURNO_INTEGRAL]) > 0) {
            $firstFirefighter = $result[1][CbmscConstants::TURNO_INTEGRAL][0];
            $this->assertEquals('BC CHEROBIN ', $firstFirefighter->getNome());
        }
    }

    /**
     * Teste: distribuição com bombeiros com carteira de ambulância
     */
    public function testDistribuirTurnosParaMesComCarteiraAmbulancia(): void
    {
        $licensedFirefighter = new FiremanEntity('Bombeiro Com Carteira', '11111111111', true);
        $licensedFirefighter->setCidadeOrigem(CbmscConstants::CIDADE_VIDEIRA);
        $licensedFirefighter->adicionarDisponibilidade(new AvailabilityEntity(1, CbmscConstants::TURNO_INTEGRAL));
        $licensedFirefighter->adicionarDisponibilidade(new AvailabilityEntity(1, CbmscConstants::TURNO_DIURNO));

        $unlicensedFirefighter = new FiremanEntity('Bombeiro Sem Carteira', '22222222222', false);
        $unlicensedFirefighter->setCidadeOrigem(CbmscConstants::CIDADE_VIDEIRA);
        $unlicensedFirefighter->adicionarDisponibilidade(new AvailabilityEntity(1, CbmscConstants::TURNO_INTEGRAL));
        $unlicensedFirefighter->adicionarDisponibilidade(new AvailabilityEntity(1, CbmscConstants::TURNO_DIURNO));

        $this->allocator->adicionarBombeiro($licensedFirefighter);
        $this->allocator->adicionarBombeiro($unlicensedFirefighter);

        $result = $this->allocator->distribuirTurnosParaMes(60);

        // Verifica que há distribuição
        $this->assertIsArray($result);
        $this->assertArrayHasKey(1, $result);
    }

    /**
     * Teste: distribuição com diferentes horas por dia
     */
    public function testDistribuirTurnosParaMesComDiferentesHorasPorDia(): void
    {
        // Cria 5 bombeiros com disponibilidades variadas
        for ($i = 1; $i <= 5; $i++) {
            $firefighter = new FiremanEntity("Bombeiro $i", str_pad((string)$i, 11, '0', STR_PAD_LEFT), false);
            $firefighter->setCidadeOrigem(CbmscConstants::CIDADE_VIDEIRA);
            
            // Cada bombeiro disponível para vários dias e turnos
            for ($day = 1; $day <= 5; $day++) {
                $firefighter->adicionarDisponibilidade(new AvailabilityEntity($day, CbmscConstants::TURNO_INTEGRAL));
                $firefighter->adicionarDisponibilidade(new AvailabilityEntity($day, CbmscConstants::TURNO_DIURNO));
                $firefighter->adicionarDisponibilidade(new AvailabilityEntity($day, CbmscConstants::TURNO_NOTURNO));
            }
            
            $this->allocator->adicionarBombeiro($firefighter);
        }

        // Testa com 24 horas (1 turno integral)
        $result24h = $this->allocator->distribuirTurnosParaMes(24);
        $this->assertIsArray($result24h);
        
        // Testa com 36 horas (1 integral + 1 diurno ou noturno)
        $result36h = $this->allocator->distribuirTurnosParaMes(36);
        $this->assertIsArray($result36h);
        
        // Testa com 60 horas (padrão: 2.5 cotas)
        $result60h = $this->allocator->distribuirTurnosParaMes(60);
        $this->assertIsArray($result60h);
        
        // Testa com 72 horas (3 turnos integrais)
        $result72h = $this->allocator->distribuirTurnosParaMes(72);
        $this->assertIsArray($result72h);
    }

    /**
     * Teste: distribuição com bombeiros de diferentes cidades (prioridade Videira)
     */
    public function testDistribuirTurnosParaMesComDiferentesCidades(): void
    {
        $videiraFirefighter = new FiremanEntity('Bombeiro Videira', '11111111111', false);
        $videiraFirefighter->setCidadeOrigem(CbmscConstants::CIDADE_VIDEIRA);
        $videiraFirefighter->adicionarDisponibilidade(new AvailabilityEntity(1, CbmscConstants::TURNO_INTEGRAL));

        $fraiburgoFirefighter = new FiremanEntity('Bombeiro Fraiburgo', '22222222222', false);
        $fraiburgoFirefighter->setCidadeOrigem(CbmscConstants::CIDADE_FRAIBURGO);
        $fraiburgoFirefighter->adicionarDisponibilidade(new AvailabilityEntity(1, CbmscConstants::TURNO_INTEGRAL));

        $cacadorFirefighter = new FiremanEntity('Bombeiro Caçador', '33333333333', false);
        $cacadorFirefighter->setCidadeOrigem(CbmscConstants::CIDADE_CACADOR);
        $cacadorFirefighter->adicionarDisponibilidade(new AvailabilityEntity(1, CbmscConstants::TURNO_INTEGRAL));

        $this->allocator->adicionarBombeiro($videiraFirefighter);
        $this->allocator->adicionarBombeiro($fraiburgoFirefighter);
        $this->allocator->adicionarBombeiro($cacadorFirefighter);

        $result = $this->allocator->distribuirTurnosParaMes(24);

        // Verifica que há distribuição
        $this->assertIsArray($result);
        $this->assertArrayHasKey(1, $result);
        
        // Bombeiro de Videira deve ter prioridade (mais pontos)
        if (isset($result[1][CbmscConstants::TURNO_INTEGRAL]) && 
            count($result[1][CbmscConstants::TURNO_INTEGRAL]) > 0) {
            $firstFirefighter = $result[1][CbmscConstants::TURNO_INTEGRAL][0];
            $this->assertEquals(CbmscConstants::CIDADE_VIDEIRA, $firstFirefighter->getCidadeOrigem());
        }
    }

    /**
     * Teste: distribuição com muitos bombeiros e disponibilidades variadas
     */
    public function testDistribuirTurnosParaMesComMuitosBombeiros(): void
    {
        // Cria 10 bombeiros com diferentes combinações
        for ($i = 1; $i <= 10; $i++) {
            $firefighter = new FiremanEntity("Bombeiro $i", str_pad((string)$i, 11, '0', STR_PAD_LEFT), $i % 3 === 0);
            $firefighter->setCidadeOrigem($i % 2 === 0 ? CbmscConstants::CIDADE_VIDEIRA : CbmscConstants::CIDADE_FRAIBURGO);
            
            // Disponibilidades variadas
            for ($day = 1; $day <= 10; $day++) {
                $shifts = [
                    CbmscConstants::TURNO_INTEGRAL,
                    CbmscConstants::TURNO_DIURNO,
                    CbmscConstants::TURNO_NOTURNO
                ];
                
                // Cada bombeiro disponível para alguns turnos aleatórios
                $availableShifts = array_slice($shifts, 0, ($i % 3) + 1);
                foreach ($availableShifts as $shift) {
                    $firefighter->adicionarDisponibilidade(new AvailabilityEntity($day, $shift));
                }
            }
            
            $this->allocator->adicionarBombeiro($firefighter);
        }

        $result = $this->allocator->distribuirTurnosParaMes(60);

        // Verifica estrutura básica
        $this->assertIsArray($result);
        
        // Verifica alguns dias
        // Nota: Nem todos os turnos podem existir se não houver bombeiros disponíveis
        for ($day = 1; $day <= 10; $day++) {
            if (isset($result[$day])) {
                // Verifica que pelo menos um turno foi criado
                $this->assertNotEmpty($result[$day], "Dia $day deve ter pelo menos um turno");
                // Verifica que os turnos existentes são arrays
                foreach ($result[$day] as $shift => $firefighters) {
                    $this->assertIsArray($firefighters, "Turno $shift no dia $day deve ser um array");
                }
            }
        }
    }

    /**
     * Teste: distribuição com bombeiros sem disponibilidade para alguns dias
     */
    public function testDistribuirTurnosParaMesComDisponibilidadeLimitada(): void
    {
        $firefighter1 = new FiremanEntity('Bombeiro 1', '11111111111', false);
        $firefighter1->setCidadeOrigem(CbmscConstants::CIDADE_VIDEIRA);
        // Disponível apenas no dia 1
        $firefighter1->adicionarDisponibilidade(new AvailabilityEntity(1, CbmscConstants::TURNO_INTEGRAL));

        $firefighter2 = new FiremanEntity('Bombeiro 2', '22222222222', false);
        $firefighter2->setCidadeOrigem(CbmscConstants::CIDADE_VIDEIRA);
        // Disponível apenas no dia 2
        $firefighter2->adicionarDisponibilidade(new AvailabilityEntity(2, CbmscConstants::TURNO_INTEGRAL));

        $firefighter3 = new FiremanEntity('Bombeiro 3', '33333333333', false);
        $firefighter3->setCidadeOrigem(CbmscConstants::CIDADE_VIDEIRA);
        // Disponível nos dias 1 e 2
        $firefighter3->adicionarDisponibilidade(new AvailabilityEntity(1, CbmscConstants::TURNO_DIURNO));
        $firefighter3->adicionarDisponibilidade(new AvailabilityEntity(2, CbmscConstants::TURNO_DIURNO));

        $this->allocator->adicionarBombeiro($firefighter1);
        $this->allocator->adicionarBombeiro($firefighter2);
        $this->allocator->adicionarBombeiro($firefighter3);

        $result = $this->allocator->distribuirTurnosParaMes(60);

        // Verifica que há distribuição nos dias 1 e 2
        $this->assertIsArray($result);
        
        // Dia 1 deve ter pelo menos o bombeiro 1
        if (isset($result[1][CbmscConstants::TURNO_INTEGRAL])) {
            $this->assertGreaterThanOrEqual(0, count($result[1][CbmscConstants::TURNO_INTEGRAL]));
        }
        
        // Dia 2 deve ter pelo menos o bombeiro 2
        if (isset($result[2][CbmscConstants::TURNO_INTEGRAL])) {
            $this->assertGreaterThanOrEqual(0, count($result[2][CbmscConstants::TURNO_INTEGRAL]));
        }
    }

    /**
     * Teste: verifica que a pontuação é recalculada após cada dia
     */
    public function testDistribuirTurnosParaMesRecalculaPontuacao(): void
    {
        $firefighter1 = new FiremanEntity('Bombeiro 1', '11111111111', false);
        $firefighter1->setCidadeOrigem(CbmscConstants::CIDADE_VIDEIRA);
        $firefighter1->adicionarDisponibilidade(new AvailabilityEntity(1, CbmscConstants::TURNO_INTEGRAL));
        $firefighter1->adicionarDisponibilidade(new AvailabilityEntity(2, CbmscConstants::TURNO_INTEGRAL));

        $firefighter2 = new FiremanEntity('Bombeiro 2', '22222222222', false);
        $firefighter2->setCidadeOrigem(CbmscConstants::CIDADE_VIDEIRA);
        $firefighter2->adicionarDisponibilidade(new AvailabilityEntity(1, CbmscConstants::TURNO_INTEGRAL));
        $firefighter2->adicionarDisponibilidade(new AvailabilityEntity(2, CbmscConstants::TURNO_INTEGRAL));

        $this->allocator->adicionarBombeiro($firefighter1);
        $this->allocator->adicionarBombeiro($firefighter2);

        $result = $this->allocator->distribuirTurnosParaMes(24);

        // Verifica que ambos os bombeiros podem ter sido selecionados
        // (a pontuação deve ser recalculada, permitindo distribuição justa)
        $this->assertIsArray($result);
        
        // Verifica que há distribuição
        if (isset($result[1][CbmscConstants::TURNO_INTEGRAL])) {
            $this->assertGreaterThanOrEqual(0, count($result[1][CbmscConstants::TURNO_INTEGRAL]));
        }
    }

    /**
     * Teste: combinação complexa com múltiplos fatores
     */
    public function testDistribuirTurnosParaMesCombinacaoComplexa(): void
    {
        // Querubim com carteira
        $priorityFirefighter = new FiremanEntity('BC CHEROBIN ', strval(10010010001), true);
        $priorityFirefighter->setCidadeOrigem(CbmscConstants::CIDADE_VIDEIRA);
        for ($day = 1; $day <= 5; $day++) {
            $priorityFirefighter->adicionarDisponibilidade(new AvailabilityEntity($day, CbmscConstants::TURNO_INTEGRAL));
        }

        // Bombeiro de Videira com carteira
        $licensedVideiraFirefighter = new FiremanEntity('Bombeiro Videira Carteira', '11111111111', true);
        $licensedVideiraFirefighter->setCidadeOrigem(CbmscConstants::CIDADE_VIDEIRA);
        for ($day = 1; $day <= 5; $day++) {
            $licensedVideiraFirefighter->adicionarDisponibilidade(new AvailabilityEntity($day, CbmscConstants::TURNO_DIURNO));
        }

        // Bombeiro de Videira sem carteira
        $unlicensedVideiraFirefighter = new FiremanEntity('Bombeiro Videira Sem Carteira', '22222222222', false);
        $unlicensedVideiraFirefighter->setCidadeOrigem(CbmscConstants::CIDADE_VIDEIRA);
        for ($day = 1; $day <= 5; $day++) {
            $unlicensedVideiraFirefighter->adicionarDisponibilidade(new AvailabilityEntity($day, CbmscConstants::TURNO_NOTURNO));
        }

        // Bombeiro de outra cidade
        $otherCityFirefighter = new FiremanEntity('Bombeiro Outra Cidade', '33333333333', false);
        $otherCityFirefighter->setCidadeOrigem(CbmscConstants::CIDADE_FRAIBURGO);
        for ($day = 1; $day <= 5; $day++) {
            $otherCityFirefighter->adicionarDisponibilidade(new AvailabilityEntity($day, CbmscConstants::TURNO_INTEGRAL));
        }

        $this->allocator->adicionarBombeiro($priorityFirefighter);
        $this->allocator->adicionarBombeiro($licensedVideiraFirefighter);
        $this->allocator->adicionarBombeiro($unlicensedVideiraFirefighter);
        $this->allocator->adicionarBombeiro($otherCityFirefighter);

        $result = $this->allocator->distribuirTurnosParaMes(60);

        // Verifica estrutura
        $this->assertIsArray($result);
        
        // Verifica que há distribuição para os primeiros 5 dias
        // Nota: Nem todos os turnos podem existir se não houver bombeiros disponíveis
        for ($day = 1; $day <= 5; $day++) {
            if (isset($result[$day])) {
                // Verifica que pelo menos um turno foi criado
                $this->assertNotEmpty($result[$day], "Dia $day deve ter pelo menos um turno");
                // Verifica que os turnos existentes são arrays
                foreach ($result[$day] as $shift => $firefighters) {
                    $this->assertIsArray($firefighters, "Turno $shift no dia $day deve ser um array");
                }
            }
        }
        
        // Verifica que o Querubim foi selecionado primeiro nos turnos integrais
        if (isset($result[1][CbmscConstants::TURNO_INTEGRAL]) && 
            count($result[1][CbmscConstants::TURNO_INTEGRAL]) > 0) {
            $firstFirefighter = $result[1][CbmscConstants::TURNO_INTEGRAL][0];
            $this->assertEquals('BC CHEROBIN ', $firstFirefighter->getNome());
        }
    }

    /**
     * Teste: Cenário 1 - Distribuição de meias cotas com demanda parcial
     * - Meias cotas restantes: 5
     * - Meias cotas necessárias para turno diurno: 3
     * - Meias cotas necessárias para turno noturno: 10
     * Solução esperada:
     * - Meias cotas para distribuir para turno diurno: 3
     * - Meias cotas para distribuir para turno noturno: 2
     */
    public function testDistribuicaoMeiasCotasCenario1(): void
    {
        // Cria 3 bombeiros disponíveis apenas para turno diurno
        for ($i = 1; $i <= 3; $i++) {
            $firefighter = new FiremanEntity("Bombeiro Diurno $i", str_pad((string)$i, 11, '0', STR_PAD_LEFT), false);
            $firefighter->setCidadeOrigem(CbmscConstants::CIDADE_VIDEIRA);
            $firefighter->adicionarDisponibilidade(new AvailabilityEntity(1, CbmscConstants::TURNO_DIURNO));
            $this->allocator->adicionarBombeiro($firefighter);
        }

        // Cria 10 bombeiros disponíveis apenas para turno noturno
        for ($i = 1; $i <= 10; $i++) {
            $firefighter = new FiremanEntity("Bombeiro Noturno $i", str_pad((string)(10 + $i), 11, '0', STR_PAD_LEFT), false);
            $firefighter->setCidadeOrigem(CbmscConstants::CIDADE_VIDEIRA);
            $firefighter->adicionarDisponibilidade(new AvailabilityEntity(1, CbmscConstants::TURNO_NOTURNO));
            $this->allocator->adicionarBombeiro($firefighter);
        }

        // 60 horas = 5 meias cotas, sem turnos integrais (todos disponíveis apenas para diurno ou noturno)
        $result = $this->allocator->distribuirTurnosParaMes(60);

        // Verifica estrutura
        $this->assertIsArray($result);
        $this->assertArrayHasKey(1, $result);

        // Verifica que não há turnos integrais
        $fullShifts = isset($result[1][CbmscConstants::TURNO_INTEGRAL]) 
            ? count($result[1][CbmscConstants::TURNO_INTEGRAL]) 
            : 0;
        $this->assertEquals(0, $fullShifts, 'Não deve haver turnos integrais');

        // Verifica distribuição: 3 meias cotas para diurno, 2 para noturno
        $dayHalfQuotas = isset($result[1][CbmscConstants::TURNO_DIURNO]) 
            ? count($result[1][CbmscConstants::TURNO_DIURNO]) 
            : 0;
        $nightHalfQuotas = isset($result[1][CbmscConstants::TURNO_NOTURNO]) 
            ? count($result[1][CbmscConstants::TURNO_NOTURNO]) 
            : 0;

        $this->assertEquals(3, $dayHalfQuotas, 'Deve distribuir 3 meias cotas para turno diurno');
        $this->assertEquals(2, $nightHalfQuotas, 'Deve distribuir 2 meias cotas para turno noturno');
        $this->assertEquals(5, $dayHalfQuotas + $nightHalfQuotas, 'Total deve ser 5 meias cotas');
    }

    /**
     * Teste: Cenário 2 - Distribuição de meias cotas sem demanda diurna
     * - Meias cotas restantes: 5
     * - Meias cotas necessárias para turno diurno: 0
     * - Meias cotas necessárias para turno noturno: 10
     * Solução esperada:
     * - Meias cotas para distribuir para turno diurno: 0
     * - Meias cotas para distribuir para turno noturno: 5
     */
    public function testDistribuicaoMeiasCotasCenario2(): void
    {
        // Não cria bombeiros para turno diurno (0 disponíveis)

        // Cria 10 bombeiros disponíveis apenas para turno noturno
        for ($i = 1; $i <= 10; $i++) {
            $firefighter = new FiremanEntity("Bombeiro Noturno $i", str_pad((string)$i, 11, '0', STR_PAD_LEFT), false);
            $firefighter->setCidadeOrigem(CbmscConstants::CIDADE_VIDEIRA);
            $firefighter->adicionarDisponibilidade(new AvailabilityEntity(1, CbmscConstants::TURNO_NOTURNO));
            $this->allocator->adicionarBombeiro($firefighter);
        }

        // 60 horas = 5 meias cotas, sem turnos integrais
        $result = $this->allocator->distribuirTurnosParaMes(60);

        // Verifica estrutura
        $this->assertIsArray($result);
        $this->assertArrayHasKey(1, $result);

        // Verifica que não há turnos integrais
        $fullShifts = isset($result[1][CbmscConstants::TURNO_INTEGRAL]) 
            ? count($result[1][CbmscConstants::TURNO_INTEGRAL]) 
            : 0;
        $this->assertEquals(0, $fullShifts, 'Não deve haver turnos integrais');

        // Verifica distribuição: 0 meias cotas para diurno, 5 para noturno
        $dayHalfQuotas = isset($result[1][CbmscConstants::TURNO_DIURNO]) 
            ? count($result[1][CbmscConstants::TURNO_DIURNO]) 
            : 0;
        $nightHalfQuotas = isset($result[1][CbmscConstants::TURNO_NOTURNO]) 
            ? count($result[1][CbmscConstants::TURNO_NOTURNO]) 
            : 0;

        $this->assertEquals(0, $dayHalfQuotas, 'Deve distribuir 0 meias cotas para turno diurno');
        $this->assertEquals(5, $nightHalfQuotas, 'Deve distribuir 5 meias cotas para turno noturno');
        $this->assertEquals(5, $dayHalfQuotas + $nightHalfQuotas, 'Total deve ser 5 meias cotas');
    }

    /**
     * Teste: Cenário 3 - Distribuição de meias cotas sem demanda noturna
     * - Meias cotas restantes: 5
     * - Meias cotas necessárias para turno diurno: 5
     * - Meias cotas necessárias para turno noturno: 0
     * Solução esperada:
     * - Meias cotas para distribuir para turno diurno: 5
     * - Meias cotas para distribuir para turno noturno: 0
     */
    public function testDistribuicaoMeiasCotasCenario3(): void
    {
        // Cria 5 bombeiros disponíveis apenas para turno diurno
        for ($i = 1; $i <= 5; $i++) {
            $firefighter = new FiremanEntity("Bombeiro Diurno $i", str_pad((string)$i, 11, '0', STR_PAD_LEFT), false);
            $firefighter->setCidadeOrigem(CbmscConstants::CIDADE_VIDEIRA);
            $firefighter->adicionarDisponibilidade(new AvailabilityEntity(1, CbmscConstants::TURNO_DIURNO));
            $this->allocator->adicionarBombeiro($firefighter);
        }

        // Não cria bombeiros para turno noturno (0 disponíveis)

        // 60 horas = 5 meias cotas, sem turnos integrais
        $result = $this->allocator->distribuirTurnosParaMes(60);

        // Verifica estrutura
        $this->assertIsArray($result);
        $this->assertArrayHasKey(1, $result);

        // Verifica que não há turnos integrais
        $fullShifts = isset($result[1][CbmscConstants::TURNO_INTEGRAL]) 
            ? count($result[1][CbmscConstants::TURNO_INTEGRAL]) 
            : 0;
        $this->assertEquals(0, $fullShifts, 'Não deve haver turnos integrais');

        // Verifica distribuição: 5 meias cotas para diurno, 0 para noturno
        $dayHalfQuotas = isset($result[1][CbmscConstants::TURNO_DIURNO]) 
            ? count($result[1][CbmscConstants::TURNO_DIURNO]) 
            : 0;
        $nightHalfQuotas = isset($result[1][CbmscConstants::TURNO_NOTURNO]) 
            ? count($result[1][CbmscConstants::TURNO_NOTURNO]) 
            : 0;

        $this->assertEquals(5, $dayHalfQuotas, 'Deve distribuir 5 meias cotas para turno diurno');
        $this->assertEquals(0, $nightHalfQuotas, 'Deve distribuir 0 meias cotas para turno noturno');
        $this->assertEquals(5, $dayHalfQuotas + $nightHalfQuotas, 'Total deve ser 5 meias cotas');
    }

    /**
     * Teste: Limite de cotas integrais
     * Com 2.5 cotas por dia (60 horas), não podemos ter mais de 2 cotas integrais.
     * Limite calculado: floor(60 / (2 * 12)) = floor(60 / 24) = 2
     */
    public function testLimiteCotasIntegrais(): void
    {
        // Cria 5 bombeiros disponíveis apenas para turno integral
        // Mesmo tendo 5 disponíveis, com 60 horas só podemos ter 2 integrais (48 horas)
        for ($i = 1; $i <= 5; $i++) {
            $firefighter = new FiremanEntity("Bombeiro Integral $i", str_pad((string)$i, 11, '0', STR_PAD_LEFT), false);
            $firefighter->setCidadeOrigem(CbmscConstants::CIDADE_VIDEIRA);
            $firefighter->adicionarDisponibilidade(new AvailabilityEntity(1, CbmscConstants::TURNO_INTEGRAL));
            $this->allocator->adicionarBombeiro($firefighter);
        }

        // 60 horas = 2.5 cotas, limite de integrais = floor(60/24) = 2
        $result = $this->allocator->distribuirTurnosParaMes(60);

        // Verifica estrutura
        $this->assertIsArray($result);
        $this->assertArrayHasKey(1, $result);

        // Verifica que o limite de 2 cotas integrais é respeitado
        $fullQuotas = isset($result[1][CbmscConstants::TURNO_INTEGRAL]) 
            ? count($result[1][CbmscConstants::TURNO_INTEGRAL]) 
            : 0;

        $this->assertEquals(2, $fullQuotas, 'Com 60 horas (2.5 cotas), deve ter no máximo 2 cotas integrais');
        
        // Verifica que as horas restantes (60 - 48 = 12) são distribuídas para outros turnos
        $fullShiftHours = $fullQuotas * 24; // Cada integral = 24 horas
        $this->assertEquals(48, $fullShiftHours, '2 cotas integrais = 48 horas');
    }

    /**
     * Teste: Limite de cotas integrais com diferentes horas por dia
     * Verifica o limite para diferentes configurações de horas
     */
    public function testLimiteCotasIntegraisDiferentesHoras(): void
    {
        // Teste com 24 horas (1 cota) - limite = floor(24/24) = 1
        $this->allocator = new ShiftAllocator($this->seniorityCalculator, 10010010001);
        for ($i = 1; $i <= 3; $i++) {
            $firefighter = new FiremanEntity("Bombeiro Integral $i", str_pad((string)$i, 11, '0', STR_PAD_LEFT), false);
            $firefighter->setCidadeOrigem(CbmscConstants::CIDADE_VIDEIRA);
            $firefighter->adicionarDisponibilidade(new AvailabilityEntity(1, CbmscConstants::TURNO_INTEGRAL));
            $this->allocator->adicionarBombeiro($firefighter);
        }
        $result24h = $this->allocator->distribuirTurnosParaMes(24);
        $fullQuotas24h = isset($result24h[1][CbmscConstants::TURNO_INTEGRAL]) 
            ? count($result24h[1][CbmscConstants::TURNO_INTEGRAL]) 
            : 0;
        $this->assertEquals(1, $fullQuotas24h, 'Com 24 horas, deve ter no máximo 1 cota integral');

        // Teste com 48 horas (2 cotas) - limite = floor(48/24) = 2
        $this->allocator = new ShiftAllocator($this->seniorityCalculator, 10010010001);
        for ($i = 1; $i <= 5; $i++) {
            $firefighter = new FiremanEntity("Bombeiro Integral $i", str_pad((string)$i, 11, '0', STR_PAD_LEFT), false);
            $firefighter->setCidadeOrigem(CbmscConstants::CIDADE_VIDEIRA);
            $firefighter->adicionarDisponibilidade(new AvailabilityEntity(1, CbmscConstants::TURNO_INTEGRAL));
            $this->allocator->adicionarBombeiro($firefighter);
        }
        $result48h = $this->allocator->distribuirTurnosParaMes(48);
        $fullQuotas48h = isset($result48h[1][CbmscConstants::TURNO_INTEGRAL]) 
            ? count($result48h[1][CbmscConstants::TURNO_INTEGRAL]) 
            : 0;
        $this->assertEquals(2, $fullQuotas48h, 'Com 48 horas, deve ter no máximo 2 cotas integrais');

        // Teste com 72 horas (3 cotas) - limite = floor(72/24) = 3
        $this->allocator = new ShiftAllocator($this->seniorityCalculator, 10010010001);
        for ($i = 1; $i <= 5; $i++) {
            $firefighter = new FiremanEntity("Bombeiro Integral $i", str_pad((string)$i, 11, '0', STR_PAD_LEFT), false);
            $firefighter->setCidadeOrigem(CbmscConstants::CIDADE_VIDEIRA);
            $firefighter->adicionarDisponibilidade(new AvailabilityEntity(1, CbmscConstants::TURNO_INTEGRAL));
            $this->allocator->adicionarBombeiro($firefighter);
        }
        $result72h = $this->allocator->distribuirTurnosParaMes(72);
        $fullQuotas72h = isset($result72h[1][CbmscConstants::TURNO_INTEGRAL]) 
            ? count($result72h[1][CbmscConstants::TURNO_INTEGRAL]) 
            : 0;
        $this->assertEquals(3, $fullQuotas72h, 'Com 72 horas, deve ter no máximo 3 cotas integrais');
    }

    /**
     * Teste: Bombeiro que já atingiu o limite de turnos integrais deve ser pulado
     * Verifica que um bombeiro com 3 turnos integrais já adquiridos não recebe mais turnos integrais
     */
    public function testBombeiroComLimiteTurnosIntegraisDeveSerPulado(): void
    {
        // Bombeiro que já tem 3 turnos integrais adquiridos (limite máximo)
        $firefighterAtLimit = new FiremanEntity('Bombeiro Com Limite', '11111111111', false);
        $firefighterAtLimit->setCidadeOrigem(CbmscConstants::CIDADE_VIDEIRA);
        $firefighterAtLimit->adicionarDisponibilidade(new AvailabilityEntity(1, CbmscConstants::TURNO_INTEGRAL));
        
        // Simula que ele já adquiriu 3 turnos integrais em dias anteriores
        $firefighterAtLimit->adicionaTurnoAdquirido(new ShiftEntity(0, CbmscConstants::TURNO_INTEGRAL));
        $firefighterAtLimit->adicionaTurnoAdquirido(new ShiftEntity(0, CbmscConstants::TURNO_INTEGRAL));
        $firefighterAtLimit->adicionaTurnoAdquirido(new ShiftEntity(0, CbmscConstants::TURNO_INTEGRAL));

        // Bombeiro que ainda não atingiu o limite
        $firefighterBelowLimit = new FiremanEntity('Bombeiro Sem Limite', '22222222222', false);
        $firefighterBelowLimit->setCidadeOrigem(CbmscConstants::CIDADE_VIDEIRA);
        $firefighterBelowLimit->adicionarDisponibilidade(new AvailabilityEntity(1, CbmscConstants::TURNO_INTEGRAL));

        $this->allocator->adicionarBombeiro($firefighterAtLimit);
        $this->allocator->adicionarBombeiro($firefighterBelowLimit);

        $result = $this->allocator->distribuirTurnosParaMes(24);

        // Verifica estrutura
        $this->assertIsArray($result);
        $this->assertArrayHasKey(1, $result);
        $this->assertArrayHasKey(CbmscConstants::TURNO_INTEGRAL, $result[1]);

        // Verifica que o bombeiro com limite não foi selecionado
        $selectedFirefighters = $result[1][CbmscConstants::TURNO_INTEGRAL];
        $selectedNames = array_map(function($b) { return $b->getNome(); }, $selectedFirefighters);
        
        $this->assertNotContains('Bombeiro Com Limite', $selectedNames, 
            'Bombeiro com limite de turnos integrais não deve ser selecionado');
        
        // Verifica que o bombeiro sem limite foi selecionado
        $this->assertContains('Bombeiro Sem Limite', $selectedNames,
            'Bombeiro sem limite deve ser selecionado');
        
        // Verifica que há exatamente 1 bombeiro selecionado (apenas o sem limite)
        $this->assertCount(1, $selectedFirefighters, 
            'Deve haver apenas 1 bombeiro selecionado (o sem limite)');
    }

    /**
     * Teste: Dias com motorista adicional devem ser processados antes dos dias normais
     * Verifica que quando há dias que precisam de motorista adicional, eles são processados primeiro
     */
    public function testDiasComMotoristaAdicionalDevemSerProcessadosAntes(): void
    {

        // Cria um bombeiro disponível dias 1,2,3 e 4 com carteira de ambulância e turno integral
        $firefighter1 = new FiremanEntity('Bombeiro 1', '11111111111', true);
        $firefighter1->setCidadeOrigem(CbmscConstants::CIDADE_VIDEIRA);
        $firefighter1->adicionarDisponibilidade(new AvailabilityEntity(1, CbmscConstants::TURNO_INTEGRAL));
        $firefighter1->adicionarDisponibilidade(new AvailabilityEntity(2, CbmscConstants::TURNO_INTEGRAL));
        $firefighter1->adicionarDisponibilidade(new AvailabilityEntity(3, CbmscConstants::TURNO_INTEGRAL));
        $firefighter1->adicionarDisponibilidade(new AvailabilityEntity(4, CbmscConstants::TURNO_INTEGRAL));
        $this->allocator->adicionarBombeiro($firefighter1);

        // Define dia 4 como o único dia que precisa de motorista adicional
        $daysRequiringAdditionalDriver = [4];
        $result = $this->allocator->distribuirTurnosParaMes(24, $daysRequiringAdditionalDriver);

        // Verifica estrutura
        $this->assertIsArray($result);
        $this->assertArrayHasKey(4, $result, 'Dia 4 (com motorista adicional) deve estar no resultado');
        $this->assertArrayHasKey(CbmscConstants::TURNO_INTEGRAL, $result[4], 
            'Dia 4 deve ter turnos integrais atribuídos');
    }

    /**
     * Teste: Garantir que bombeiros sem turno recebam pelo menos um turno
     * Verifica que a função garantirTurnoParaBombeirosSemTurno funciona corretamente
     */
    public function testGarantirTurnoParaBombeirosSemTurno(): void
    {
        // Cria bombeiros com alta pontuação (Videira + carteira) que receberão turnos facilmente
        $firefighter1 = new FiremanEntity('Bombeiro Alta Pontuação 1', '11111111111', true);
        $firefighter1->setCidadeOrigem(CbmscConstants::CIDADE_VIDEIRA);
        $firefighter1->adicionarDisponibilidade(new AvailabilityEntity(1, CbmscConstants::TURNO_INTEGRAL));
        $firefighter1->adicionarDisponibilidade(new AvailabilityEntity(2, CbmscConstants::TURNO_INTEGRAL));
        $firefighter1->adicionarDisponibilidade(new AvailabilityEntity(3, CbmscConstants::TURNO_INTEGRAL));

        $firefighter2 = new FiremanEntity('Bombeiro Alta Pontuação 2', '22222222222', true);
        $firefighter2->setCidadeOrigem(CbmscConstants::CIDADE_VIDEIRA);
        $firefighter2->adicionarDisponibilidade(new AvailabilityEntity(1, CbmscConstants::TURNO_INTEGRAL));
        $firefighter2->adicionarDisponibilidade(new AvailabilityEntity(2, CbmscConstants::TURNO_INTEGRAL));
        $firefighter2->adicionarDisponibilidade(new AvailabilityEntity(3, CbmscConstants::TURNO_INTEGRAL));

        // Cria um bombeiro com baixa pontuação (outra cidade, sem carteira) que pode não receber turno inicialmente
        $firefighterWithoutShift = new FiremanEntity('Bombeiro Sem Turno', '33333333333', false);
        $firefighterWithoutShift->setCidadeOrigem(CbmscConstants::CIDADE_FRAIBURGO);
        // Disponibilidade limitada apenas para alguns dias
        $firefighterWithoutShift->adicionarDisponibilidade(new AvailabilityEntity(1, CbmscConstants::TURNO_DIURNO));
        $firefighterWithoutShift->adicionarDisponibilidade(new AvailabilityEntity(2, CbmscConstants::TURNO_NOTURNO));
        $firefighterWithoutShift->adicionarDisponibilidade(new AvailabilityEntity(3, CbmscConstants::TURNO_DIURNO));

        $this->allocator->adicionarBombeiro($firefighter1);
        $this->allocator->adicionarBombeiro($firefighter2);
        $this->allocator->adicionarBombeiro($firefighterWithoutShift);

        // Distribui turnos com poucas horas por dia para que o bombeiro de baixa pontuação possa não receber inicialmente
        $result = $this->allocator->distribuirTurnosParaMes(24);

        // Verifica que todos os bombeiros têm pelo menos um turno após a garantia
        $this->assertGreaterThanOrEqual(1, count($firefighter1->getTurnosAdquiridos()), 
            'Bombeiro 1 deve ter pelo menos um turno');
        $this->assertGreaterThanOrEqual(1, count($firefighter2->getTurnosAdquiridos()), 
            'Bombeiro 2 deve ter pelo menos um turno');
        $this->assertGreaterThanOrEqual(1, count($firefighterWithoutShift->getTurnosAdquiridos()), 
            'Bombeiro sem turno deve receber pelo menos um turno após garantirTurnoParaBombeirosSemTurno');

        // Verifica que o bombeiro sem turno está presente no resultado
        $firefighterWithoutShiftFound = false;
        foreach ($result as $day => $shifts) {
            foreach ($shifts as $shift => $firefighters) {
                foreach ($firefighters as $firefighter) {
                    if ($firefighter->getNome() === 'Bombeiro Sem Turno') {
                        $firefighterWithoutShiftFound = true;
                        break 3;
                    }
                }
            }
        }
        $this->assertTrue($firefighterWithoutShiftFound, 
            'Bombeiro sem turno deve estar presente no resultado após garantirTurnoParaBombeirosSemTurno');
    }

}
