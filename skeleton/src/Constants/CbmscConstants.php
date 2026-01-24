<?php

namespace App\Constants;

class CbmscConstants {
    // Limite de cotas integrais por mês por bombeiro
    public const COTAS_INTEGRAIS_POR_MES = 3;

    // Cidades
    public const CIDADE_FRAIBURGO = "Fraiburgo";
    public const CIDADE_VIDEIRA = "Videira";
    public const CIDADE_CACADOR = "Caçador";
    
    // CPFs especiais
    public static function getCpfDoQuerubin(): int {
        return (int) ($_ENV['CPF_DO_QUERUBIN'] ?? 10010010001);
    }

    // Colunas da planilha de respostas
    public const PLANILHA_HORARIOS_COLUNA_NOME = 1; // B (coluna 0 (A) é a data da resposta)
    public const PLANILHA_HORARIOS_COLUNA_CPF = 2; // C
    public const PLANILHA_HORARIOS_COLUNA_CARTEIRA_DE_AMBULANCIA = 3; // D
    public const PLANILHA_HORARIOS_COLUNA_DIA_1 = 4; // E

    // Colunas da planilha PME
    public const PLANILHA_PME_COLUNA_NOMES = 'A';
    public const PLANILHA_PME_COLUNA_DIA_31 = 'AH';
    public const PLANILHA_PME_PRIMEIRA_LINHA_NOMES = 13;
    public const PLANILHA_HORARIOS_COLUNA_DATA_INITIAL = 'A2'; // A2 (primeira linha de dados)
    public const PLANILHA_HORARIOS_COLUNA_DATA_FINAL = 'AI102'; // Suporta até 100 respostas
    public const PLANILHA_PME_COLUNA_NOME = 0; // A
    public const PLANILHA_PME_COLUNA_CPF = 1; // B
    public const PLANILHA_PME_COLUNA_CARTEIRA_DE_AMBULANCIA = 2; // C
    public const PLANILHA_PME_COLUNA_DIA_1 = 3; // D
    
    // Turnos (duplicado da classe Disponibilidade para facilitar acesso)
    public const TURNO_NOTURNO = "NOTURNO";
    public const TURNO_INTEGRAL = "INTEGRAL";
    public const TURNO_DIURNO = "DIURNO";
    public const MEIA_COTA_EM_HORAS = 12;
    
    // Pontuações por cidade
    public const PONTUACAO_VIDEIRA = 100;
    public const PONTUACAO_OUTRAS_CIDADES = 0;
    
    // Pontuações por características
    public const PONTUACAO_CARTEIRA_AMBULANCIA = 50;
    public const PONTUACAO_QUERUBIN = 1000000;
    
    /**
     * Retorna um array com todas as cidades válidas
     */
    public static function getCidadesValidas(): array {
        return [
            self::CIDADE_FRAIBURGO,
            self::CIDADE_VIDEIRA,
            self::CIDADE_CACADOR
        ];
    }
    
    /**
     * Retorna um array com todos os turnos válidos
     */
    public static function getTurnosValidos(): array {
        return [
            self::TURNO_NOTURNO,
            self::TURNO_INTEGRAL,
            self::TURNO_DIURNO
        ];
    }
    
    /**
     * Verifica se uma cidade é válida
     */
    public static function isCidadeValida(string $cidade): bool {
        return in_array($cidade, self::getCidadesValidas());
    }
    
    /**
     * Retorna a pontuação para uma cidade específica
     */
    public static function getPontuacaoPorCidade(string $cidade): int {
        switch ($cidade) {
            case self::CIDADE_VIDEIRA:
                return self::PONTUACAO_VIDEIRA;
            case self::CIDADE_FRAIBURGO:
                return self::PONTUACAO_FRAIBURGO;
            case self::CIDADE_CACADOR:
                return self::PONTUACAO_CACADOR;
            default:
                return self::PONTUACAO_OUTRAS_CIDADES;
        }
    }
}
