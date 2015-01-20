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
        $manager = $this->getContainer()->get('hevinci.mockup.export_manager');
        $collector = $this->getContainer()->get('hevinci.mockup.template_collector');
        $templates = $collector->collect($input->getArgument('target'));

        if (count($templates) === 0) {
            $output->writeln('No template found in mockup directory');
            exit(1);
        }

        $targetDir = 'mockups-' . date('d-m-y-h:i:s');
        $manager->exportTemplates($templates, getcwd() . '/' . $targetDir);
        $output->writeln("Mockups exported in '{$targetDir}'");
    }
}
