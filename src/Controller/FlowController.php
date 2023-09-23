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
    private $itemCache = [];

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


    #[Route('/flow/orders_to_csv', name: 'flow_orders_to_csv')]
    public function ordersToCsvAction(Request $request, LoggerInterface $logger): Response
    {
        try {

            $client = HttpClient::createForBaseUri('http://localhost:3000/api/results/');

            /*
            $client = HttpClient::createForBaseUri('https://4ebb0152-1174-42f0-ba9b-4d6a69cf93be.mock.pstmn.io/', [
                'headers' => [
                    'x-api-key' => 'PMAK-62642462da39cd50e9ab4ea7-815e244f4fdea2d2075d8966cac3b7f10b',
                ],
            ]);
            */


            $contactsData = $this->fetchContactsFromAPI($client);
            $this->processContacts($contactsData, $logger);

            $ordersData = $this->fetchOrdersFromAPI($client);
            $this->processOrders($ordersData, $logger);

            $csvContent = $this->generateCsvContent($ordersData);
            return $this->generateCsvResponse($csvContent);
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
            // Vérifiez d'abord si une commande avec le même ID existe
            $existingOrder = $this->entityManager->getRepository(Order::class)->find($orderData['OrderID']);

            if ($existingOrder) {
                // La commande existe déjà, journalisez un message ou effectuez d'autres actions nécessaires
                $logger->info('La commande avec ID ' . $orderData['OrderID'] . ' existe déjà.');
                continue; // Passez à la commande suivante
            }

            // Créez une nouvelle entité Order et enregistrez les données
            $order = new Order();
            $order->setId($orderData['OrderID']);
            $order->setOrderNumber($orderData['OrderNumber']);
            $order->setAmount($orderData['Amount']);
            $order->setCurrency($orderData['Currency']);

            // Une relation ManyToOne avec Contact, donc vous devez obtenir l'objet Contact approprié en fonction de l'ID et le définir comme DeliverTo.
            $deliverToId = $orderData['DeliverTo'];
            $contact = $this->entityManager->getRepository(Contact::class)->find($deliverToId);

            if (!$contact) {
                // Le contact associé n'existe pas, journalisez un message ou effectuez d'autres actions nécessaires
                $logger->error('Le contact avec ID ' . $deliverToId . ' n\'existe pas.');
                continue; // Passez à la commande suivante
            }

            $order->setDeliverTo($contact);

            // Créez un tableau pour stocker les lignes de commande liées à cette commande
            $orderLines = [];

            // Parcourez les lignes de commande
            foreach ($orderData['SalesOrderLines']['results'] as $orderLineData) {
                // Vérifiez si l'Item existe
                $itemId = $orderLineData['Item'];

                if (!isset($this->itemCache[$itemId])) {
                    $item = $this->entityManager->getRepository(Item::class)->find($itemId);

                    if (!$item) {
                        // L'élément (Item) n'existe pas, créez et enregistrez-le d'abord
                        $item = new Item();
                        $item->setId($itemId);
                        $item->setItemDescription($orderLineData['ItemDescription']);

                        $this->entityManager->persist($item);
                        $this->entityManager->flush(); // Enregistrez l'item dans une transaction distincte

                        // Vous pouvez maintenant l'ajouter au cache
                        $this->itemCache[$itemId] = $item;
                    } else {
                        // L'item existe, ajoutez-le au cache
                        $this->itemCache[$itemId] = $item;
                    }
                }

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

                // Associez l'Item à la OrderLine (utilisez le cache pour éviter une nouvelle recherche)
                $orderLine->setItem($this->itemCache[$itemId]);

                // Associez la OrderLine à l'Order
                $orderLine->setOrder($order);

                // Ajoutez la OrderLine au tableau
                $orderLines[] = $orderLine;
            }

            // Associez les OrderLines à la Order
            $order->setSalesOrderLine($orderLines);

            // Enregistrez l'entité Order en base de données
            $this->entityManager->persist($order);
            $this->entityManager->flush(); // Enregistrez la commande dans une transaction distincte
        }
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

    private function processContacts(array $contactsData, LoggerInterface $logger): void
    {
        foreach ($contactsData['results'] as $contactData) {
            // Vérifiez d'abord si un contact avec le même ID existe
            $existingContact = $this->entityManager->getRepository(Contact::class)->find($contactData['ID']);

            if ($existingContact) {
                // Le contact existe déjà, journalisez un message ou effectuez d'autres actions nécessaires
                $logger->info('Le contact avec ID ' . $contactData['ID'] . ' existe déjà.');
                continue; // Passez au contact suivant
            }

            // Créez une nouvelle entité Contact et enregistrez les données
            $contact = new Contact();
            $contact->setId($contactData['ID']);
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

            // Récupérez le contact en utilisant l'ID de contact
            $contactId = $orderData['DeliverTo'];
            $contact = $this->entityManager->getRepository(Contact::class)->find($contactId);

            if (!$contact) {
                // Le contact associé n'existe pas, journaliser un message ou effectuer d'autres actions nécessaires
                continue; // Passez à la commande suivante
            }

            $deliveryName = $contact->getContactName();
            $deliveryAddress = $contact->getAddressLine1();
            $deliveryCountry = $contact->getCountry();
            $deliveryZipcode = $contact->getZipCode();
            $deliveryCity = $contact->getCity();
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


    
    #[Route('flow/orders', name: 'flow_orders_list')]
    public function ordersListAction(LoggerInterface $logger): Response
    {

        // Afficher une page web décrivant les commandes de l'api
        try {

            //Récupérez les données des commandes depuis l'API :
            $client = HttpClient::createForBaseUri('http://localhost:3000/api/results/');

            $ordersData = $this->fetchOrdersFromAPI($client);


            return $this->render('flow/flow_orders_list.html.twig', [
                'orders' => $ordersData, // Passer les données des commandes comme une variable 'orders'
            ]);
        } catch (\Exception $e) {
            return $this->handleError($e, $logger);
        }
    }

}
