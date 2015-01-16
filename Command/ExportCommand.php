<?php

namespace HeVinci\MockupBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;

class ExportCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName('hevinci:mockup:export')
            ->setDescription('Creates a portable version of a mockup')
            ->addArgument(
                'target',
                InputOption::VALUE_REQUIRED,
                'Bundle or template to export',
                null
            );
    }

    protected function interact(InputInterface $input, OutputInterface $output)
    {
        if (!$input->getArgument('target')) {
            $target = $this->getHelper('dialog')->askAndValidate(
                $output,
                'Enter the bundle, or template to export: ',
                function ($argument) {
                    if (empty($argument)) {
                        throw new \Exception('This argument is required');
                    }

                    return $argument;
                }
            );
            $input->setArgument('target', $target);
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $target = $input->getArgument('target');
        $manager = $this->getContainer()->get('hevinci.mockup.export_manager');
        $kernel = $this->getContainer()->get('kernel');
        $bundles = $kernel->getBundles();
        $templates = [];

        // look for bundle target
        if (array_key_exists($target, $bundles)) {
            $mockupDir = $bundles[$target]->getPath() . '/Resources/views/mockup';

            if (!file_exists($mockupDir)) {
                $output->writeln(
                    "Bundle '{$bundles[$target]->getName()}' has no mockup directory"
                );
                exit(1);
            }

            $finder = (new Finder())
                ->files()
                ->name('*.twig')
                ->in($mockupDir);

            foreach ($finder as $file) {
                $templates[] = $target . '::mockup/' . $file->getRelativePathname();
            }

            if (count($templates) === 0) {
                $output->writeln('No template found in mockup directory');
                exit(1);
            }
        } else {
            // try with simple template
            // TODO: directory option should be added
            $templates[] = $target;
        }

        $targetDir = 'mockups-' . date('d-m-y-s');
        $manager->exportTemplates($templates, getcwd() . '/' . $targetDir);
        $output->writeln("Mockups exported in '{$targetDir}'");
    }
}
