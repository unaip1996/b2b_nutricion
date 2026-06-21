# Reglas de Infraestructura: Doctrine y PostgreSQL

1. **Ubicación:** Las entidades Doctrine van en `src/Infrastructure/Entity/`.
2. **Atributos:** Usa solo atributos PHP 8 (`#[ORM\Entity]`, `#[ORM\Column]`). Nada de anotaciones, YAML o XML.
3. **Identificadores (UUID):** Todos los IDs deben ser UUID.
   ```php
   #[ORM\Id]
   #[ORM\Column(type: UuidType::NAME, unique: true)]
   #[ORM\GeneratedValue(strategy: 'CUSTOM')]
   #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
   private ?Uuid $id = null;
4. **Soft Delete (Auditoría Médica):**
   - TODAS las entidades deben incluir este campo anulable y sus getter/setter:
     ```php
     #[ORM\Column(nullable: true)]
     private ?\DateTimeImmutable $deletedAt = null;
     ```
5. **Relaciones: Colecciones: Inicializa siempre las relaciones OneToMany y ManyToMany en el constructor usando new ArrayCollection();.

6. **Repositorios: Implementan las interfaces (Puertos) definidas en el Dominio.