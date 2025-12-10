<?php

namespace App\Controller;

use App\Entity\Product;
use App\Form\ProductType;
use App\Repository\ProductRepository;
use App\Service\ActivityLogger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/product')]
final class ProductController extends AbstractController
{
    private ActivityLogger $logger;
    private string $imageDirectory;

    public function __construct(ActivityLogger $logger, string $imageDirectory)
    {
        $this->logger = $logger;
        $this->imageDirectory = $imageDirectory;
    }

    #[Route(name: 'app_product_index', methods: ['GET'])]
    public function index(Request $request, ProductRepository $productRepository): Response
    {
        $search = $request->query->get('search');
        $minPrice = $request->query->get('min_price');
        $maxPrice = $request->query->get('max_price');

        $qb = $productRepository->createQueryBuilder('p');

        // Optional: staff sees only own products for edit/delete
        if ($this->isGranted('ROLE_STAFF')) {
            $qb->andWhere('p.createdBy = :user')
               ->setParameter('user', $this->getUser());
        }

        if ($search) {
            $qb->andWhere('p.name LIKE :search OR p.description LIKE :search')
               ->setParameter('search', '%'.$search.'%');
        }

        if ($minPrice !== null && $minPrice !== '') {
            $qb->andWhere('p.price >= :minPrice')
               ->setParameter('minPrice', $minPrice);
        }

        if ($maxPrice !== null && $maxPrice !== '') {
            $qb->andWhere('p.price <= :maxPrice')
               ->setParameter('maxPrice', $maxPrice);
        }

        $products = $qb->getQuery()->getResult();

        return $this->render('product/index.html.twig', [
            'products' => $products,
        ]);
    }

    #[Route('/new', name: 'app_product_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em, SluggerInterface $slugger): Response
    {
        $product = new Product();
        $product->setCreatedBy($this->getUser()); // Set ownership

        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('image')->getData();
            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();
                try {
                    $imageFile->move($this->imageDirectory, $newFilename);
                    $product->setImage($newFilename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'Failed to upload image.');
                }
            }

            $product->setCreatedAt(new \DateTime());
            $em->persist($product);
            $em->flush();

            $this->logger->log('Create', 'Product created: '.$product->getName().' (ID: '.$product->getId().')');

            return $this->redirectToRoute('app_product_index');
        }

        return $this->render('product/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/edit', name: 'app_product_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Product $product, EntityManagerInterface $em, SluggerInterface $slugger): Response
    {
        // Restrict staff to own products
        if ($this->isGranted('ROLE_STAFF') && $product->getCreatedBy() !== $this->getUser()) {
            $this->addFlash('error', 'You cannot edit this product.');
            return $this->redirectToRoute('app_product_index');
        }

        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('image')->getData();
            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();
                try {
                    $imageFile->move($this->imageDirectory, $newFilename);
                    $product->setImage($newFilename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'Failed to upload image.');
                }
            }

            $product->setUpdatedAt(new \DateTime());
            $em->flush();

            $this->logger->log('Update', 'Product updated: '.$product->getName().' (ID: '.$product->getId().')');

            return $this->redirectToRoute('app_product_index');
        }

        return $this->render('product/edit.html.twig', [
            'form' => $form->createView(),
            'product' => $product,
        ]);
    }

    #[Route('/{id}', name: 'app_product_delete', methods: ['POST'])]
    public function delete(Request $request, Product $product, EntityManagerInterface $em): Response
    {
        // Restrict staff to own products
        if ($this->isGranted('ROLE_STAFF') && $product->getCreatedBy() !== $this->getUser()) {
            $this->addFlash('error', 'You cannot delete this product.');
            return $this->redirectToRoute('app_product_index');
        }

        if ($this->isCsrfTokenValid('delete'.$product->getId(), $request->request->get('_token'))) {
            $em->remove($product);
            $em->flush();
            $this->logger->log('Delete', 'Product deleted: '.$product->getName().' (ID: '.$product->getId().')');
        }

        return $this->redirectToRoute('app_product_index');
    }

    #[Route('/{id}', name: 'app_product_show', methods: ['GET'])]
    public function show(Product $product): Response
    {
        return $this->render('product/show.html.twig', [
            'product' => $product,
        ]);
    }
}
