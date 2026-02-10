<?php

namespace App\Entity;

use App\Enum\TrajetStatus;
use App\Repository\TrajetRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TrajetRepository::class)]
class Trajet
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $depart = null;

    #[ORM\Column(length: 255)]
    private ?string $arrivee = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTime $date = null;

    #[ORM\Column(type: Types::TIME_MUTABLE)]
    private ?\DateTime $heure = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $conducteur = null;

    #[ORM\Column(type: Types::FLOAT)]
    private ?float $departLat = null;

    #[ORM\Column(type: Types::FLOAT)]
    private ?float $departLng = null;

    #[ORM\Column(type: Types::FLOAT)]
    private ?float $arriveeLat = null;

    #[ORM\Column(type: Types::FLOAT)]
    private ?float $arriveeLng = null;

    #[ORM\Column(type: Types::FLOAT)]
    private ?float $prix = null;

    /**
     * @var Collection<int, Reservation>
     */
    #[ORM\OneToMany(targetEntity: Reservation::class, mappedBy: 'trajet')]
    private Collection $reservations;

    public function __construct()
    {
        $this->reservations = new ArrayCollection();
    }

    // ==============================
    // GETTERS ET SETTERS
    // ==============================

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDepart(): ?string
    {
        return $this->depart;
    }

    public function setDepart(string $depart): static
    {
        $this->depart = $depart;
        return $this;
    }

    public function getArrivee(): ?string
    {
        return $this->arrivee;
    }

    public function setArrivee(string $arrivee): static
    {
        $this->arrivee = $arrivee;
        return $this;
    }

    public function getDate(): ?\DateTime
    {
        return $this->date;
    }

    public function setDate(\DateTime $date): static
    {
        $this->date = $date;
        return $this;
    }

    public function getHeure(): ?\DateTime
    {
        return $this->heure;
    }

    public function setHeure(\DateTime $heure): static
    {
        $this->heure = $heure;
        return $this;
    }

    public function getConducteur(): ?User
    {
        return $this->conducteur;
    }

    public function setConducteur(?User $conducteur): static
    {
        $this->conducteur = $conducteur;
        return $this;
    }

    public function getDepartLat(): ?float
    {
        return $this->departLat;
    }

    public function setDepartLat(float $departLat): static
    {
        $this->departLat = $departLat;
        return $this;
    }

    public function getDepartLng(): ?float
    {
        return $this->departLng;
    }

    public function setDepartLng(float $departLng): static
    {
        $this->departLng = $departLng;
        return $this;
    }

    public function getArriveeLat(): ?float
    {
        return $this->arriveeLat;
    }

    public function setArriveeLat(float $arriveeLat): static
    {
        $this->arriveeLat = $arriveeLat;
        return $this;
    }

    public function getArriveeLng(): ?float
    {
        return $this->arriveeLng;
    }

    public function setArriveeLng(float $arriveeLng): static
    {
        $this->arriveeLng = $arriveeLng;
        return $this;
    }

    public function getPrix(): ?float
    {
        return $this->prix;
    }

    public function setPrix(float $prix): static
    {
        $this->prix = $prix;
        return $this;
    }

    /**
     * @return Collection<int, Reservation>
     */
    public function getReservations(): Collection
    {
        return $this->reservations;
    }

    public function addReservation(Reservation $reservation): static
    {
        if (!$this->reservations->contains($reservation)) {
            $this->reservations->add($reservation);
            $reservation->setTrajet($this);
        }

        return $this;
    }

    public function removeReservation(Reservation $reservation): static
    {
        if ($this->reservations->removeElement($reservation)) {
            // set the owning side to null (unless already changed)
            if ($reservation->getTrajet() === $this) {
                $reservation->setTrajet(null);
            }
        }

        return $this;
    }


    // Ajoutez ces propriétés dans l'entité Trajet


#[ORM\Column(nullable: false, options: ["default" => 4])]
private int $placesDisponibles = 4;

#[ORM\Column(type: Types::STRING, length: 50, options: ["default" => "disponible"])]
private string $statut = 'disponible';

public function getPlacesDisponibles(): int
{
    return $this->placesDisponibles;
}

public function setPlacesDisponibles(int $placesDisponibles): static
{
    $this->placesDisponibles = $placesDisponibles;
    return $this;
}

public function getStatut(): TrajetStatus
{
    return TrajetStatus::from($this->statut);
}

public function setStatut(TrajetStatus $statut): static
{
    $this->statut = $statut->value;
    return $this;
}
}
