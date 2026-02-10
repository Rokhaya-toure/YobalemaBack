<?php

namespace App\Entity;
use App\Enum\ReservationStatus;

use App\Repository\ReservationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ReservationRepository::class)]
class Reservation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'reservations')]
    private ?User $utilisateur = null;

    #[ORM\ManyToOne(inversedBy: 'reservations')]
    private ?Trajet $trajet = null;

    #[ORM\Column]
    private ?int $place = null;


 #[ORM\Column(type: Types::STRING, length: 50)]
private string $statut = 'en_attente';

public function getStatut(): ReservationStatus
{
    return ReservationStatus::from($this->statut);
}

public function setStatut(ReservationStatus $statut): static
{
    $this->statut = $statut->value;
    return $this;
}



    public function getId(): ?int
    {
        return $this->id;
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

    public function getTrajet(): ?Trajet
    {
        return $this->trajet;
    }

    public function setTrajet(?Trajet $trajet): static
    {
        $this->trajet = $trajet;

        return $this;
    }

    public function getPlace(): ?int
    {
        return $this->place;
    }

    public function setPlace(int $place): static
    {
        $this->place = $place;

        return $this;
    }
}
