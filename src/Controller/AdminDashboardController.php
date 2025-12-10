<?php

namespace App\Controller;

use App\Repository\ActivityLogRepository;
use App\Repository\UserRepository;
use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Routing\Attribute\Route;

#[IsGranted('ROLE_ADMIN')]
final class AdminDashboardController extends AbstractController
{
    #[Route('/admin/dashboard', name: 'app_admin_dashboard')]
    public function index(
        UserRepository $userRepository,
        ProductRepository $productRepository,
        ActivityLogRepository $activityLogRepository
    ): Response {
        $totalUsers = $userRepository->count([]);

        $totalStaff = $userRepository->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->where('u.roles LIKE :role')
            ->setParameter('role', '%"ROLE_STAFF"%')
            ->getQuery()
            ->getSingleScalarResult();

        $totalProducts = $productRepository->count([]);

         $recentLogs = $activityLogRepository->findBy(
            [],
            ['createdAt' => 'DESC'],
            5
        );
        return $this->render('admin_dashboard/index.html.twig', [
            'totalUsers'    => $totalUsers,
            'totalStaff'    => $totalStaff,
            'totalProducts' => $totalProducts,
            'recentLogs' => $recentLogs
        ]);
    }
}