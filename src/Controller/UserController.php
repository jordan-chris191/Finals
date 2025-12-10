<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use App\Repository\UserRepository;
use App\Service\ActivityLogger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Routing\Attribute\Route;

#[IsGranted('ROLE_ADMIN')]
#[Route('/user')]
final class UserController extends AbstractController
{
    private ActivityLogger $logger;
    public function __construct(ActivityLogger $logger)
    {
        $this->logger = $logger;
    }
    #[Route('/', name: 'app_user_index', methods: ['GET'])]
    public function index(Request $request, UserRepository $userRepository): Response
    {
        $search = $request->query->get('search');
        $role = $request->query->get('role');
        $status = $request->query->get('status');

        $users = $userRepository->filterUsers($search, $role, $status);


        return $this->render('user/index.html.twig', [
            'users' => $users,
        ]);
    }

#[Route('/new', name: 'app_user_new', methods: ['GET', 'POST'])]
public function new(
    Request $request, 
    EntityManagerInterface $entityManager, 
    UserPasswordHasherInterface $passwordHasher
): Response 
{
    $user = new User();
    $form = $this->createForm(UserType::class, $user);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {

        // Hash password
        $plainPassword = $form->get('password')->getData();
        if (!$plainPassword) {
            $this->addFlash('error', 'Password is required for new users.');
            return $this->redirectToRoute('app_user_new');
        }
        $user->setPassword($passwordHasher->hashPassword($user, $plainPassword));

        $entityManager->persist($user);
        $entityManager->flush();

        // 🔥 Log activity
        $this->logger->log(
            'Create',
            'User: ' . $user->getUsername()
        );

        return $this->redirectToRoute('app_user_index');
    }

    return $this->render('user/new.html.twig', [
        'user' => $user,
        'form' => $form,
    ]);
}

    #[Route('/show/{id}', name: 'app_user_show', methods: ['GET'])]
    public function show(User $user): Response
    {

        return $this->render('user/show.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_user_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request, 
        User $user, 
        EntityManagerInterface $entityManager, 
        UserPasswordHasherInterface $passwordHasher
    ): Response 
    {
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            // Only update password if changed
            $plainPassword = $form->get('password')->getData();
            if ($plainPassword) {
                $user->setPassword($passwordHasher->hashPassword($user, $plainPassword));
            }

            $entityManager->flush();

            // 🔥 Log activity
            $this->logger->log(
                'Update',
                'User: ' . $user->getUsername()
            );

            return $this->redirectToRoute('app_user_index');
        }

        return $this->render('user/edit.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_user_delete', methods: ['POST'])]
    public function delete(
        Request $request, 
        User $user, 
        EntityManagerInterface $entityManager
    ): Response 
    {
        if ($this->isCsrfTokenValid('delete'.$user->getId(), $request->request->get('_token'))) {
            $username = $user->getUsername();

            $entityManager->remove($user);
            $entityManager->flush();

            // 🔥 Log delete action
            $this->logger->log(
                'Delete',
                'User: ' . $username
            );
        }

        return $this->redirectToRoute('app_user_index');
    }
}