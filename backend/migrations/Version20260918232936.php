<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260918232936 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Cria bombeiros, disponibilidades mensais e turnos atribuídos.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE bombeiro (
              id INT AUTO_INCREMENT NOT NULL,
              nome VARCHAR(150) NOT NULL,
              cpf VARCHAR(11) NOT NULL,
              antiguidade INT DEFAULT 0 NOT NULL,
              carteira_ambulancia TINYINT DEFAULT 0 NOT NULL,
              cidade_origem VARCHAR(50) DEFAULT NULL,
              UNIQUE INDEX uniq_bombeiro_cpf (cpf),
              PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE disponibilidade (
              id INT AUTO_INCREMENT NOT NULL,
              dia SMALLINT NOT NULL,
              mes SMALLINT NOT NULL,
              ano SMALLINT NOT NULL,
              turno VARCHAR(10) NOT NULL,
              bombeiro_id INT NOT NULL,
              INDEX IDX_44402DA8F31375D5 (bombeiro_id),
              UNIQUE INDEX uniq_disponibilidade_bombeiro_data (bombeiro_id, ano, mes, dia),
              PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE turno (
              id INT AUTO_INCREMENT NOT NULL,
              dia SMALLINT NOT NULL,
              mes SMALLINT NOT NULL,
              ano SMALLINT NOT NULL,
              turno VARCHAR(10) NOT NULL,
              turno_integral_decomposto TINYINT DEFAULT 0 NOT NULL,
              bombeiro_id INT NOT NULL,
              INDEX IDX_E7976762F31375D5 (bombeiro_id),
              UNIQUE INDEX uniq_turno_bombeiro_data (bombeiro_id, ano, mes, dia),
              PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              disponibilidade
            ADD
              CONSTRAINT FK_44402DA8F31375D5 FOREIGN KEY (bombeiro_id) REFERENCES bombeiro (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              turno
            ADD
              CONSTRAINT FK_E7976762F31375D5 FOREIGN KEY (bombeiro_id) REFERENCES bombeiro (id) ON DELETE CASCADE
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE disponibilidade DROP FOREIGN KEY FK_44402DA8F31375D5');
        $this->addSql('ALTER TABLE turno DROP FOREIGN KEY FK_E7976762F31375D5');
        $this->addSql('DROP TABLE bombeiro');
        $this->addSql('DROP TABLE disponibilidade');
        $this->addSql('DROP TABLE turno');
    }
}
