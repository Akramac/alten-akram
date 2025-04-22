<?php

namespace App\Controller;

use App\Entity\Cart;
use App\Entity\CartItem;
use App\Enum\InventoryStatus;
use App\Repository\CartRepository;
use App\Repository\ProductRepository;
use App\Repository\UserRepository;
use App\Service\ProductService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class CartController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private CartRepository $cartRepository;
    private UserRepository $userRepository;
    private ProductRepository $productRepository;

    public function __construct(EntityManagerInterface $entityManager, CartRepository $cartRepository, UserRepository $userRepository,ProductRepository $productRepository)
    {
        $this->entityManager = $entityManager;
        $this->cartRepository = $cartRepository;
        $this->userRepository = $userRepository;
        $this->productRepository = $productRepository;
    }

    #[Route('/cart', name: 'api_cart', methods: ['GET'])]
    public function getCart(Request $request): Response
    {
        $user = $this->getUser();
        $cart = $this->cartRepository->findOneBy(['user' => $user]);

        if (!$cart) {
            return new JsonResponse(['message' => 'Panier non trouvé'], Response::HTTP_NOT_FOUND);
        }

        $items = $cart->getCartItems();
        $cartData = [];

        foreach ($items as $item) {
            $product = $item->getProduct();
            $cartData[] = [
                'id' => $product->getId(),
                'name' => $product->getName(),
                'price' => $product->getPrice(),
                'quantity' => $item->getQuantity(),
            ];
        }

        return new JsonResponse($cartData, Response::HTTP_OK);
    }

    #[Route('/cart/add', name: 'api_cart_add', methods: ['POST'])]
    public function addToCart(Request $request): Response
    {
        $userInterface = $this->getUser();
        $user = $this->userRepository->find($userInterface?->getId());
        $cart = $this->cartRepository->findOneBy(['user' => $user]);
        if (!$user) {
            return new JsonResponse(['message' => 'Connectez vous!'], Response::HTTP_NOT_FOUND);
        }
        if (!$cart) {
            $cart = new Cart();
            $cart->setUser($user);
            $this->entityManager->persist($cart);
        }

        $data = json_decode($request->getContent(), true);
        if (!isset($data['productId'])) {
            return new JsonResponse(['message' => 'productId manquant'], Response::HTTP_BAD_REQUEST);
        }
        if (!isset($data['quantity'])) {
            return new JsonResponse(['message' => 'quantity manquant'], Response::HTTP_BAD_REQUEST);
        }

        $product = $this->productRepository->find($data['productId']);

        if (!$product) {
            return new JsonResponse(['message' => 'Produit non trouvé'], Response::HTTP_NOT_FOUND);
        }

        $item = new CartItem();
        $item->setCart($cart);
        $item->setProduct($product);
        $item->setQuantity($data['quantity']);

        $cart->addCartItem($item);
        $this->entityManager->persist($item);
        $this->entityManager->flush();

        return new JsonResponse(['message' => 'Produit ajouté '], Response::HTTP_CREATED);
    }
    #[Route('/cart/remove', name: 'api_cart_remove', methods: ['POST'])]
    public function removeFromCart(Request $request): Response
    {
        $userInterface = $this->getUser();
        $user = $this->userRepository->find($userInterface?->getId());
        $cart = $this->cartRepository->findOneBy(['user' => $user]);

        if (!$user) {
            return new JsonResponse(['message' => 'Connectez vous!'], Response::HTTP_NOT_FOUND);
        }
        if (!$cart) {
            return new JsonResponse(['message' => 'Panier non trouvé'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);
        if (!isset($data['productId'])) {
            return new JsonResponse(['message' => 'productId manquant'], Response::HTTP_BAD_REQUEST);
        }
        $product = $this->productRepository->find($data['productId']);

        $item = $cart->getCartItems()->filter(function (CartItem $cartItem) use ($product) {
            return $cartItem->getProduct() === $product;
        })->first();

        if (!$item) {
            return new JsonResponse(['message' => 'Produit non trouvé dans le panier'], Response::HTTP_NOT_FOUND);
        }

        $cart->removeCartItem($item);
        $this->entityManager->remove($item);
        $this->entityManager->flush();

        return new JsonResponse(['message' => 'Produit supprimé du panier'], Response::HTTP_OK);
    }
}
