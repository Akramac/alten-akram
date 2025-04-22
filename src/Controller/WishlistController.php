<?php

namespace App\Controller;

use App\Entity\Cart;
use App\Entity\CartItem;
use App\Entity\Wishlist;
use App\Entity\WishlistItem;
use App\Enum\InventoryStatus;
use App\Repository\CartRepository;
use App\Repository\ProductRepository;
use App\Repository\UserRepository;
use App\Repository\WishlistRepository;
use App\Service\ProductService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class WishlistController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private WishlistRepository $wishlistRepository;
    private UserRepository $userRepository;
    private ProductRepository $productRepository;

    public function __construct(EntityManagerInterface $entityManager, WishlistRepository $wishlistRepository, UserRepository $userRepository,ProductRepository $productRepository)
    {
        $this->entityManager = $entityManager;
        $this->wishlistRepository = $wishlistRepository;
        $this->userRepository = $userRepository;
        $this->productRepository = $productRepository;
    }

    #[Route('/wishlist', name: 'api_wishlist', methods: ['GET'])]
    public function getWishlist(Request $request): Response
    {
        $user = $this->getUser();
        $wishlist = $this->wishlistRepository->findOneBy(['user' => $user]);

        if (!$wishlist) {
            return new JsonResponse(['message' => 'Liste envie non trouvée'], Response::HTTP_NOT_FOUND);
        }

        $items = $wishlist->getWishlistItems();
        $wishlistData = [];

        foreach ($items as $item) {
            $product = $item->getProduct();
            $wishlistData[] = [
                'id' => $product->getId(),
                'name' => $product->getName(),
                'price' => $product->getPrice(),
            ];
        }

        return new JsonResponse($wishlistData, Response::HTTP_OK);
    }

    #[Route('/wishlist/add', name: 'api_wishlist_add', methods: ['POST'])]
    public function addToWishlist(Request $request): Response
    {
        $userInterface = $this->getUser();
        $user = $this->userRepository->find($userInterface?->getId());
        $wishlist = $this->wishlistRepository->findOneBy(['user' => $user]);
        if (!$user) {
            return new JsonResponse(['message' => 'Connectez vous!'], Response::HTTP_NOT_FOUND);
        }
        if (!$wishlist) {
            $wishlist = new Wishlist();
            $wishlist->setUser($user);
            $this->entityManager->persist($wishlist);
        }

        $data = json_decode($request->getContent(), true);
        if (!isset($data['productId'])) {
            return new JsonResponse(['message' => 'productId manquant'], Response::HTTP_BAD_REQUEST);
        }

        $product = $this->productRepository->find($data['productId']);

        if (!$product) {
            return new JsonResponse(['message' => 'Produit non trouvé'], Response::HTTP_NOT_FOUND);
        }

        $item = new WishlistItem();
        $item->setWishlist($wishlist);
        $item->setProduct($product);

        $wishlist->addWishlistItem($item);
        $this->entityManager->persist($item);
        $this->entityManager->flush();

        return new JsonResponse(['message' => 'Produit ajouté à la liste envie'], Response::HTTP_CREATED);
    }
    #[Route('/wishlist/remove', name: 'api_wishlist_remove', methods: ['POST'])]
    public function removeFromWishlist(Request $request): Response
    {
        $userInterface = $this->getUser();
        $user = $this->userRepository->find($userInterface?->getId());
        $wishlist = $this->wishlistRepository->findOneBy(['user' => $user]);

        if (!$user) {
            return new JsonResponse(['message' => 'Connectez vous!'], Response::HTTP_NOT_FOUND);
        }
        if (!$wishlist) {
            return new JsonResponse(['message' => 'Liste envie non trouvée'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);
        if (!isset($data['productId'])) {
            return new JsonResponse(['message' => 'productId manquant'], Response::HTTP_BAD_REQUEST);
        }
        $product = $this->productRepository->find($data['productId']);

        $item = $wishlist->getWishlistItems()->filter(function (WishlistItem $wishlistItem) use ($product) {
            return $wishlistItem->getProduct() === $product;
        })->first();

        if (!$item) {
            return new JsonResponse(['message' => 'Produit non trouvé dans la liste envie'], Response::HTTP_NOT_FOUND);
        }

        $wishlist->removeWishlistItem($item);
        $this->entityManager->remove($item);
        $this->entityManager->flush();

        return new JsonResponse(['message' => 'Produit supprimé de la liste envie'], Response::HTTP_OK);
    }
}
