<?php
namespace App\Controller;

use App\Service\CleanOldFileProcessesService;
use App\Service\GetExamplesService;
use Formapro\Pvm\Process;
use Formapro\Pvm\ProcessEngine;
use Formapro\Pvm\Visual\BuildDigraphScript;
use Formapro\Pvm\Visual\VisualizeFlow;
use function Makasim\Values\get_values;
use Symfony\Component\HttpFoundation\Response;

class DemoController
{
    public function __invoke(
        string $exampleName,
        \Twig_Environment $twig,
        GetExamplesService $getExamplesService,
        CleanOldFileProcessesService $cleanOldFileProcessesService
    ): Response {
        $exampleName = str_replace(['.', '/'], '', $exampleName);

        $example = $getExamplesService->getOne($exampleName);
        $examples = $getExamplesService->getAll();

        $cleanOldFileProcessesService->clean($example);

        $process = null;
        $engine = null;


        $scriptFiles = $example->scriptFiles;
        $firstScriptFile = array_shift($scriptFiles);

        ob_start();
        require_once $firstScriptFile;
        $outputs[basename($firstScriptFile)] = ob_get_contents();
        ob_end_clean();

        foreach ($scriptFiles as $name => $scriptFile) {
            $outputs[$name] = $this->executeScript($scriptFile);
        }

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
            'currentExample' => $example,
            'examples' => $examples,
            'outputs' => $outputs,
            'pvmContext' => [
                'process' => get_values($process),
                'tokens' => $rawTokens,
                'dot' => (new BuildDigraphScript())->build($graph)
            ]
        ]));
    }

    protected function executeScript(string $file): string
    {
        ob_start();
        require_once $file;
        $output = ob_get_contents();
        ob_end_clean();

        return $output;
    }
}