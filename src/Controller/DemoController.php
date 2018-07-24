<?php
namespace App\Controller;

use App\Service\GetExamplesService;
use Formapro\Pvm\Process;
use Formapro\Pvm\ProcessEngine;
use Formapro\Pvm\Token;
use Formapro\Pvm\Visual\BuildDigraphScript;
use Formapro\Pvm\Visual\VisualizeFlow;
use function Makasim\Values\get_values;
use Symfony\Component\HttpFoundation\Response;

class DemoController
{
    public function __invoke(string $exampleName, \Twig_Environment $twig, GetExamplesService $getExamplesService): Response
    {
        $exampleName = str_replace(['.', '/'], '', $exampleName);

        $example = $getExamplesService->getOne($exampleName);
        $examples = $getExamplesService->getAll();

        $process = null;
        $engine = null;

        $exampleFile = $example->scriptFiles[0];

        ob_start();
        require_once $exampleFile;
        $output = ob_get_contents();
        ob_end_clean();

        if (false == $process instanceof Process) {
            throw new \LogicException('Process var is not defined');
        }
        $graph = (new VisualizeFlow())->createGraph($process);

        $rawTokens = [];
        if ($engine instanceof ProcessEngine) {
            $tokens = iterator_to_array($engine->getProcessTokens($process));
            foreach ($tokens as $token) {
                $rawTokens[] = get_values($token);
            }

            (new VisualizeFlow())->applyTokens($graph, $process, $tokens);
        }

        return new Response($twig->render('demo.html.twig', [
            'source' => highlight_file($exampleFile, true),
            'title' => ucwords(str_replace(['-', '_'], ' ', $exampleName)).' Example',
            'currentExample' => $example,
            'examples' => $examples,
            'output' => $output,
            'pvmContext' => [
                'process' => get_values($process),
                'tokens' => $rawTokens,
                'dot' => (new BuildDigraphScript())->build($graph)
            ]
        ]));
    }
}