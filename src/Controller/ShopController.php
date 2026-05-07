<?php
namespace App\Controller;

use App\Entity\CartItem;
use App\Repository\ProductRepository;
use App\Repository\CategoryRepository;
use App\Repository\CartItemRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ShopController extends AbstractController
{
    /**
     * @Route("/", name="app_home")
     */
    public function index(ProductRepository $productRepo): Response
    {
        $products = $productRepo->findAll();
        return $this->render('shop/index.html.twig', ['products' => $products]);
    }

    /**
     * @Route("/categories", name="app_categories")
     */
    public function categories(CategoryRepository $categoryRepo): Response
    {
        $categories = $categoryRepo->findAll();
        return $this->render('shop/browse_categories.html.twig', ['categories' => $categories]);
    }

    /**
     * @Route("/product/{id}", name="app_product_details")
     */
    public function productDetails(int $id, ProductRepository $productRepo): Response
    {
        $product = $productRepo->find($id);
        if (!$product) {
            throw $this->createNotFoundException('Produit non trouvé');
        }
        return $this->render('shop/product_details.html.twig', ['product' => $product]);
    }

    /**
     * @Route("/cart", name="app_cart")
     */
    public function cart(CartItemRepository $cartRepo): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        $items = $cartRepo->findBy(['user' => $this->getUser()]);
        $total = 0;
        foreach ($items as $item) {
            $total += $item->getProduct()->getPrice() * $item->getQuantity();
        }
        return $this->render('shop/cart.html.twig', ['items' => $items, 'total' => $total]);
    }

    /**
     * @Route("/cart/add/{id}", name="app_cart_add", methods={"POST"})
     */
    public function addToCart(int $id, ProductRepository $productRepo, CartItemRepository $cartRepo, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        $product = $productRepo->find($id);
        if (!$product) {
            throw $this->createNotFoundException();
        }
        $existingItem = $cartRepo->findOneBy(['product' => $product, 'user' => $this->getUser()]);
        if ($existingItem) {
            $existingItem->setQuantity($existingItem->getQuantity() + 1);
        } else {
            $cartItem = new CartItem();
            $cartItem->setProduct($product);
            $cartItem->setUser($this->getUser());
            $cartItem->setQuantity(1);
            $em->persist($cartItem);
        }
        $em->flush();
        $this->addFlash('success', 'Produit ajouté au panier !');
        return $this->redirectToRoute('app_product_details', ['id' => $id]);
    }

    /**
     * @Route("/cart/remove/{id}", name="app_cart_remove")
     */
    public function removeFromCart(int $id, CartItemRepository $cartRepo, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        $item = $cartRepo->find($id);
        if ($item && $item->getUser() === $this->getUser()) {
            $em->remove($item);
            $em->flush();
        }
        return $this->redirectToRoute('app_cart');
    }

    /**
     * @Route("/products/category/{id}", name="app_products_by_category")
     */
    public function productsByCategory(int $id, CategoryRepository $categoryRepo, ProductRepository $productRepo): Response
    {
        $category = $categoryRepo->find($id);
        $products = $productRepo->findBy(['category' => $category]);
        return $this->render('shop/products_by_category.html.twig', ['category' => $category, 'products' => $products]);
    }

    /**
     * @Route("/profile", name="app_profile")
     */
    public function profile(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        return $this->render('shop/profile.html.twig');
    }
}
