<?php
namespace App\Service;

use App\Model\Example;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class GetExamplesService
{
    public function getOne(string $name): Example
    {
        $examples = $this->getAll();

        foreach ($examples as $example) {
            if ($example->name === $name) {
                return $example;
            }
        }

        throw new \LogicException('The example with such name does not exists');
    }

    /**
     * @return array|Example[]
     */
    public function getAll(): array
    {
        $exampleDirectories = (new Finder())
            ->depth(0)
            ->directories()
            ->exclude('vendor')
            ->in(realpath(__DIR__.'/../../examples'))
        ;

        $examples = [];
        foreach ($exampleDirectories as $exampleDirectory) {
            list($order) = explode('-', basename($exampleDirectory), 2);

            $descriptionFile = $exampleDirectory.'/desc.html';
            $description = file_exists($descriptionFile) ? file_get_contents($descriptionFile) : '';

            $config = json_decode(file_get_contents($exampleDirectory.'/config.json'), true);

            $example = new Example();
            $example->name = $config['name'];
            $example->title = $config['title'];
            $example->description = $description;
            $example->order = (int) $order;
            $example->exampleDirectory = $exampleDirectory;
            $example->htmlOutput = (bool) $config['htmlOutput'];

            $exampleScriptFiles = (new Finder())
                ->name('*.php')
                ->depth(0)
                ->sort(function(\SplFileInfo $left, \SplFileInfo $right) use ($config) {
                    $leftOrder = array_search($left->getBasename(), $config['order']);
                    if (false === $leftOrder) {
                        throw new \LogicException('No order for file '.$left);
                    }
                    $rightOrder = array_search($right->getBasename(), $config['order']);
                    if (false === $rightOrder) {
                        throw new \LogicException('No order for file '.$right);
                    }

                    return $leftOrder <=> $rightOrder;
                })
                ->in((string) $exampleDirectory)
            ;

            foreach ($exampleScriptFiles as $exampleFile) {
                $example->scriptFiles[basename($exampleFile)] = $exampleFile;
            }

            $examples[] = $example;
        }

        usort($examples, function ($left, $right) {
            return $left->order <=> $right->order;
        });

        return $examples;
    }
}
