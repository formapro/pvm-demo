<?php
namespace App\Controller;

use App\Service\GetExamplesService;
use Formapro\Pvm\ProcessBuilder;
use Formapro\Pvm\Visual\BuildDigraphScript;
use Formapro\Pvm\Visual\VisualizeFlow;
use function Makasim\Values\get_values;
use Symfony\Component\HttpFoundation\Response;

class ListController
{
    public function __invoke(\Twig_Environment $twig, GetExamplesService $getExamplesService): Response
    {
        $process = (new ProcessBuilder())
            ->createNode('Credit request')->end()
            ->createNode('Review request')->end()
            ->createNode('Assess risks')->end()
            ->createNode('Standard Terms Applicable')->end()
            ->createNode('Prepare special terms')->end()
            ->createNode('Calculate terms')->end()
            ->createNode('Prepare contract')->end()
            ->createNode('Send quote')->end()
            ->createNode('WaitForAll')->end()
            ->createNode('WaitForFirst')->end()
            ->createNode('Quote sent')->end()

            ->createStartTransition('Credit request')->end()
            ->createTransition('Credit request', 'Review request')->end()
            ->createTransition('Review request', 'Assess risks')->end()
            ->createTransition('Review request', 'Standard Terms Applicable')->end()
            ->createTransition('Standard Terms Applicable', 'Calculate terms', 'yes')->end()
            ->createTransition('Standard Terms Applicable', 'Prepare special terms', 'no')->end()
            ->createTransition( 'Prepare special terms', 'WaitForFirst')->end()
            ->createTransition( 'Calculate terms', 'WaitForFirst')->end()
            ->createTransition('WaitForFirst', 'Prepare contract')->end()
            ->createTransition(  'Prepare contract', 'WaitForAll')->end()
            ->createTransition(  'Assess risks', 'WaitForAll')->end()
            ->createTransition(   'WaitForAll', 'Send quote')->end()
            ->createTransition(   'Send quote', 'Quote sent')->end()

            ->getProcess()
        ;

        $graph = (new VisualizeFlow())->createGraph($process);
        $graph->setAttribute('graphviz.graph.ranksep', 0.1);
        $graph->setAttribute('alom.graphviz', array_replace($graph->getAttribute('alom.graphviz'), [
            'ranksep' => 0.2,
        ]));

        return new Response($twig->render('list.html.twig', [
            'examples' => $getExamplesService->getAll(),
            'pvmContext' => [
                'process' => get_values($process),
                'tokens' => [],
                'dot' => (new BuildDigraphScript())->build($graph)
            ]
        ]));
    }
}