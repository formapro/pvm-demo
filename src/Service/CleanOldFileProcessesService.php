<?php
namespace App\Service;

use App\Model\Example;
use Symfony\Component\Finder\Finder;

class CleanOldFileProcessesService
{
    public function clean(Example $example): void
    {
        if (false == file_exists($example->exampleDirectory . '/store')) {
            return;
        }

        $twoHoursAgo = new \DateTime('now - 2 hours');

        $processFiles = (new Finder())
            ->files()
            ->ignoreDotFiles(true)
            ->name('*.json')
            ->filter(function (\SplFileInfo $file) use ($twoHoursAgo) {
                if ($file->isLink() && false == file_exists($file->getRealPath())) {
                    return true;
                }

                return \DateTime::createFromFormat('U', $file->getCTime()) < $twoHoursAgo;
            })
            ->depth(0)
            ->in($example->exampleDirectory . '/store');

        foreach ($processFiles as $file) {
            unlink($file);
        }
    }
}