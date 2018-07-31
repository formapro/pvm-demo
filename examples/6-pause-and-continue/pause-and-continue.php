<?php
use Formapro\Pvm\DefaultBehaviorRegistry;
use Formapro\Pvm\Exception\WaitExecutionException;
use Formapro\Pvm\ProcessEngine;
use Formapro\Pvm\Token;
use Formapro\Pvm\ProcessBuilder;

require_once __DIR__.'/vendor/autoload.php';

$process = (new ProcessBuilder())
    ->createNode('purchase', 'purchase')->end()
    ->createStartTransition('purchase')->end()

    ->getProcess()
;

$engine = new ProcessEngine(new DefaultBehaviorRegistry([
    'purchase' => function(Token $token) {
        if (false ==$token->getValue('credit_card', false)) {
            echo 'need a credit card '.PHP_EOL;
            throw new WaitExecutionException();
        }

        echo 'purchased '.PHP_EOL;
    },
]));

$token = $engine->createTokenFor($process->getStartTransition());

$waitTokens = $engine->proceed($token);

// the process was paused because one of the tasks needs something.
echo 'ask customer to fill credit card'.PHP_EOL;

$waitTokens[0]->setValue('credit_card', '4111 1111 1111 1111');

echo 'credit card is provided'.PHP_EOL;

$engine->proceed($waitTokens[0]);
