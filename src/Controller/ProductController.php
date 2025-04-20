<?php

namespace App\Controller;

use App\Enum\InventoryStatus;
use App\Service\ProductService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/products')]
class ProductController extends AbstractController
{
    private ProductService $productService;

    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }

    #[Route('', name: 'api_products', methods: ['POST','GET'])]
    public function createProduct(Request $request,SerializerInterface $serializer): Response
    {
        if ($request->isMethod('POST')) {
            $data = json_decode($request->getContent(), true);
            $requiredFields = ['code', 'name', 'description', 'image', 'category', 'price', 'quantity', 'internalReference', 'shellId', 'inventoryStatus', 'rating'];
            // Check missing fields
            foreach ($requiredFields as $field) {
                if (!isset($data[$field])) {
                    return new JsonResponse(['message' => "Champ manquant: $field"], Response::HTTP_BAD_REQUEST);
                }
            }
            if (isset($data['inventoryStatus']) && in_array($data['inventoryStatus'], [InventoryStatus::INSTOCK->value, InventoryStatus::LOWSTOCK->value, InventoryStatus::OUTOFSTOCK->value])) {
                $product = $this->productService->createProduct($data);
            } else {
                return new JsonResponse(['message' => 'inventory status incorrect'], Response::HTTP_BAD_REQUEST);
            }
            if ($product) {
                return new JsonResponse(['message' => 'Produit ajouté'], Response::HTTP_OK);
            } else {
                return new JsonResponse(['message' => 'Erreur lors de l\'ajout'], Response::HTTP_INTERNAL_SERVER_ERROR);
            }
        } elseif ($request->isMethod('GET')) {
            // Handle GET request to get all products
            $products = $this->productService->getProducts();

            if ($products) {
                $jsonProducts = $serializer->serialize($products, 'json', ['groups' => 'produits']);
                return new Response($jsonProducts, Response::HTTP_OK, ['Content-Type' => 'application/json']);
            } else {
                return new JsonResponse(['message' => 'Pas de produits trouvés'], Response::HTTP_NOT_FOUND);
            }
        }

        return new JsonResponse(['message' => 'Methode non prise en compte'], Response::HTTP_METHOD_NOT_ALLOWED);
    }

    #[Route('/{id}', name: 'api_product_get_one', methods: ['GET'])]
    public function getProductById(int $id,SerializerInterface $serializer): Response
    {
        $product = $this->productService->getProductById($id);
        if (!$product) {
            return new JsonResponse(['message' => 'Produit non trouvé'], Response::HTTP_NOT_FOUND);
        }
        $jsonProduct = $serializer->serialize($product, 'json', ['groups' => 'produits']);
        return new Response($jsonProduct, Response::HTTP_OK, ['Content-Type' => 'application/json']);
    }

    #[Route('/{id}', name: 'api_product_patch', methods: ['PATCH'])]
    public function  patchProductById(int $id, Request $request, SerializerInterface $serializer): Response
    {
        $data = json_decode($request->getContent(), true);

        $requiredFields = ['code', 'name', 'description', 'image', 'category', 'price', 'quantity', 'internalReference', 'shellId', 'inventoryStatus', 'rating'];
        // Check field exist
        $atLeastOneFieldPresent = false;
        foreach ($requiredFields as $field) {
            if (isset($data[$field])) {
                $atLeastOneFieldPresent = true;
                break;
            }
        }
        if (!$atLeastOneFieldPresent) {
            return new JsonResponse(['message' => 'Au moins un champ doit etre valide'], Response::HTTP_BAD_REQUEST);
        }

        $product = $this->productService->updateProduct($id, $data);
        if (!$product) {
            return new JsonResponse(['message' => 'Produit non trouvé'], Response::HTTP_NOT_FOUND);
        }
        $jsonProduct = $serializer->serialize($product, 'json', ['groups' => 'produits']);
        return new Response($jsonProduct, Response::HTTP_OK, ['Content-Type' => 'application/json']);
    }

    #[Route('/{id}', name: 'api_product_delete', methods: ['DELETE'])]
    public function  deleteProductById(int $id, SerializerInterface $serializer): Response
    {
        $success = $this->productService->deleteProduct($id);
        if (!$success) {
            return new JsonResponse(['message' => 'Produit non trouvé'], Response::HTTP_NOT_FOUND);
        }
        return new JsonResponse(['message' => 'Produit supprimé'], Response::HTTP_OK);

    }

}
