<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Psr\Log\LoggerInterface;

use App\Exception\ApiRequestException;

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


    #[Route('/flow/orders_to_csv', name: 'flow_orders_to_csv')]
    public function ordersToCsvAction(Request $request, LoggerInterface $logger): Response
    {

        // 1) Récupérer les nouvelles commandes depuis l'API et les stocker en base de données -------------------------

        // Configurez le client HTTP avec l'API key
        $client = HttpClient::createForBaseUri('https://4ebb0152-1174-42f0-ba9b-4d6a69cf93be.mock.pstmn.io/', [
            'headers' => [
                'x-api-key' => 'PMAK-62642462da39cd50e9ab4ea7-815e244f4fdea2d2075d8966cac3b7f10b',
            ],
        ]);


        try {

            // Effectuez la requête GET pour récupérer les commandes ----------------------
            $response = $client->request('GET', 'orders');

            if ($response->getStatusCode() === 200) {
                $ordersData = $response->toArray();

                // Parcourez les données des commandes
                foreach ($ordersData['results'] as $orderData) {
                    // Créez une nouvelle entité Order et enregistrez les données
                    $order = new Order();
                    $order->setOrderId($orderData['OrderID']);
                    $order->setOrderNumber($orderData['OrderNumber']);
                    $order->setAmount($orderData['Amount']);
                    $order->setCurrency($orderData['Currency']);
                    $order->setOrderNumber($orderData['OrderNumber']);

                    //une relation ManyToOne avec Contact, donc vous devez obtenir l'objet Contact approprié en fonction de l'ID et le définir comme DeliverTo.
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
            } else {
                $errorMessage = 'La requête d\'orders a échoué : ' . $response->getStatusCode();
                $this->addFlash('error', 'Impossible de récupérer les commandes depuis l\'API.');
                //throw new ApiRequestException($errorMessage, $response->getStatusCode()); // Lancez une exception personnalisée avec le code d'erreur API
                $logger->error($errorMessage);
                throw new HttpException(500, $errorMessage);
            }
        } catch (\Exception $e) {
            // Gérez les exceptions génériques ici
            $errorMessage = 'Une erreur est survenue lors de la récupération des commandes.';
            $this->addFlash('error', $errorMessage);
            // Journalisez l'erreur
            $logger->error($errorMessage, ['exception' => $e]);
            // Lancez une exception HTTP pour indiquer une erreur de serveur
            throw new HttpException(500, $errorMessage);
        }

        try {
            // Effectuez la requête GET pour récupérer les contacts --------------------------
            $response = $client->request('GET', 'contacts');

            if ($response->getStatusCode() === 200) {
                $contactsData = $response->toArray();

                // Parcourez les données des contacts
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
            } else {
                $errorMessage = 'La requête de contact a échoué : ' . $response->getStatusCode();
                $this->addFlash('error', 'Impossible de récupérer les contacts depuis l\'API.');
                // Journalisez l'erreur
                $logger->error($errorMessage);
                // Lancez une exception HTTP pour indiquer une erreur de serveur
                throw new HttpException(500, $errorMessage); //throw new ApiRequestException($errorMessage, $response->getStatusCode()); // Lancez une exception personnalisée avec le code d'erreur API
            }
        } catch (\Exception $e) {
            // Gérez les exceptions génériques ici
            $errorMessage = 'Une erreur est survenue lors de la récupération des contacts.';
            $this->addFlash('error', $errorMessage);
            // Journalisez l'erreur
            $logger->error($errorMessage, ['exception' => $e]);
            // Lancez une exception HTTP pour indiquer une erreur de serveur
            throw new HttpException(500, $errorMessage);
        }

        
        // 2) Générer un fichier CSV à partir des nouvelles commandes ---------------------------------------------

        $csvData = ""; // Initialisez la variable avec une chaîne vide au début

        $response = new Response($csvData);
        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="nouvelles_commandes.csv"');

        return $response;
    }

    #[Route('orders', name: 'orders_list')]
    public function ordersListAction(): Response
    {
        // Afficher une page web décrivant les commandes déjà traitées par l'outil

        return $this->render('flow/orders_list.html.twig');
    }
}
