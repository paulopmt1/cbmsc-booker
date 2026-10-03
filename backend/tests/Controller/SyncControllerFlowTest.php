<?php

namespace App\Tests\Controller;

use App\GoogleSheetsBundle\Service\GoogleSheetsService;
use App\Tests\FiremanBundle\Service\FiremanSpreadsheetConverterTest;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Testa o fluxo principal completo (formulário -> Google Sheets -> algoritmo -> Google Sheets),
 * substituindo apenas o GoogleSheetsService por um dublê em memória.
 */
class SyncControllerFlowTest extends KernelTestCase
{
    private const ID_ESCOLHA = 'id-escolha';
    private const ID_PRELIMINAR = 'id-preliminar';
    private const ID_ANTIGUIDADE = 'id-antiguidade';

    /** @var array<string, string> */
    private array $titulos;

    /** @var array<string, array> */
    private array $dados;

    /** @var array<int, array{sheetId: string, range: string, values: array}> */
    private array $escritas = [];

    /** @var array<int, string> */
    private array $leituras = [];

    private ?\Exception $erroDaApi = null;

    protected function setUp(): void
    {
        $this->titulos = [
            self::ID_ESCOLHA => 'Escolha de Horários - Agosto 2025 (respostas)',
            self::ID_PRELIMINAR => 'PME Preliminar - Agosto 2025',
            self::ID_ANTIGUIDADE => 'Antiguidade BCs 2025',
        ];

        $this->dados = [
            self::ID_ESCOLHA => [
                FiremanSpreadsheetConverterTest::linhaPlanilha('BC Ana', '111', 'Não', [1 => 'Integral', 2 => 'Diurno']),
                FiremanSpreadsheetConverterTest::linhaPlanilha('BC Bruno', '222', 'Sim', [1 => 'Diurno', 3 => 'Noturno']),
                FiremanSpreadsheetConverterTest::linhaPlanilha('BC Carla', '333', 'Não', [2 => 'Integral']),
            ],
            self::ID_ANTIGUIDADE => [['111', '1'], ['222', '2'], ['333', '3']],
        ];

        self::bootKernel();

        $sheets = $this->createMock(GoogleSheetsService::class);
        $sheets->method('getSpreadsheetTitle')->willReturnCallback(function (string $id) {
            if ($this->erroDaApi) {
                throw $this->erroDaApi;
            }
            return $this->titulos[$id] ?? throw new \Exception("Planilha {$id} não encontrada");
        });
        $sheets->method('getSheetData')->willReturnCallback(function (string $id, string $range) {
            $this->leituras[] = "{$id}!{$range}";
            return $this->dados[$id] ?? throw new \Exception('A planilha está vazia');
        });
        $sheets->method('updateData')->willReturnCallback(function (string $id, string $range, array $values) {
            $this->escritas[] = ['sheetId' => $id, 'range' => $range, 'values' => $values];
        });

        static::getContainer()->set(GoogleSheetsService::class, $sheets);
    }

    private function post(array $campos = []): Response
    {
        $campos += [
            'sheetIdEscolhaHorarios' => self::ID_ESCOLHA,
            'sheetIdPreliminar' => self::ID_PRELIMINAR,
            'sheetIdAntiguidade' => '',
            'cotasPorDia' => '2.5',
            'diasMotoristaHidden' => '',
            'tipoProcessamento' => 'algoritmo',
        ];
        $campos = array_filter($campos, fn ($v) => $v !== null);

        return self::$kernel->handle(Request::create('/', 'POST', $campos));
    }

    public function testGetExibeFormulario(): void
    {
        $response = self::$kernel->handle(Request::create('/', 'GET'));

        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString('name="sheetIdEscolhaHorarios"', $response->getContent());
        $this->assertStringContainsString('name="sheetIdPreliminar"', $response->getContent());
        $this->assertSame([], $this->escritas);
    }

