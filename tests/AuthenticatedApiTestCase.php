<?php

declare(strict_types=1);

namespace App\Tests;

use App\Infrastructure\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\BrowserKit\AbstractBrowser;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

abstract class AuthenticatedApiTestCase extends WebTestCase
{
    protected ?EntityManagerInterface $em;

    protected function tearDown(): void
    {
        parent::tearDown();
        if (isset($this->em) && $this->em->isOpen()) {
            $this->em->close();
            $this->em = null;
        }
    }

    protected function createAuthenticatedClient(string $email = 'test@user.com', string $password = 'password', array $roles = ['ROLE_USER']): AbstractBrowser
    {
        $client = static::createClient();
        $this->em = $client->getContainer()->get(EntityManagerInterface::class);
        
        // Crear usuario de prueba
        $user = $this->em->getRepository(User::class)->findOneBy(['email' => $email]);
        if (!$user) {
            $user = new User();
            $user->setEmail($email);
            $user->setRoles($roles);

            /** @var UserPasswordHasherInterface $passwordHasher */
            $passwordHasher = $client->getContainer()->get(UserPasswordHasherInterface::class);
            $user->setPassword($passwordHasher->hashPassword($user, $password));

            // Si el usuario es un nutricionista, creamos y asociamos su perfil profesional
            // para que los tests que dependen de ello (ej. listar pacientes) funcionen.
            if (in_array('ROLE_NUTRITIONIST', $roles, true)) {
                $profile = new \App\Infrastructure\Entity\NutritionistProfile();
                $this->em->persist($profile);
                $user->setNutritionistProfile($profile);
            }

            $this->em->persist($user);
            $this->em->flush();
        }

        // Autenticar y obtener token JWT
        $client->request(
            'POST',
            '/api/login_check',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['username' => $email, 'password' => $password])
        );

        $data = json_decode($client->getResponse()->getContent(), true);
        $client->setServerParameter('HTTP_Authorization', sprintf('Bearer %s', $data['token']));

        return $client;
    }
}