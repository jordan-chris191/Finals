<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[IsGranted('ROLE_ADMIN')]
final class AdminViewProfileController extends AbstractController
{
    #[Route('/admin/view/profile', name: 'app_admin_view_profile')]
    public function index(): Response
    {
        return $this->render('admin_view_profile/index.html.twig', [
            'controller_name' => 'AdminViewProfileController',
        ]);
    }
}
