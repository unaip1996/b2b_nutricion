<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller;

use App\Infrastructure\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserController extends AbstractController
{
    public function __construct(
        private UserRepository $userRepository,
        private EntityManagerInterface $em
    ) {}

    #[Route('/api/users', name: 'api_users_create', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function create(Request $request, UserPasswordHasherInterface $passwordHasher): JsonResponse
    {
        try {
            $payload = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);

            if (empty($payload['email']) || empty($payload['password'])) {
                return new JsonResponse(['error' => 'El correo electrónico y la contraseña son obligatorios.'], Response::HTTP_BAD_REQUEST);
            }

            // Evitar duplicados
            $existingUser = $this->userRepository->findOneBy(['email' => $payload['email']]);
            if ($existingUser) {
                return new JsonResponse(['error' => 'El correo electrónico ya está registrado.'], Response::HTTP_CONFLICT);
            }

            $user = new User();
            $user->setEmail($payload['email']);
            
            $roles = $payload['roles'] ?? ['ROLE_USER'];
            if (!in_array('ROLE_USER', $roles, true)) {
                $roles[] = 'ROLE_USER';
            }
            $user->setRoles($roles);

            // Hashear contraseña descodificada
            $hashedPassword = $passwordHasher->hashPassword($user, $payload['password']);
            $user->setPassword($hashedPassword);

            // ✨ LÓGICA DE NEGOCIO: Asociación automática de perfil profesional
            if (in_array('ROLE_NUTRITIONIST', $roles, true)) {
                $profile = new \App\Infrastructure\Entity\NutritionistProfile();
                $this->em->persist($profile);
                $user->setNutritionistProfile($profile);
            }

            $this->em->persist($user);
            $this->em->flush();

            return new JsonResponse([
                'message' => 'Usuario creado con éxito',
                'id' => (string) $user->getId()
            ], Response::HTTP_CREATED);

        } catch (\Throwable $e) {
            return new JsonResponse(['error' => 'Error interno al crear usuario: ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Endpoint exclusivo para que cualquier usuario autenticado obtenga su propio perfil.
     * Al estar en '/api/profile' evitamos por completo la colisión con el validador UUID de '/api/users/{id}'
     */
    #[Route('/api/profile', name: 'api_profile_show', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function showProfile(): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Usuario no autenticado'], Response::HTTP_UNAUTHORIZED);
        }

        return new JsonResponse(['data' => [
            'id' => (string) $user->getId(),
            'email' => $user->getEmail(),
            'roles' => $user->getRoles(),
        ]], Response::HTTP_OK);
    }

    /**
     * Endpoint exclusivo para que cualquier usuario modifique sus propias credenciales.
     */
    #[Route('/api/profile', name: 'api_profile_update', methods: ['PUT'])]
    #[IsGranted('ROLE_USER')]
    public function updateProfile(Request $request, UserPasswordHasherInterface $passwordHasher): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Usuario no autenticado'], Response::HTTP_UNAUTHORIZED);
        }

        try {
            $payload = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
            
            $user->setEmail($payload['email'] ?? $user->getEmail());

            if (!empty($payload['password'])) {
                $hashedPassword = $passwordHasher->hashPassword($user, $payload['password']);
                $user->setPassword($hashedPassword);
            }

            $this->em->flush();
            return new JsonResponse(['message' => 'Perfil actualizado con éxito'], Response::HTTP_OK);
        } catch (\Throwable $e) {
            return new JsonResponse(['error' => 'Error al actualizar el perfil: ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // =========================================================================
    // ENDPOINTS DE ADMINISTRACIÓN (Protegidos con ROLE_ADMIN)
    // =========================================================================

    #[Route('/api/users', name: 'api_users_list', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function list(): JsonResponse
    {
        $currentUser = $this->getUser();
        /** @var User[] $allUsers */
        $allUsers = $this->userRepository->findAll();
        $data = [];

        foreach ($allUsers as $user) {
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

    #[Route('/api/users/{id}', name: 'api_users_show', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
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

    #[Route('/api/users/{id}', name: 'api_users_update', methods: ['PUT'])]
    #[IsGranted('ROLE_ADMIN')]
    public function update(string $id, Request $request, UserPasswordHasherInterface $passwordHasher): JsonResponse
    {
        try {
            $user = $this->userRepository->find($id);
            if (!$user) {
                return new JsonResponse(['error' => 'Usuario no encontrado'], Response::HTTP_NOT_FOUND);
            }

            $payload = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
            $user->setEmail($payload['email'] ?? $user->getEmail());
            
            if (isset($payload['roles']) && is_array($payload['roles'])) {
                $currentUser = $this->getUser();
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
}