    public function testProcessamentoComAlgoritmoEscrevePlanilhaPreliminar(): void
    {
        $response = $this->post();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString('Dados sincronizados com sucesso!', $response->getContent());

        $this->assertSame([self::ID_ESCOLHA . '!A2:AI102'], $this->leituras);
        $this->assertCount(1, $this->escritas);

        $escrita = $this->escritas[0];
        $this->assertSame(self::ID_PRELIMINAR, $escrita['sheetId']);
        // 3 bombeiros a partir da linha 13
        $this->assertSame('A13:AH16', $escrita['range']);
        $this->assertCount(3, $escrita['values']);
        $this->assertSame(['BC Ana', 'BC Bruno', 'BC Carla'], array_column($escrita['values'], 0));

        foreach ($escrita['values'] as $linha) {
            $this->assertCount(34, $linha);
        }

        // Poucos bombeiros: todos são escalados em tudo que pediram
        [$ana, $bruno, $carla] = $escrita['values'];
        $this->assertSame(['I', 'D', ''], [$ana[3], $ana[4], $ana[5]]);
        $this->assertSame(['D', '', 'N'], [$bruno[3], $bruno[4], $bruno[5]]);
        $this->assertSame(['', 'I', ''], [$carla[3], $carla[4], $carla[5]]);
        $this->assertTrue($bruno[2]);
    }

    public function testConversaoSimplesCopiaTodasAsDisponibilidades(): void
    {
        // Muitos candidatos para poucas vagas: com o algoritmo alguém ficaria de fora
        $this->dados[self::ID_ESCOLHA] = array_map(
            fn (int $i) => FiremanSpreadsheetConverterTest::linhaPlanilha("BC {$i}", strval($i), 'Não', [1 => 'Diurno']),
            range(1, 8)
        );

        // Checkbox desmarcado = campo ausente no POST
        $this->post(['tipoProcessamento' => null, 'cotasPorDia' => '1']);

        $this->assertCount(1, $this->escritas);
        $this->assertSame(array_fill(0, 8, 'D'), array_column($this->escritas[0]['values'], 3));
    }

    public function testAlgoritmoLimitaVagasPelasCotasPorDia(): void
    {
        $this->dados[self::ID_ESCOLHA] = array_map(
            fn (int $i) => FiremanSpreadsheetConverterTest::linhaPlanilha("BC {$i}", strval($i), 'Não', [1 => 'Diurno']),
            range(1, 8)
        );

        // 1 cota = 24h = 2 meios turnos. A garantia de "todos recebem pelo menos 1 turno"
        // só troca quem já tem mais de um turno, então aqui ficam exatamente 2 escalados.
        $this->post(['cotasPorDia' => '1']);

        $escalados = array_filter(array_column($this->escritas[0]['values'], 3));
        $this->assertCount(2, $escalados);
    }

    public function testPlanilhaDeAntiguidadeDefinePrioridade(): void
    {
        // Dois candidatos para uma única vaga de 12h (0.5 cota)
        $this->dados[self::ID_ESCOLHA] = [
            FiremanSpreadsheetConverterTest::linhaPlanilha('BC Novo', '111', 'Não', [1 => 'Diurno']),
            FiremanSpreadsheetConverterTest::linhaPlanilha('BC Antigo', '222', 'Não', [1 => 'Diurno']),
        ];
        $this->dados[self::ID_ANTIGUIDADE] = [['222', '1'], ['111', '50']];

        $this->post(['cotasPorDia' => '0.5', 'sheetIdAntiguidade' => self::ID_ANTIGUIDADE]);

        $this->assertContains(self::ID_ANTIGUIDADE . '!A2:B', $this->leituras);
        [$novo, $antigo] = $this->escritas[0]['values'];
        $this->assertSame('', $novo[3]);
        $this->assertSame('D', $antigo[3]);
    }

