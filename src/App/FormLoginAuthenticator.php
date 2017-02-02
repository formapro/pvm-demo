<?php
namespace App;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Guard\AbstractGuardAuthenticator;

class FormLoginAuthenticator extends AbstractGuardAuthenticator
{
    public function getCredentials(Request $request)
    {

    }

    public function getUser($credentials, UserProviderInterface $userProvider)
    {

    }

    public function checkCredentials($credentials, UserInterface $user)
    {

    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception)
    {

    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey)
    {

    }

    public function supportsRememberMe()
    {

    }

    public function start(Request $request, AuthenticationException $authException = null)
    {

    }
}
