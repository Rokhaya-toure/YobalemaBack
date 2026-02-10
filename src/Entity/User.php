<?php

namespace App\Entity;

use App\Enum\UserRole;
use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    private ?string $email = null;

    /**
     * @var list<string> The user roles
     */
    #[ORM\Column]
    private array $roles = [];
    

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column(length: 255)]
    private ?string $telephone = null;

    #[ORM\Column(length: 255)]
    private ?string $nom = null;

    #[ORM\Column(length: 255)]
    private ?string $prenom = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTime $dateinscription = null;

    /**
     * @var Collection<int, Avis>
     */
    #[ORM\OneToMany(targetEntity: Avis::class, mappedBy: 'utilisateur')]
    private Collection $avis;

    /**
     * @var Collection<int, Message>
     */
    #[ORM\OneToMany(targetEntity: Message::class, mappedBy: 'utilisateur')]
    private Collection $messages;

    /**
     * @var Collection<int, Message>
     */
    #[ORM\OneToMany(targetEntity: Message::class, mappedBy: 'sendeur')]
    private Collection $sendeur;

    /**
     * @var Collection<int, Message>
     */
    #[ORM\OneToMany(targetEntity: Message::class, mappedBy: 'receive')]
    private Collection $receive;

    /**
     * @var Collection<int, Reservation>
     */
    #[ORM\OneToMany(targetEntity: Reservation::class, mappedBy: 'utilisateur')]
    private Collection $reservations;

      // ✅ AJOUTER cette propriété
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $photo = null;

    /**
     * @var Collection<int, Notification>
     */
    #[ORM\OneToMany(targetEntity: Notification::class, mappedBy: 'relation')]
    private Collection $conducteur_id;

    // ✅ AJOUTER le getter
    public function getPhoto(): ?string
    {
        return $this->photo;
    }

    // ✅ AJOUTER le setter
    public function setPhoto(?string $photo): static
    {
        $this->photo = $photo;
        return $this;
    }

    public function __construct()
    {
        $this->avis = new ArrayCollection();
        $this->messages = new ArrayCollection();
        $this->sendeur = new ArrayCollection();
        $this->receive = new ArrayCollection();
        $this->reservations = new ArrayCollection();
        $this->conducteur_id = new ArrayCollection();
    }

   

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     */
    // public function getRoles(): array
    // {
    //     $roles = $this->roles;
    //     // guarantee every user at least has ROLE_USER
    //     $roles[] = 'ROLE_USER';

    //     return array_unique($roles);
    // }

// GETTER ET SETTER
    public function getRoles(): array
    {
        $roles = $this->roles;

        // garantir que chaque utilisateur a ROLE_USER
        if (!in_array(UserRole::ROLE_USER->value, $roles)) {
            $roles[] = UserRole::ROLE_USER->value;
        }

        return array_unique($roles);
    }


     // Méthode pratique pour ajouter un rôle
    public function addRole(UserRole $role): self
    {
        if (!in_array($role->value, $this->roles)) {
            $this->roles[] = $role->value;
        }
        return $this;
    }

     public function setRoles(array $roles): self
    {
        $this->roles = array_map(fn($role) => $role instanceof UserRole ? $role->value : $role, $roles);
        return $this;
    }

    /**
     * @param list<string> $roles
     */
    // public function setRoles(array $roles): static
    // {
    //     $this->roles = $roles;

    //     return $this;
    // }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    #[\Deprecated]
    public function eraseCredentials(): void
    {
        // @deprecated, to be removed when upgrading to Symfony 8
    }

     public function getUsername(): string {
        return $this->getUserIdentifier();
    }

     public function getTelephone(): ?string
     {
         return $this->telephone;
     }

     public function setTelephone(string $telephone): static
     {
         $this->telephone = $telephone;

         return $this;
     }

     public function getNom(): ?string
     {
         return $this->nom;
     }

     public function setNom(string $nom): static
     {
         $this->nom = $nom;

         return $this;
     }

     public function getPrenom(): ?string
     {
         return $this->prenom;
     }

     public function setPrenom(string $prenom): static
     {
         $this->prenom = $prenom;

         return $this;
     }

     public function getDateinscription(): ?\DateTime
     {
         return $this->dateinscription;
     }

     public function setDateinscription(\DateTime $dateinscription): static
     {
         $this->dateinscription = $dateinscription;

         return $this;
     }

     /**
      * @return Collection<int, Avis>
      */
     public function getAvis(): Collection
     {
         return $this->avis;
     }

     public function addAvi(Avis $avi): static
     {
         if (!$this->avis->contains($avi)) {
             $this->avis->add($avi);
             $avi->setUtilisateur($this);
         }

         return $this;
     }

     public function removeAvi(Avis $avi): static
     {
         if ($this->avis->removeElement($avi)) {
             // set the owning side to null (unless already changed)
             if ($avi->getUtilisateur() === $this) {
                 $avi->setUtilisateur(null);
             }
         }

         return $this;
     }

     /**
      * @return Collection<int, Message>
      */
     public function getMessages(): Collection
     {
         return $this->messages;
     }

     /**
      * @return Collection<int, Message>
      */
     public function getSendeur(): Collection
     {
         return $this->sendeur;
     }

     public function addSendeur(Message $sendeur): static
     {
         if (!$this->sendeur->contains($sendeur)) {
             $this->sendeur->add($sendeur);
             $sendeur->setSendeur($this);
         }

         return $this;
     }

     public function removeSendeur(Message $sendeur): static
     {
         if ($this->sendeur->removeElement($sendeur)) {
             // set the owning side to null (unless already changed)
             if ($sendeur->getSendeur() === $this) {
                 $sendeur->setSendeur(null);
             }
         }

         return $this;
     }

     /**
      * @return Collection<int, Message>
      */
     public function getReceive(): Collection
     {
         return $this->receive;
     }

     public function addReceive(Message $receive): static
     {
         if (!$this->receive->contains($receive)) {
             $this->receive->add($receive);
             $receive->setReceive($this);
         }

         return $this;
     }

     public function removeReceive(Message $receive): static
     {
         if ($this->receive->removeElement($receive)) {
             // set the owning side to null (unless already changed)
             if ($receive->getReceive() === $this) {
                 $receive->setReceive(null);
             }
         }

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
             $reservation->setUtilisateur($this);
         }

         return $this;
     }

     public function removeReservation(Reservation $reservation): static
     {
         if ($this->reservations->removeElement($reservation)) {
             // set the owning side to null (unless already changed)
             if ($reservation->getUtilisateur() === $this) {
                 $reservation->setUtilisateur(null);
             }
         }

         return $this;
     }

     /**
      * @return Collection<int, Notification>
      */
     public function getConducteurId(): Collection
     {
         return $this->conducteur_id;
     }



    
}

