<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    #[Route('/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_keywords_finder');
        }

        return $this->render('security/login.html.twig', [
            'error'         => $authenticationUtils->getLastAuthenticationError(),
            'last_username' => $authenticationUtils->getLastUsername(),
        ]);
    }

    #[Route('/register', name: 'app_register')]
    public function register(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $em,
    ): Response {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_keywords_finder');
        }

        $error = null;

        if ($request->isMethod('POST')) {
            $email    = trim((string) $request->request->get('email', ''));
            $password = (string) $request->request->get('password', '');

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error = 'Adresse email invalide.';
            } elseif (strlen($password) < 8) {
                $error = 'Le mot de passe doit contenir au moins 8 caractères.';
            } elseif ($em->getRepository(User::class)->findOneBy(['email' => $email])) {
                $error = 'Un compte existe déjà avec cet email.';
            } else {
                $user = new User();
                $user->setEmail($email);
                $user->setPassword($passwordHasher->hashPassword($user, $password));
                $em->persist($user);
                $em->flush();

                return $this->redirectToRoute('app_login');
            }
        }

        return $this->render('security/register.html.twig', ['error' => $error]);
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This should never be reached.');
    }
}