    public function testDiasComMotoristaPriorizamCarteiraDeAmbulancia(): void
    {
        // Mesmo cenário, mas o menos antigo tem carteira de ambulância
        $this->dados[self::ID_ESCOLHA] = [
            FiremanSpreadsheetConverterTest::linhaPlanilha('BC Motorista', '111', 'Sim', [3 => 'Diurno', 4 => 'Diurno']),
            FiremanSpreadsheetConverterTest::linhaPlanilha('BC Antigo', '222', 'Não', [3 => 'Diurno', 4 => 'Diurno']),
        ];
        $this->dados[self::ID_ANTIGUIDADE] = [['222', '1'], ['111', '90']];

        // Datas no formato do datepicker; valores inválidos são ignorados
        $this->post([
            'cotasPorDia' => '0.5',
            'sheetIdAntiguidade' => self::ID_ANTIGUIDADE,
            'diasMotoristaHidden' => '2025-08-03, invalido,2025-02-30,',
        ]);

        [$motorista, $antigo] = $this->escritas[0]['values'];
        $diaTres = 3 + 2;
        $diaQuatro = 4 + 2;

        // Dia 3 precisa de motorista: carteira vence a antiguidade
        $this->assertSame('D', $motorista[$diaTres]);
        $this->assertSame('', $antigo[$diaTres]);

        // Dia 4 não precisa: antiguidade vence
        $this->assertSame('', $motorista[$diaQuatro]);
        $this->assertSame('D', $antigo[$diaQuatro]);
    }

    public static function cotasInvalidasProvider(): array
    {
        return [
            'vazio' => [''],
            'zero' => ['0'],
            'negativo' => ['-1'],
            'texto' => ['abc'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('cotasInvalidasProvider')]
    public function testCotasPorDiaInvalidasNaoAcessamPlanilhas(string $cotas): void
    {
        $response = $this->post(['cotasPorDia' => $cotas]);

        $this->assertStringContainsString('deve ser um número positivo maior que zero', $response->getContent());
        $this->assertSame([], $this->leituras);
        $this->assertSame([], $this->escritas);
    }

    public function testRecusaPlanilhaDeEscolhaComTituloErrado(): void
    {
        $this->titulos[self::ID_ESCOLHA] = 'Outra planilha qualquer';

        $response = $this->post();

        $this->assertStringContainsString('deve conter', $response->getContent());
        $this->assertStringContainsString('Outra planilha qualquer', $response->getContent());
        $this->assertSame([], $this->leituras);
        $this->assertSame([], $this->escritas);
    }

    public function testAceitaTituloDeEscolhaSemAcento(): void
    {
        $this->titulos[self::ID_ESCOLHA] = 'ESCOLHA DE HORARIOS agosto';

        $this->post();

        $this->assertCount(1, $this->escritas);
    }

    public function testRecusaPlanilhaPreliminarComTituloErrado(): void
    {
        // Proteção contra sobrescrever a planilha errada (ex.: a PME final)
        $this->titulos[self::ID_PRELIMINAR] = 'PME Final - Agosto 2025';

        $response = $this->post();

        $this->assertStringContainsString('PME Preliminar', $response->getContent());
        $this->assertSame([], $this->escritas);
    }

    public function testRecusaPlanilhaDeAntiguidadeComTituloErrado(): void
    {
        $this->titulos[self::ID_ANTIGUIDADE] = 'Planilha de férias';

        $response = $this->post(['sheetIdAntiguidade' => self::ID_ANTIGUIDADE]);

        $this->assertStringContainsString('Planilha de férias', $response->getContent());
        $this->assertSame([], $this->escritas);
    }

    public function testErroDaApiDoGoogleExibeMensagemAmigavel(): void
    {
        $this->erroDaApi = new \Exception('Erro da API do Google Sheets: Requested entity was not found.');

        $response = $this->post();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString('Erro ao sincronizar planilhas', $response->getContent());
        // Detalhes técnicos só aparecem no ambiente dev
        $this->assertStringNotContainsString('Requested entity was not found.', $response->getContent());
        $this->assertSame([], $this->escritas);
    }

    public function testPlanilhaSemRespostasValidasNaoEscreveNada(): void
    {
        $this->dados[self::ID_ESCOLHA] = [
            FiremanSpreadsheetConverterTest::linhaPlanilha('', '', 'Não'),
        ];

        $response = $this->post();

        $this->assertStringContainsString('Nenhum dado foi processado', $response->getContent());
        $this->assertSame([], $this->escritas);
    }

    public function testFormularioMantemValoresAposEnvio(): void
    {
        $response = $this->post();

        $this->assertStringContainsString('value="' . self::ID_ESCOLHA . '"', $response->getContent());
        $this->assertStringContainsString('value="' . self::ID_PRELIMINAR . '"', $response->getContent());
    }
}
