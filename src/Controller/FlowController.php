<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;


use League\Csv\Writer;
use Psr\Log\LoggerInterface;


use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Item;
use App\Entity\Contact;
use App\Entity\Order;
use App\Entity\OrderLine;


class FlowController extends AbstractController
{

    private $entityManager;
    private $logger;

    public function __construct(EntityManagerInterface $entityManager, LoggerInterface $logger)
    {
        $this->entityManager = $entityManager;
        $this->logger = $logger;
    }

    #[Route('/flow', name: 'app_flow')]
    public function index(): JsonResponse
    {
        return $this->json([
            'message' => 'Welcome to your new controller!',
            'path' => 'src/Controller/FlowController.php',
        ]);
    }


    #[Route('orders', name: 'orders_list')]
    public function ordersListAction(LoggerInterface $logger): Response
    {
        // Afficher une page web décrivant les commandes déjà traitées par l'outil

        try {

            //Récupérez les données des commandes depuis l'API :
            $client = HttpClient::createForBaseUri('https://4ebb0152-1174-42f0-ba9b-4d6a69cf93be.mock.pstmn.io/', [
                'headers' => [
                    'x-api-key' => 'PMAK-62642462da39cd50e9ab4ea7-815e244f4fdea2d2075d8966cac3b7f10b',
                ],
            ]);

            $ordersData = $this->fetchOrdersFromAPI($client);

            return $this->render('flow/orders_list.html.twig', [
                'orders' => $ordersData, // Passer les données des commandes comme une variable 'orders'
            ]);
        } catch (\Exception $e) {
            return $this->handleError($e, $logger);
        }
    }

    #[Route('/flow/orders_to_csv', name: 'flow_orders_to_csv')]
    public function ordersToCsvAction(Request $request, LoggerInterface $logger): Response
    {
        try {
            $client = HttpClient::createForBaseUri('https://4ebb0152-1174-42f0-ba9b-4d6a69cf93be.mock.pstmn.io/', [
                'headers' => [
                    'x-api-key' => 'PMAK-62642462da39cd50e9ab4ea7-815e244f4fdea2d2075d8966cac3b7f10b',
                ],
            ]);

            $ordersData = $this->fetchOrdersFromAPI($client);
            $this->processOrders($ordersData, $logger);

            $contactsData = $this->fetchContactsFromAPI($client);
            $this->processContacts($contactsData, $logger);

            $csvContent = $this->generateCsvContent($ordersData);
            return $this->generateCsvResponse($csvContent);

            // Continue to Partie 2
        } catch (\Exception $e) {
            return $this->handleError($e, $logger);
        }
    }

    private function fetchOrdersFromAPI(HttpClientInterface $client): array
    {
        $response = $client->request('GET', 'orders');
        if ($response->getStatusCode() === 200) {
            return $response->toArray();
        } else {
            throw new \Exception('La requête de récupération des commandes a échoué : ' . $response->getStatusCode());
        }
    }

    // Traitement des données des commandes et enregistrement en base de données
    private function processOrders(array $ordersData, LoggerInterface $logger): void
    {
        foreach ($ordersData['results'] as $orderData) {
            // Créez une nouvelle entité Order et enregistrez les données
            $order = new Order();
            $order->setOrderId($orderData['OrderID']);
            $order->setOrderNumber($orderData['OrderNumber']);
            $order->setAmount($orderData['Amount']);
            $order->setCurrency($orderData['Currency']);
            $order->setOrderNumber($orderData['OrderNumber']);

            // Une relation ManyToOne avec Contact, donc vous devez obtenir l'objet Contact approprié en fonction de l'ID et le définir comme DeliverTo.
            $deliverToId = $orderData['DeliverTo'];
            $contact = $this->entityManager->getRepository(Contact::class)->findOneBy(['id' => $deliverToId]);
            $order->setDeliverTo($contact);

            // Parcourez les lignes de commande
            foreach ($orderData['SalesOrderLines']['results'] as $orderLineData) {
                // Créez une nouvelle entité OrderLine et enregistrez les données
                $orderLine = new OrderLine();
                $orderLine->setAmount($orderLineData['Amount']);
                $orderLine->setDescription($orderLineData['Description']);
                $orderLine->setDiscount($orderLineData['Discount']);
                $orderLine->setQuantity($orderLineData['Quantity']);
                $orderLine->setUnitCode($orderLineData['UnitCode']);
                $orderLine->setUnitDescription($orderLineData['UnitDescription']);
                $orderLine->setUnitPrice($orderLineData['UnitPrice']);
                $orderLine->setVatAmount($orderLineData['VATAmount']);
                $orderLine->setVatPercentage($orderLineData['VATPercentage']);

                // Ajoutez la relation avec l'entité Item
                $itemId = $orderLineData['Item'];
                $item = $this->entityManager->getRepository(Item::class)->find($itemId); // Supposons que vous utilisiez l'EntityManager
                if ($item) {
                    $orderLine->setItem($item);
                } else {
                    throw new \Exception('L\'item associé à cette ligne de commande n\'a pas été trouvé. ID de l\'item : ' . $itemId);
                }

                // Ajoutez la ligne de commande à l'entité Order
                $order->addSalesOrderLine($orderLine);
            }

            // Enregistrez l'entité Order en base de données
            $this->entityManager->persist($order);
        }

        // Enregistrez les modifications en base de données
        $this->entityManager->flush();
    }


