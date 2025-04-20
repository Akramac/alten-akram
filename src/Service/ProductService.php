<?php

namespace App\Service;

use App\Entity\Product;
use App\Enum\InventoryStatus;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;

class ProductService
{
    private ProductRepository $productRepository;
    private EntityManagerInterface $entityManager;

    public function __construct(ProductRepository $productRepository, EntityManagerInterface $entityManager)
    {
    $this->productRepository = $productRepository;
    $this->entityManager = $entityManager;
    }

    public function createProduct(array $data): Product
    {
    $product = new Product();
    $product->setCode($data['code']);
    $product->setName($data['name']);
    $product->setDescription($data['description']);
    $product->setImage($data['image']);
    $product->setCategory($data['category']);
    $product->setPrice($data['price']);
    $product->setQuantity($data['quantity']);
    $product->setInternalReference($data['internalReference']);
    $product->setShellId($data['shellId']);
    $product->setInventoryStatus(InventoryStatus::from($data['inventoryStatus']));
    $product->setRating($data['rating']);
    $product->setCreatedAt(new \DateTimeImmutable('now'));
    $product->setUpdatedAt(new \DateTimeImmutable('now'));

    $this->entityManager->persist($product);
    $this->entityManager->flush();

    return $product;
    }

    public function getProducts(): array
    {
    return $this->productRepository->findAll();
    }

    public function getProductById(int $id): ?Product
    {
    return $this->productRepository->find($id);
    }

    public function updateProduct(int $id, array $data): ?Product
    {
    $product = $this->productRepository->find($id);
    if (!$product) {
        return null;
    }

    if (isset($data['code'])) $product->setCode($data['code']);
    if (isset($data['name'])) $product->setName($data['name']);
    if (isset($data['description'])) $product->setDescription($data['description']);
    if (isset($data['image'])) $product->setImage($data['image']);
    if (isset($data['category'])) $product->setCategory($data['category']);
    if (isset($data['price'])) $product->setPrice($data['price']);
    if (isset($data['quantity'])) $product->setQuantity($data['quantity']);
    if (isset($data['internalReference'])) $product->setInternalReference($data['internalReference']);
    if (isset($data['shellId'])) $product->setShellId($data['shellId']);
    if (isset($data['inventoryStatus'])) $product->setInventoryStatus(InventoryStatus::from($data['inventoryStatus']));
    if (isset($data['rating'])) $product->setRating($data['rating']);
    $product->setUpdatedAt(new \DateTimeImmutable('now'));

    $this->entityManager->flush();

    return $product;
    }

    public function deleteProduct(int $id): bool
    {
    $product = $this->productRepository->find($id);
    if (!$product) {
    return false;
    }

    $this->entityManager->remove($product);
    $this->entityManager->flush();

    return true;
    }
}