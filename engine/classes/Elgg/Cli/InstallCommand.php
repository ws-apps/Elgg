<?php

namespace Elgg\Cli;

use ElggInstaller;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * elgg-cli install [--config]
 */
class InstallCommand extends \Symfony\Component\Console\Command\Command {

	use ConsoleInteractions;

	/**
	 * {@inheritdoc}
	 */
	protected function configure() {
		$this->setName('install')
			->setDescription('Install Elgg using a configuration file or interactive questions')
			->addOption('config', 'c', InputOption::VALUE_OPTIONAL, 'Path to php file that returns an array with installation configuration');
	}

	/**
	 * {@inheritdoc}
	 */
	protected function execute(InputInterface $input, OutputInterface $output) {

		$this->input = $input;
		$this->output = $output;

		$config = $this->option('config');
		if ($config && file_exists(realpath($config))) {
			$params = include $config;
		} else {
			$params = [
				/**
				 * Admin account
				 */
				'displayname' => 'Administrator',
				'username' => $this->ask('Enter admin username [admin]: ', 'admin'),
				'password' => $this->ask('Enter admin password: ', null, true),
				'email' => $email = $this->ask('Enter admin email: '),
				/**
				 * Database parameters
				 */
				'dbhost' => $this->ask('Enter database host [localhost]: ', 'localhost'),
				'dbuser' => $this->ask('Enter database username: '),
				'dbpassword' => $this->ask('Enter database password: ', null, true),
				'dbname' => $this->ask('Enter database name: '),
				'dbprefix' => $this->ask('Enter database prefix [elgg_]: ', 'elgg_'),
				/**
				 * Site settings
				 */
				'sitename' => $this->ask('Enter site name: '),
				'siteemail' => $this->ask("Enter site email [$email]: ", $email),
				'wwwroot' => $this->ask('Enter site URL (including protocol http|https and a trailing /): '),
				'dataroot' => $this->ask('Enter data directory path: '),
				'timezone' => 'UTC',
			];
		}

		try {
			$installer = new ElggInstaller();
			$htaccess = !is_file(\Elgg\Application::projectDir()->getPath('.htaccess'));
			$installer->batchInstall($params, $htaccess);
		} catch (\InstallationException $ex) {
			$this->dumpRegisters();
			$this->write(elgg_format_element('error', [], $ex->getMessage()));

			return 1;
		}

		\Elgg\Application::start();

		$version = elgg_get_version(true);

		$this->write("Elgg $version install successful");
		$this->write("wwwroot: " . elgg_get_config('wwwroot'));
		$this->write("dataroot: " . elgg_get_config('dataroot'));
		$this->write("cacheroot: " . elgg_get_config('cacheroot'));

	}

}