    private function fetchContactsFromAPI(HttpClientInterface $client): array
    {
        $response = $client->request('GET', 'contacts');
        if ($response->getStatusCode() === 200) {
            return $response->toArray();
        } else {
            throw new \Exception('La requête de récupération des contacts a échoué : ' . $response->getStatusCode());
        }
    }

    // Traitement des données des contacts et enregistrement en base de données
    private function processContacts(array $contactsData, LoggerInterface $logger): void
    {
        foreach ($contactsData['results'] as $contactData) {
            // Créez une nouvelle entité Contact et enregistrez les données
            $contact = new Contact();
            $contact->setAccountName($contactData['AccountName']);
            $contact->setAddressLine1($contactData['AddressLine1']);
            $contact->setAddressLine2($contactData['AddressLine2']); // Prenez en compte AddressLine2
            $contact->setCity($contactData['City']);
            $contact->setContactName($contactData['ContactName']);
            $contact->setCountry($contactData['Country']);
            $contact->setZipCode($contactData['ZipCode']);

            // Enregistrez l'entité Contact en base de données
            $this->entityManager->persist($contact);
        }

        // Enregistrez les modifications en base de données
        $this->entityManager->flush();
    }

    // Génération du contenu CSV à partir des données des commandes
    private function generateCsvContent(array $ordersData): string
    {
        $csv = Writer::createFromString('');

        // Ajoutez l'en-tête CSV
        $csv->insertOne([
            'order',
            'delivery_name',
            'delivery_address',
            'delivery_country',
            'delivery_zipcode',
            'delivery_city',
            'items_count',
            'item_index',
            'item_id',
            'item_quantity',
            'line_price_excl_vat',
            'line_price_incl_vat',
        ]);

        // Parcourez les commandes et les articles pour ajouter les lignes au CSV
        foreach ($ordersData['results'] as $orderData) {
            $orderNumber = $orderData['OrderNumber'];
            $deliveryName = $orderData['DeliverTo']['ContactName'];
            $deliveryAddress = $orderData['DeliverTo']['AddressLine1'];
            $deliveryCountry = $orderData['DeliverTo']['Country'];
            $deliveryZipcode = $orderData['DeliverTo']['ZipCode'];
            $deliveryCity = $orderData['DeliverTo']['City'];
            $itemsCount = count($orderData['SalesOrderLines']['results']);

            foreach ($orderData['SalesOrderLines']['results'] as $itemIndex => $orderLineData) {
                $itemId = $orderLineData['Item'];
                $itemQuantity = $orderLineData['Quantity'];
                $linePriceExclVat = $orderLineData['Amount'];
                $linePriceInclVat = $orderLineData['Amount'] + $orderLineData['VATAmount'];

                // Ajoutez une ligne au CSV pour chaque article
                $csv->insertOne([
                    $orderNumber,
                    $deliveryName,
                    $deliveryAddress,
                    $deliveryCountry,
                    $deliveryZipcode,
                    $deliveryCity,
                    $itemsCount,
                    $itemIndex + 1,
                    $itemId,
                    $itemQuantity,
                    $linePriceExclVat,
                    $linePriceInclVat,
                ]);
            }
        }

        // Récupérez le contenu CSV
        $csvContent = $csv->getContent();

        return $csvContent;
    }


    private function generateCsvResponse(string $csvContent): Response
    {
        $response = new Response($csvContent);
        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="nouvelles_commandes.csv"');

        return $response;
    }

    private function handleError(\Exception $e, LoggerInterface $logger): Response
    {
        $errorMessage = 'Une erreur est survenue : ' . $e->getMessage();
        $this->addFlash('error', $errorMessage);
        $logger->error($errorMessage, ['exception' => $e]);
        throw new HttpException(500, $errorMessage);
    }
}
