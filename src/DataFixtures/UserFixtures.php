<?php

namespace App\DataFixtures;

use App\Entity\User;
use App\Enum\UserRole;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture
{
    public function __construct(private UserPasswordHasherInterface $passwordHasher) {}

    public function load(ObjectManager $manager): void
    {
        // Utilisateur simple
        $user = new User();
        $user->setEmail('user@example.com');
        $user->setNom('Doe');
        $user->setPrenom('John');
        $user->setTelephone('7824248680');
        $user->setDateinscription(new \DateTime()); // date actuelle
        $user->setPassword($this->passwordHasher->hashPassword($user, 'password123'));
        $user->setRoles([UserRole::ROLE_USER->value]); // rôle utilisateur
        $manager->persist($user);

        // Conducteur (multi-roles)
        $conducteur = new User();
        $conducteur->setEmail('conducteur@example.com');
        $conducteur->setNom('Diop');
        $conducteur->setPrenom('Ali');
        $conducteur->setTelephone('770123456');
        $conducteur->setDateinscription(new \DateTime());
        $conducteur->setPassword($this->passwordHasher->hashPassword($conducteur, 'password123'));
        $conducteur->setRoles([ UserRole::ROLE_CONDUCTEUR->value]);
        $manager->persist($conducteur);

        // Administrateur
        $admin = new User();
        $admin->setEmail('admin@example.com');
        $admin->setNom('Sarr');
        $admin->setPrenom('Fatou');
        $admin->setTelephone('770987654');
        $admin->setDateinscription(new \DateTime());
        $admin->setPassword($this->passwordHasher->hashPassword($admin, 'admin123'));
        $admin->setRoles([UserRole::ROLE_ADMIN->value]);
        $manager->persist($admin);

        // Enregistrer en base
        $manager->flush();
    }
}
