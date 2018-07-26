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
            list($order, $exampleName) = explode('-', basename($exampleDirectory), 2);
            $exampleTitle = ucwords(str_replace(['_', '-'], ' ', $exampleName));

            $descriptionFile = $exampleDirectory.'/desc.html';
            $description = file_exists($descriptionFile) ? file_get_contents($descriptionFile) : '';

            $example = new Example();
            $example->name = $exampleName;
            $example->title = $exampleTitle;
            $example->description = $description;
            $example->order = (int) $order;

            $exampleScriptFiles = (new Finder())
                ->name('*.php')
                ->depth(0)
                ->files()
                ->in((string) $exampleDirectory)
            ;

            foreach (array_reverse(iterator_to_array($exampleScriptFiles)) as $exampleFile) {
                $example->scriptFiles[substr(basename($exampleFile), 2)] = $exampleFile;
            }

            $examples[] = $example;
        }

        usort($examples, function ($left, $right) {
            return $left->order <=> $right->order;
        });

        return $examples;
    }
}
