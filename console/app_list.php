<?php
/**
 * i18n:generate command for Skeleton Console
 *
 * @author Gerry Demaret <gerry@tigron.be>
 * @author Christophe Gosiau <christophe@tigron.be>
 * @author David Vandemaele <david@tigron.be>
 */

namespace Skeleton\Console\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Aptoma\Twig\Extension\MarkdownExtension;
use Aptoma\Twig\Extension\MarkdownEngine;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableCell;

class Core_App_List extends \Skeleton\Console\Command {

	protected $twig_extractor = null;

	/**
	 * Configure the Create command
	 *
	 * @access protected
	 */
	protected function configure() {
		$this->setName('core:app:list');
		$this->setDescription('Lists all applications in this project');
	}

	/**
	 * Execute the Command
	 *
	 * @access protected
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 */
	protected function execute(InputInterface $input, OutputInterface $output) {
		$applications = \Skeleton\Core\Application::get_all();

		$table = new Table($output);
		$table->setHeaders(['Name', 'Hostnames', 'Type']);

		$rows = [];
		foreach ($applications as $application) {
			$rows[] = [
				$application->name,
				implode(', ', $application->config->hostnames),
				$application->config->application_type
			];
		}
		$table->setRows($rows);
		$table->render();
		return 0;
	}

}
