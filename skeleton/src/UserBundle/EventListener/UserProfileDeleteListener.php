<?php

namespace UserBundle\EventListener;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use UserBundle\Entity\UserProfile;

class UserProfileDeleteListener
{
    /**
     * @return void
     */
    public function preRemove(LifecycleEventArgs $event)
    {
        $entity = $event->getEntity();

        if (!$entity instanceof UserProfile) {
            return;
        }

        // Prevent deletion of the admin profile (ID = 1)
        if (1 === $entity->getId()) {
            throw new AccessDeniedHttpException('O perfil de usuário admin não pode ser removido.');
        }
    }
}
