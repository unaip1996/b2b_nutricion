<?php

declare(strict_types=1);

namespace App\Infrastructure\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'users')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    private ?Uuid $id = null;

    #[ORM\Column(length: 180, unique: true)]
    private ?string $email = null;

    #[ORM\Column(type: 'json')]
    private array $roles = [];

    #[ORM\Column(length: 255)]
    private ?string $password = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $lastLogin = null;

    #[ORM\OneToOne(mappedBy: 'account', cascade: ['persist', 'remove'])]
    private ?NutritionistProfile $nutritionistProfile = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $deletedAt = null;

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Identificador visual principal del usuario para Symfony.
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // Garantiza que todos los usuarios tengan al menos este rol base
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Limpia cualquier dato temporal o sensible.
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // Si en el futuro guardas contraseñas en texto plano temporalmente, se borran aquí.
    }

    public function getLastLogin(): ?\DateTimeImmutable
    {
        return $this->lastLogin;
    }

    public function setLastLogin(?\DateTimeImmutable $lastLogin): self
    {
        $this->lastLogin = $lastLogin;

        return $this;
    }

    public function getNutritionistProfile(): ?NutritionistProfile
    {
        return $this->nutritionistProfile;
    }

    public function setNutritionistProfile(NutritionistProfile $nutritionistProfile): self
    {
        if ($nutritionistProfile->getAccount() !== $this) {
            $nutritionistProfile->setAccount($this);
        }

        $this->nutritionistProfile = $nutritionistProfile;

        return $this;
    }

    public function getDeletedAt(): ?\DateTimeImmutable
    {
        return $this->deletedAt;
    }

    public function setDeletedAt(?\DateTimeImmutable $deletedAt): self
    {
        $this->deletedAt = $deletedAt;

        return $this;
    }
}