<?php
use Formapro\Pvm\DefaultBehaviorRegistry;
use Formapro\Pvm\ProcessEngine;
use Formapro\Pvm\Token;
use Formapro\Pvm\ProcessBuilder;

require_once __DIR__.'/vendor/autoload.php';

$process = (new ProcessBuilder())
    ->createNode('calculate_total_price', 'calculate_total_price')->end()
    ->createNode('calculate_shipping_price', 'calculate_shipping_price')->end()

    ->createTransition('calculate_total_price', 'calculate_shipping_price')->end()
    ->createStartTransition('calculate_total_price')->end()

    ->getProcess()
;

$engine = new ProcessEngine(new DefaultBehaviorRegistry([
    'calculate_total_price' => function(Token $token) {
        $totalPrice = 0;
        foreach ($token->getValue('items') as $item) {
            echo 'item price: '.$item['price'].PHP_EOL;

            $totalPrice += $item['price'];
        }

        $token->setValue('total_price', $totalPrice);

        echo 'total price: '.$totalPrice.PHP_EOL;
    },
    'calculate_shipping_price' => function(Token $token) {
        $totalPrice = $token->getValue('total_price');
        $token->setValue('shipping_price', $totalPrice * 0.1);

        echo 'shipping price: '.$token->getValue('shipping_price').PHP_EOL;
    },
]));

$token = $engine->createTokenFor($process->getStartTransition());
$token->setValue('items.0.price', rand(10, 100));
$token->setValue('items.1.price', rand(10, 100));

$engine->proceed($token);