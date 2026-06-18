<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller;

use App\Infrastructure\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[Route('/api/users')]
#[IsGranted('ROLE_ADMIN')] // 🔒 Capa 1: Solo los admins pueden entrar aquí
class UserController
{
    public function __construct(
        private UserRepository $userRepository,
        private EntityManagerInterface $em,
        private Security $security
    ) {}

    #[Route('', name: 'api_users_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        // Obtenemos al usuario que está haciendo la petición en este momento
        $currentUser = $this->security->getUser();
        
        /** @var User[] $allUsers */
        $allUsers = $this->userRepository->findAll();
        $data = [];

        foreach ($allUsers as $user) {
            // Filtro: Si el ID del usuario en el bucle coincide con el mío, lo salto
            if ($currentUser && $user->getId() === $currentUser->getId()) {
                continue;
            }

            $data[] = [
                'id' => (string) $user->getId(),
                'email' => $user->getEmail(),
                'roles' => $user->getRoles(),
                'lastLogin' => $user->getLastLogin()?->format('Y-m-d H:i:s'),
            ];
        }

        return new JsonResponse(['data' => $data], Response::HTTP_OK);
    }

    #[Route('/{id}', name: 'api_users_show', methods: ['GET'])]
    public function show(string $id): JsonResponse
    {
        $user = $this->userRepository->find($id);
        if (!$user) {
            return new JsonResponse(['error' => 'Usuario no encontrado'], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse(['data' => [
            'id' => (string) $user->getId(),
            'email' => $user->getEmail(),
            'roles' => $user->getRoles(),
        ]], Response::HTTP_OK);
    }

    #[Route('/{id}', name: 'api_users_update', methods: ['PUT'])]
    public function update(string $id, Request $request, UserPasswordHasherInterface $passwordHasher): JsonResponse
    {
        try {
            $user = $this->userRepository->find($id);
            if (!$user) {
                return new JsonResponse(['error' => 'Usuario no encontrado'], Response::HTTP_NOT_FOUND);
            }

            $payload = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);

            $user->setEmail($payload['email'] ?? $user->getEmail());
            
            // 🔒 Capa 2: Control estricto de roles
            if (isset($payload['roles']) && is_array($payload['roles'])) {
                $currentUser = $this->security->getUser();
                
                // Evitar que el admin se quite sus propios permisos por accidente
                if ($currentUser === $user && !in_array('ROLE_ADMIN', $payload['roles'], true)) {
                    return new JsonResponse(['error' => 'Seguridad: No puedes quitarte el rol de Administrador a ti mismo.'], Response::HTTP_FORBIDDEN);
                }

                $user->setRoles($payload['roles']);
            }

            if (!empty($payload['password'])) {
                $hashedPassword = $passwordHasher->hashPassword($user, $payload['password']);
                $user->setPassword($hashedPassword);
            }

            $this->em->flush();

            return new JsonResponse(['message' => 'Usuario actualizado con éxito'], Response::HTTP_OK);
        } catch (\Throwable $e) {
            return new JsonResponse(['error' => 'Error interno al actualizar: ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    #[Route('/api/profile', name: 'api_profile_show', methods: ['GET'])]
    public function showProfile(): JsonResponse
    {
        /** @var User $user */
        $user = $this->security->getUser(); 
        return new JsonResponse(['data' => [
            'id' => (string) $user->getId(),
            'email' => $user->getEmail(),
            'roles' => $user->getRoles(),
        ]], Response::HTTP_OK);
    }

    #[Route('/api/profile', name: 'api_profile_update', methods: ['PUT'])]
    public function updateProfile(Request $request, UserPasswordHasherInterface $passwordHasher): JsonResponse
    {
        /** @var User $user */
        $user = $this->security->getUser();
        
        try {
            $payload = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);

            // Solo permitimos cambiar email y contraseña en el perfil propio
            if (isset($payload['email'])) {
                $user->setEmail($payload['email']);
            }

            if (!empty($payload['password'])) {
                $hashedPassword = $passwordHasher->hashPassword($user, $payload['password']);
                $user->setPassword($hashedPassword);
            }

            $this->em->flush();

            return new JsonResponse(['message' => 'Perfil actualizado con éxito'], Response::HTTP_OK);
        } catch (\Throwable $e) {
            return new JsonResponse(['error' => 'Error al actualizar perfil: ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}