<?php

namespace UserBundle\Service;

use AppBundle\Entity\Migration\Migration;
use AppBundle\Service\FeatureService;

class UserProfileManagerService
{
    public function __construct(
        private readonly string $adminProfilesId,
    ) {
    }

    public function insertRole(Migration $migration, string $feature): void
    {
        if (empty($this->adminProfilesId)) {
            throw new \InvalidArgumentException('A Váriavel APP_ADMIN_PROFILES_IDS não pode ser vazia');
        }

        if (!\preg_match('/^\d+(,\d+)*$/', $this->adminProfilesId)) {
            throw new \InvalidArgumentException('A Váriavel APP_ADMIN_PROFILES_IDS de ter o seguinte padrão: 50 ou 10,50,100');
        }

        if (empty(FeatureService::getValue($feature))) {
            throw new \InvalidArgumentException('Feature não configurada em: AppBundle\Service\FeatureService');
        }

        $profiles = \explode(',', $this->adminProfilesId);
        foreach ($profiles as $profile) {
            $migration->addSql("INSERT INTO db_sistema.perfil_usuario_permissao
				(
					perfil_usuario_permissao_id,
				 	perfil_usuario_permissao_functionality_id,
				 	perfil_usuario_permissao_profile_id,
				 	perfil_usuario_permissao_action
				) VALUES (
					nextval('db_sistema.perfil_usuario_permissao_perfil_usuario_permissao_id_seq'),
				    (SELECT func_id FROM db_sistema.funcionalidade WHERE funcionalidade_alias = ?),
				    ?,
				    4
				)
			", [$feature, $profile]);
        }
    }

    public function deleteRole(Migration $migration, string $feature): void
    {
        if (empty(FeatureService::getValue($feature))) {
            throw new \InvalidArgumentException('Feature não configurada em: AppBundle\Service\FeatureService');
        }

        $migration->addSql('
			DELETE FROM db_sistema.perfil_usuario_permissao
			WHERE perfil_usuario_permissao_functionality_id = (
				SELECT func_id FROM db_sistema.funcionalidade WHERE funcionalidade_alias = ?
			);
		', [$feature]);
    }
}
