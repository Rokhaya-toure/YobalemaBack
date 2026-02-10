<?php

namespace App\Entity;

use App\Repository\MessageRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MessageRepository::class)]
class Message
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $contenue = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTime $date = null;

    #[ORM\ManyToOne(inversedBy: 'messages')]
    private ?user $utilisateur = null;

    #[ORM\ManyToOne(inversedBy: 'sendeur')]
    private ?user $sendeur = null;

    #[ORM\ManyToOne(inversedBy: 'receive')]
    private ?user $receive = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getContenue(): ?string
    {
        return $this->contenue;
    }

    public function setContenue(string $contenue): static
    {
        $this->contenue = $contenue;

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

    public function getSendeur(): ?user
    {
        return $this->sendeur;
    }

    public function setSendeur(?user $sendeur): static
    {
        $this->sendeur = $sendeur;

        return $this;
    }

    public function getReceive(): ?user
    {
        return $this->receive;
    }

    public function setReceive(?user $receive): static
    {
        $this->receive = $receive;

        return $this;
    }

  
    
}
