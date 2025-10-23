<?php

namespace UserBundle\EventListener;

use Doctrine\ORM\Event\PreUpdateEventArgs;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use UserBundle\Entity\User;
use UserBundle\Entity\UserProfile;
use UserBundle\Entity\UserProfileRoles;

class UserProfileChangeListener
{
    /**
     * @return void
     */
    public function preUpdate(PreUpdateEventArgs $event)
    {
        $entity = $event->getEntity();

        if (!$entity instanceof User) {
            return;
        }

        if (!$event->hasChangedField('profile')) {
            return;
        }

        $currentProfile = $event->getOldValue('profile');
        $newProfile = $event->getNewValue('profile');

        // If the user is an admin, prevent downgrading their profile
        if ($entity->isAdmin() && $currentProfile !== $newProfile) {
            if ($this->hasFewerPermissions($newProfile, $currentProfile)) {
                throw new AccessDeniedHttpException('Administradores não podem atribuir um perfil de usuário com nível de permissão inferior.');
            }
        }
    }

    /**
     * @param UserProfile $newProfile
     * @param UserProfile $currentProfile
     */
    private function hasFewerPermissions($newProfile, $currentProfile): bool
    {
        $currentLevel = 0;
        $newLevel = 0;

        $currentRoles = $currentProfile->getRoles();
        $newRoles = $newProfile->getRoles();

        /** @var UserProfileRoles $role */
        foreach ($currentRoles as $role) {
            $currentLevel += $role->getAction();
        }

        /** @var UserProfileRoles $role */
        foreach ($newRoles as $role) {
            $newLevel += $role->getAction();
        }

        return $newLevel < $currentLevel || \count($newRoles) < \count($currentRoles);
    }
}
