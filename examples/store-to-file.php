<?php
// store-to-file.php

use Formapro\Pvm\DefaultBehaviorRegistry;
use Formapro\Pvm\Process;
use Formapro\Pvm\ProcessEngine;
use Formapro\Pvm\Token;
use Formapro\Pvm\ProcessBuilder;
use function Makasim\Values\get_values;

require_once __DIR__.'/vendor/autoload.php';

$process = (new ProcessBuilder())
    ->createNode('a_task', 'print_label')->end()
    ->createStartTransition('a_task')->end()

    ->getProcess()
;

$tmpFile = tempnam(sys_get_temp_dir(), 'pvm-demo-');
try {
    file_put_contents($tmpFile, json_encode(get_values($process)));
    echo 'Saved to file: '.$tmpFile.PHP_EOL;

    unset($process);

    $process = Process::create(json_decode(file_get_contents($tmpFile), true));

    echo 'Read from file: '.$tmpFile.PHP_EOL;
} finally {
    unlink($tmpFile);
}

$engine = new ProcessEngine(new DefaultBehaviorRegistry([
    'print_label' => function(Token $token) {
        echo $token->getTo()->getId().PHP_EOL;
    },
]));

$token = $engine->createTokenFor($process->getStartTransition());
$engine->proceed($token);