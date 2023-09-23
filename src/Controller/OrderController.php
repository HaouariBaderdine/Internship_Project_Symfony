<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Psr\Log\LoggerInterface;

use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Item;
use App\Entity\Contact;
use App\Entity\Order;
use App\Entity\OrderLine;

class OrderController extends AbstractController
{


    #[Route('/orders', name: 'orders_list')]
    public function ordersListAction(EntityManagerInterface $entityManager, LoggerInterface $logger): Response
    {
        // Récupérer la liste des commandes depuis la base de données
        $orderRepository = $entityManager->getRepository(Order::class);
        $orders = $orderRepository->findAll(); // Vous pouvez utiliser findAll() ou une autre méthode de recherche selon vos besoins.

        // Afficher une page web décrivant les commandes déjà traitées par l'outil
        try {
            return $this->render('flow/orders_list.html.twig', [
                'orders' => $orders, // Passer les données des commandes comme une variable 'orders'
            ]);
        } catch (\Exception $e) {
            return $this->handleError($e, $logger);
        }
    }
    
    /*
    #[Route('/orders', name: 'orders_list')]
    public function ordersListAction(Request $request, LoggerInterface $logger): Response
    {
        $orderNumber = $request->query->get('orderNumber');
        $currency = $request->query->get('currency');

        // Récupérer la liste des commandes depuis la base de données
        $entityManager = $this->getDoctrine()->getManager();
        $orderRepository = $entityManager->getRepository(Order::class);

        // Vous pouvez ajouter des conditions de recherche en fonction des critères
        $queryBuilder = $orderRepository->createQueryBuilder('o');

        if ($orderNumber) {
            $queryBuilder->andWhere('o.orderNumber = :orderNumber')
                ->setParameter('orderNumber', $orderNumber);
        }

        if ($currency) {
            $queryBuilder->andWhere('o.currency = :currency')
                ->setParameter('currency', $currency);
        }

        // Exécutez la requête
        $filteredOrders = $queryBuilder->getQuery()->getResult();

        // Afficher une page web décrivant les commandes déjà traitées par l'outil
        try {
            return $this->render('flow/orders_list.html.twig', [
                'orders' => $filteredOrders, // Passer les données des commandes comme une variable 'orders'
            ]);
        } catch (\Exception $e) {
            return $this->handleError($e, $logger);
        }
    }

*/

    private function handleError(\Exception $e, LoggerInterface $logger): Response
    {
        $errorMessage = 'Une erreur est survenue : ' . $e->getMessage();
        $this->addFlash('error', $errorMessage);
        $logger->error($errorMessage, ['exception' => $e]);
        throw new HttpException(500, $errorMessage);
    }
}
