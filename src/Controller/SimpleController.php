<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SimpleController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function home(): Response
    {
        return $this->render('simple/index.html.twig');
    }

    #[Route('/about-us', name: 'app_about')]
    public function about(): Response
    {
        return $this->render('simple/about.html.twig');
    }
}
