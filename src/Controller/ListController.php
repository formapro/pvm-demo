<?php
namespace App\Controller;

use App\Service\GetExamplesService;
use Symfony\Component\HttpFoundation\Response;

class ListController
{
    public function __invoke(\Twig_Environment $twig, GetExamplesService $getExamplesService): Response
    {
        return new Response($twig->render('list.html.twig', [
            'examples' => $getExamplesService->getAll(),
        ]));
    }
}