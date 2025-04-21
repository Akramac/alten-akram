<?php

namespace App\Service;

use Symfony\Component\Security\Core\User\UserInterface;

class AuthorizationService
{
    public function isAdmin(?UserInterface $user): bool
    {
        return $user && $user->getEmail() === 'admin@admin.com';
    }
}