<?php

declare(strict_types=1);

/**
 * i18n:generate command for Skeleton Console
 *
 * @author Gerry Demaret <gerry@tigron.be>
 * @author Christophe Gosiau <christophe@tigron.be>
 * @author David Vandemaele <david@tigron.be>
 */

namespace Skeleton\Console\Command;

use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Core_App_List extends \Skeleton\Console\Command {
	/**
	 * Configure the Create command
	 *
	 * @access protected
	 */
	protected function configure(): void {
		$this->setName('core:app:list');
		$this->setDescription('Lists all applications in this project');
	}

	/**
	 * Execute the Command
	 *
	 * @access protected
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
				$application->config->application_type,
			];
		}
		$table->setRows($rows);
		$table->render();
		return 0;
	}
}
