<?php

namespace App\Constants;

class CbmscConstants {
    // Cidades
    public const CIDADE_FRAIBURGO = "Fraiburgo";
    public const CIDADE_VIDEIRA = "Videira";
    public const CIDADE_CACADOR = "Caçador";
    
    // CPFs especiais
    public const CPF_DO_QUERUBIN = 10010010001; // CPF do Querubin

    // Colunas da planilha de respostas
    public const PLANILHA_HORARIOS_COLUNA_NOME = 1; // B (coluna 0 (A) é a data da resposta)
    public const PLANILHA_HORARIOS_COLUNA_CPF = 2; // C
    public const PLANILHA_HORARIOS_COLUNA_CARTEIRA_DE_AMBULANCIA = 3; // D
    public const PLANILHA_HORARIOS_COLUNA_DIA_1 = 4; // E
    public const PLANILHA_HORARIOS_PRIMEIRA_LINHA_NOMES = 13;
    public const PLANILHA_HORARIOS_COLUNA_NOMES = 'A';
    public const PLANILHA_HORARIOS_COLUNA_DIA_31 = 'AH';

    // Colunas da planilha PME
    public const PLANILHA_PME_COLUNA_NOME = 0; // A
    public const PLANILHA_PME_COLUNA_CPF = 1; // B
    public const PLANILHA_PME_COLUNA_CARTEIRA_DE_AMBULANCIA = 2; // C
    public const PLANILHA_PME_COLUNA_DIA_1 = 3; // D
    
    // Turnos (duplicado da classe Disponibilidade para facilitar acesso)
    public const TURNO_NOTURNO = "NOTURNO";
    public const TURNO_INTEGRAL = "INTEGRAL";
    public const TURNO_DIURNO = "DIURNO";
    
    // Pontuações por cidade
    public const PONTUACAO_VIDEIRA = 20;
    public const PONTUACAO_FRAIBURGO = 15;
    public const PONTUACAO_CACADOR = 10;
    public const PONTUACAO_OUTRAS_CIDADES = 5;
    
    // Pontuações por características
    public const PONTUACAO_CARTEIRA_AMBULANCIA = 10;
    public const PONTUACAO_QUERUBIN = 1000;
    
    // Pontuações por antiguidade
    public const PONTUACAO_ANTIGUIDADE_MENOS_2_ANOS = 5;
    public const PONTUACAO_ANTIGUIDADE_2_A_5_ANOS = 10;
    public const PONTUACAO_ANTIGUIDADE_5_ANOS_OU_MAIS = 15;
    public const PONTUACAO_ANTIGUIDADE_MENOS_10_ANOS = 20;
    
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
