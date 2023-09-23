<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;


use App\Repository\OrderRepository;


class AnalyticsController extends AbstractController
{


    public function calculateTotalRevenueByClient(OrderRepository $orderRepository): array
    {
        // Récupérer toutes les commandes
        $orders = $orderRepository->findAll();

        // Initialiser une structure de données pour stocker le chiffre d'affaires par client
        $totalRevenueByClient = [];

        // Parcourir les commandes
        foreach ($orders as $order) {
            // Récupérer le client associé à la commande
            $client = $order->getDeliverTo();

            // Vérifier si le client existe
            if ($client) {
                $clientId = $client->getId();

                // Si le client n'existe pas dans la structure de données, l'initialiser
                if (!isset($totalRevenueByClient[$clientId])) {
                    $totalRevenueByClient[$clientId] = 0.0;
                }

                // Ajouter le chiffre d'affaires de la commande actuelle
                $totalRevenueByClient[$clientId] += $order->getAmount();
            }
        }

        // renvoyer le résulta
        return $totalRevenueByClient;
    }

    private function getRandomClients(array $clients, int $count): array
    {
        // Mélangez les clés du tableau associatif
        $shuffledKeys = array_keys($clients);
        shuffle($shuffledKeys);

        // Sélectionnez les 10 premières clés mélangées
        $randomKeys = array_slice($shuffledKeys, 0, $count);

        // Créez un tableau de clients aléatoires en utilisant les clés sélectionnées
        $randomClients = [];
        foreach ($randomKeys as $key) {
            $randomClients[$key] = $clients[$key];
        }

        return $randomClients;
    }


    #[Route('/analytics', name: 'app_analytics')]
    public function index(OrderRepository $orderRepository, Request $request): JsonResponse
    {
        // Appeler la méthode pour calculer le chiffre d'affaires par client
        $totalRevenueByClient = $this->calculateTotalRevenueByClient($orderRepository);

        // Convertir le tableau associatif en JSON
        $jsonData = json_encode($totalRevenueByClient);

        // Définir la valeur de la variable "quantile"
        $quantile = 0.025; // 2.5% par quantile

        // Trier les clients par chiffre d'affaires décroissant
        arsort($totalRevenueByClient);

        // Calculer la taille du premier quantile
        $totalClients = count($totalRevenueByClient);
        $quantileSize = (int) ($totalClients * $quantile);

        // Sélectionner les N clients du premier quantile
        $topCustomers = array_slice($totalRevenueByClient, 0, $quantileSize, true);
        $numberOfTopCustomers = count($topCustomers);

        // Créer la map "quantile par quantile"
        $quantiles = [];
        $currentQuantile = 1;
        $quantileRevenue = 0.0;
        $quantileCustomers = 0;

        foreach ($totalRevenueByClient as $clientId => $revenue) {
            if ($quantileCustomers < $quantileSize) {
                $quantileRevenue += $revenue;
                $quantileCustomers++;
            } else {
                // Enregistrez les informations du quantile actuel
                $quantiles["Quantile $currentQuantile"] = [
                    'Number of Customers' => $quantileCustomers,
                    'Max Revenue' => $quantileRevenue,
                ];

                // Réinitialisez les compteurs pour le prochain quantile
                $quantileRevenue = $revenue;
                $quantileCustomers = 1;
                $currentQuantile++;
            }
        }

        // Enregistrez le dernier quantile
        $quantiles["Quantile $currentQuantile"] = [
            'Number of Customers' => $quantileCustomers,
            'Max Revenue' => $quantileRevenue,
        ];

        // Créez un tableau contenant toutes les données à renvoyer
        $responseData = [
            'totalRevenueByClient' => $totalRevenueByClient,
            'topCustomers' => $topCustomers,
            'quantiles' => $quantiles,
        ];

        // Créez une réponse JSON
        return new JsonResponse($responseData);
    }
}
