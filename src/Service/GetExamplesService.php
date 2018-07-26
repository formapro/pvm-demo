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
        if (false == array_key_exists($name, $examples)) {
            throw new \LogicException('The example with such name does not exists');
        }

        return $examples[$name];
    }

    /**
     * @return array|Example[]
     */
    public function getAll(): array
    {
       $unorderedExamples = $this->getAllUnordered();

       return [
           'sequence' => $unorderedExamples['sequence'],
           'fork' => $unorderedExamples['fork'],
           'condition' => $unorderedExamples['condition'],
           'cycle' => $unorderedExamples['cycle'],
           'synchronization' => $unorderedExamples['synchronization'],
           'pause-and-continue' => $unorderedExamples['pause-and-continue'],
           'clone-and-modify' => $unorderedExamples['clone-and-modify'],
           'store-to-file' => $unorderedExamples['store-to-file'],
           'store-to-mongodb' => $unorderedExamples['store-to-mongodb'],
       ];
    }

    /**
     * @return array|Example[]
     */
    private function getAllUnordered(): array
    {
        $exampleFiles = (new Finder())
            ->name('*.php')
            ->depth(0)
            ->files()
            ->in(__DIR__.'/../../examples')
        ;

        $examples = [];
        foreach ($exampleFiles as $exampleFile) {
            /** @var SplFileInfo $exampleFile */

            $exampleName = str_replace('.php', '', $exampleFile->getFilename());
            $exampleTitle = ucwords(str_replace(['_', '-'], ' ', $exampleName));

            $descriptionFile = __DIR__.'/../../examples/'.$exampleName.'.html';
            $description = file_exists($descriptionFile) ? file_get_contents($descriptionFile) : '';

            $example = new Example();
            $example->name = $exampleName;
            $example->title = $exampleTitle;
            $example->description = $description;
            $example->scriptFiles[] = $exampleFile;

            $examples[$exampleName] = $example;
        }

        return $examples;
    }
}
