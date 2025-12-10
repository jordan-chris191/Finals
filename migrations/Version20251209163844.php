<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251209163844 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE activity_log DROP FOREIGN KEY FK_FD06F6479D86650F');
        $this->addSql('DROP INDEX IDX_FD06F6479D86650F ON activity_log');
        $this->addSql('ALTER TABLE activity_log CHANGE target_data target_data VARCHAR(255) DEFAULT NULL, CHANGE user_id_id user_id INT NOT NULL');
        $this->addSql('ALTER TABLE activity_log ADD CONSTRAINT FK_FD06F647A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_FD06F647A76ED395 ON activity_log (user_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE activity_log DROP FOREIGN KEY FK_FD06F647A76ED395');
        $this->addSql('DROP INDEX IDX_FD06F647A76ED395 ON activity_log');
        $this->addSql('ALTER TABLE activity_log CHANGE target_data target_data VARCHAR(255) NOT NULL, CHANGE user_id user_id_id INT NOT NULL');
        $this->addSql('ALTER TABLE activity_log ADD CONSTRAINT FK_FD06F6479D86650F FOREIGN KEY (user_id_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_FD06F6479D86650F ON activity_log (user_id_id)');
    }
}
