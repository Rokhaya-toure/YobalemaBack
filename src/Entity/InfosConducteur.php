<?php

namespace App\Entity;

use App\Enum\StatutValidation;
use App\Repository\InfosConducteurRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: InfosConducteurRepository::class)]
#[ORM\HasLifecycleCallbacks]
class InfosConducteur
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $numeropermis = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTime $dateemission = null;

    #[ORM\Column(length: 255)]
    private ?string $payededelivrance = null;

    #[ORM\OneToOne(cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $utilisateur = null;

    // ✅ NOUVEAUX CHAMPS pour la validation
    #[ORM\Column(type: 'string', enumType: StatutValidation::class)]
    private StatutValidation $statut = StatutValidation::EN_ATTENTE;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateValidation = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $validePar = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $motifRejet = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
        $this->statut = StatutValidation::EN_ATTENTE;
    }

    #[ORM\PreUpdate]
    public function setUpdatedAtValue(): void
    {
        $this->updatedAt = new \DateTime();
    }

    // ========== GETTERS & SETTERS EXISTANTS ==========
    
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNumeropermis(): ?string
    {
        return $this->numeropermis;
    }

    public function setNumeropermis(string $numeropermis): static
    {
        $this->numeropermis = $numeropermis;
        return $this;
    }

    public function getDateemission(): ?\DateTime
    {
        return $this->dateemission;
    }

    public function setDateemission(\DateTime $dateemission): static
    {
        $this->dateemission = $dateemission;
        return $this;
    }

    public function getPayededelivrance(): ?string
    {
        return $this->payededelivrance;
    }

    public function setPayededelivrance(string $payededelivrance): static
    {
        $this->payededelivrance = $payededelivrance;
        return $this;
    }

    public function getUtilisateur(): ?User
    {
        return $this->utilisateur;
    }

    public function setUtilisateur(?User $utilisateur): static
    {
        $this->utilisateur = $utilisateur;
        return $this;
    }

    // ========== NOUVEAUX GETTERS & SETTERS ==========

    public function getStatut(): StatutValidation
    {
        return $this->statut;
    }

    public function setStatut(StatutValidation $statut): static
    {
        $this->statut = $statut;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function getDateValidation(): ?\DateTimeInterface
    {
        return $this->dateValidation;
    }

    public function setDateValidation(?\DateTimeInterface $dateValidation): static
    {
        $this->dateValidation = $dateValidation;
        return $this;
    }

    public function getValidePar(): ?User
    {
        return $this->validePar;
    }

    public function setValidePar(?User $validePar): static
    {
        $this->validePar = $validePar;
        return $this;
    }

    public function getMotifRejet(): ?string
    {
        return $this->motifRejet;
    }

    public function setMotifRejet(?string $motifRejet): static
    {
        $this->motifRejet = $motifRejet;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeInterface $updatedAt): static
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    // ========== MÉTHODES UTILITAIRES ==========

    public function isEnAttente(): bool
    {
        return $this->statut === StatutValidation::EN_ATTENTE;
    }

    public function isValide(): bool
    {
        return $this->statut === StatutValidation::VALIDE;
    }

    public function isRejete(): bool
    {
        return $this->statut === StatutValidation::REJETE;
    }
}