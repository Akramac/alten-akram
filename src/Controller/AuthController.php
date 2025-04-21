<?php

namespace App\Controller;


use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\Serializer\SerializerInterface;

class AuthController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private UserPasswordHasherInterface $passwordEncoder;
    private JWTTokenManagerInterface $jwtManager;

    public function __construct(EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordEncoder, JWTTokenManagerInterface $jwtManager)
    {
        $this->entityManager = $entityManager;
        $this->passwordEncoder = $passwordEncoder;
        $this->jwtManager = $jwtManager;
    }

    #[Route('/account', name: 'api_user_account', methods: ['POST'])]
    public function createAccount(Request $request,ValidatorInterface $validator): Response
    {
        $data = json_decode($request->getContent(), true);

        $requiredFields = ['username', 'firstname', 'email', 'password'];
        // Check missing fields
        foreach ($requiredFields as $field) {
            if (!isset($data[$field])) {
                return new JsonResponse(['message' => "Champ manquant: $field"], Response::HTTP_BAD_REQUEST);
            }
        }
        $emailConstraint = new Email();
        $emailErrors = $validator->validate($data['email'], $emailConstraint);
        if (count($emailErrors) > 0) {
            return new JsonResponse(['message' => (string) $emailErrors], Response::HTTP_BAD_REQUEST);
        }

        $user = new User();
        $user->setUsername($data['username']);
        $user->setFirstname($data['firstname']);
        $user->setEmail($data['email']);
        $user->setPlainPassword($data['password']);

        // Encode the password
        $encodedPassword = $this->passwordEncoder->hashPassword($user, $user->getPlainPassword());
        $user->setPassword($encodedPassword);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return new JsonResponse(['message' => 'Utilisateur crée'], Response::HTTP_CREATED);
    }

    #[Route('/token', name: 'api_user_token', methods: ['POST'])]
    public function getToken(Request $request, UserRepository $userRepository, SerializerInterface $serializer): Response
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['email']) || !isset($data['password'])) {
            return new JsonResponse(['message' => 'Email ou password manquant'], Response::HTTP_BAD_REQUEST);
        }

        $user = $userRepository->findOneBy(['email' => $data['email']]);
        if (!$user) {
            return new JsonResponse(['message' => 'Utilisateur non trouvé'], Response::HTTP_NOT_FOUND);
        }

        if (!$this->passwordEncoder->isPasswordValid($user, $data['password'])) {
            return new JsonResponse(['message' => 'Mot de passe incorrect'], Response::HTTP_UNAUTHORIZED);
        }

        // Generate the token
        $token = $this->jwtManager->create($user);

        return new JsonResponse(['token' => $token]);
    }
}
