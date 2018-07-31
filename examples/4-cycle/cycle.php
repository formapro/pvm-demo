<?php
use Formapro\Pvm\DefaultBehaviorRegistry;
use Formapro\Pvm\ProcessEngine;
use Formapro\Pvm\Token;
use Formapro\Pvm\ProcessBuilder;

require_once __DIR__.'/vendor/autoload.php';


$process = (new ProcessBuilder())
    ->createNode('send_email', 'send_email')->end()
    ->createNode('email_sent', 'email_sent')->end()

    ->createTransition('send_email', 'send_email', 'retry')->end()
    ->createTransition('send_email', 'email_sent')->end()
    ->createStartTransition('send_email')->end()

    ->getProcess()
;

$engine = new ProcessEngine(new DefaultBehaviorRegistry([
    'email_sent' => function(Token $token) {
        echo 'email is sent'.PHP_EOL;
    },
    'send_email' => function(Token $token) {
        $counter = $token->getValue('counter', 0);
        $counter++;

        $token->setValue('counter', $counter);

        try {
            if ($counter < $token->getValue('maxRetries')) {
                throw new \LogicException('Mail server is down');
            }
        } catch (\Throwable $e) {
            echo 'failed to send email'.PHP_EOL;
            return 'retry';
        }
    },
]));

$token = $engine->createTokenFor($process->getStartTransition());
$token->setValue('maxRetries', rand(2, 4));

$engine->proceed($token);
