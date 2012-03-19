<?php
namespace CRM\Civix\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use CRM\Civix\Command\BaseCommand;
use CRM\Civix\Builder\Collection;
use CRM\Civix\Builder\Dirs;
use CRM\Civix\Builder\Info;
use CRM\Civix\Utils\Path;

class InitCommand extends BaseCommand
{
    protected function configure()
    {
        $this
            ->setName('init')
            ->setDescription('Create a new extension')
            ->addArgument('full-name', InputArgument::REQUIRED, 'Qualified extension name (e.g. "com.example.myextension")')
            //->addOption('type', null, InputOption::VALUE_OPTIONAL, 'Type of extension (e.g. "module", "payment", "report", "search")', 'module')
            ->addOption('type', null, InputOption::VALUE_OPTIONAL, 'Type of extension', 'module')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $ctx = array();
        $ctx['type'] = $input->getOption('type');
        $ctx['fullName'] = $input->getArgument('full-name');
        $ctx['basedir'] = $ctx['fullName'];
        if (preg_match('/^[a-z0-9\.]+\.([a-z0-9]+)$/', $ctx['fullName'], $matches)) {
            $ctx['mainFile'] = $matches[1];
            $ctx['namespace'] = 'CRM/' . strtoupper($ctx['mainFile']{0}) . substr($ctx['mainFile'], 1);
        } else {
            $output->writeln('<error>Malformed package name</error>');
            return;
        }
        $ext = new Collection();
        
        switch($input->getOption('type')) {
          case 'module':
              $output->writeln("<info>Initalize module ".$ctx['fullName']."</info>");
              $basedir = new Path($ctx['basedir']);
              $ext->builders['dirs'] = new Dirs(array(
                  $basedir->string('build'),
                  $basedir->string('templates'),
                  $basedir->string('xml'),
                  $basedir->string($ctx['namespace']),
              ));
              $ext->builders['info'] = new Info($basedir->string('info.xml'));
              break;
          case 'payment':
          case 'report':
          case 'search':
          default:
              $output->writeln("<error>Unrecognized extension type: ". $input->getOption('type'). "</error>");
              return;
        }
        
        $ext->init($ctx);
        $ext->save($ctx, $output);
    }
}