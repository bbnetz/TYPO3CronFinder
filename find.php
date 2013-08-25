#!/usr/bin/php
<?php
/**
 * TYPO3CronFinder Class
 * By Bastian Bringenberg <mail@bastian-bringenberg.de>
 *
 * #########
 * # USAGE #
 * #########
 *
 * See Readme File
 *
 * ###########
 * # Licence #
 * ###########
 *
 * See License File
 *
 * ##############
 * # Repository #
 * ##############
 *
 * Fork me on GitHub
 * https://github.com/bbnetz/TYPO3CronFinder
 *
 */

/**
 * Class TYPO3CronFinder
 * @author Bastian Bringenberg <mail@bastian-bringenberg.de>
 * @link https://github.com/bbnetz/TYPO3CronFinder
 *
 */
class TYPO3CronFinder {
	/**
	 * @param string $path The basic path.
	 */
	protected $path = '/var/www/';

	/**
	 * @param int $depth The depth to watch. Eg 2 adds 2 folders to the basic path
	 */
	protected $depth = 2;

	/**
	 * @param int $everyMinutes The number of minutes that should have been waited between each call
	 */
	protected $everyMinutes = 5;

	/**
	 * @param string $cronPath The file to write the cron into
	 */
	protected $cronPath = '/etc/cron.d/typo3';

	/**
	 * @param boolean $writeCron True if script should write cron itself
	 */
	protected $writeCron = false;

	/**
	 * @param string $processUser The user to start the cronjob.
	 * 			Only used if $useFileOwner is false
	 */
	protected $processUser = 'www-data';

	/**
	 * @param boolean $useFileOwner if set $processUser is ignored and file owner will be used
	 *				directory with TYPO3 inside will be used for getting the username
	 */
	protected $useFileOwner = false;

	/**
	 * @param boolean $quiet if normal output should be ignored
	 */
	protected $quiet = false;

	/**
	 * Constructor
	 *
	 * Collection all CLI Params
	 * Setting local settings from CLI params
	 *
	 */
	public function __construct($argv) {
		try{
			// $path
			if(!isset($argv[1])) throw new Exception('Please use correct usage: $ ./find.php path depth everyMinutes.');
			if(!file_exists(realpath($argv[1]))) throw new Exception('Basic Path does not exist.');
			$this->path = realpath($argv[1]);

			// $depth
			if(!isset($argv[2])) throw new Exception('Please use correct usage: $ ./find.php path depth everyMinutes.');
			if(intval($argv[2]) <= 0) throw new Exception('0 depth makes no sense...');
			$this->depth = intval($argv[2]);

			// $everyMinutes
			if(!isset($argv[3])) throw new Exception('Please use correct usage: $ ./find.php path depth everyMinutes.');
			if(intval($argv[3]) <= 0) throw new Exception('Running less than 1 makes no sense...');
			if(intval($argv[3]) > 59) throw new Exception('Running more than 59 not yet implemented. If needed create an issue.');
			$this->everyMinutes = intval($argv[3]);

			// $cronPath
			if(isset($argv[4])) {
				if($argv[4] == 'NONE') {
					$this->writeCron = false;
				}else{
					$this->cronPath = realpath($argv[4]);
					if(!file_exists(dirname($this->cronPath))) throw new Exception('Path for CronJob does not exist!');
					$this->writeCron = true;
				}
			}

			// $cronUser
			if(isset($argv[5])) {
				if($argv[5] == 'owner') {
					$this->useFileOwner = true;
				}else{
					$this->processUser = $argv[5];
				}
			}

			// $quiet
			if(isset($argv[4]) && $argv[4] == '-q') $this->quiet = true;
			if(isset($argv[5]) && $argv[5] == '-q') $this->quiet = true;
			if(isset($argv[6]) && $argv[6] == '-q') $this->quiet = true;

		}catch(Exception $e){
			echo $e->getMessage();
			exit(0);
		}
	}

	/**
	 * function run
	 * Doing the main work
	 * Using collected informations to build cronfile content
	 * and write it down if requested
	 *
	 * @return void
	 */
	public function run() {
		$founds = $this->getTYPO3Instances();
		if($this->writeCron) {
			if($this->writeCronFile($this->cronPath, $this->buildCronFile($founds))) {
				if(!$this->quiet) echo 'CronFile written to '.$this->cronPath;
			}else{
				$user = posix_getpwuid(posix_geteuid());
				echo 'CronFile not written. Please check path and rights.'.PHP_EOL;
				echo 'Path:'.$this->cronPath.PHP_EOL;
				echo 'User:'.$user['name'].PHP_EOL;
				echo PHP_EOL;
				echo $this->buildCronFile($founds);
			}
		}else{
			echo $this->buildCronFile($founds);
		}
	}

	/**
	 * function getTYPO3Instances
	 * Builds SearchPath from basic path and depth
	 * Uses typo3conf as TYPO3 identifier
	 *
	 * @throws Exception if no instances found
	 * @return array<string> Pathes to the TYPO3 instances
	 */
	protected function getTYPO3Instances() {
		$path = $this->path;
		for($i = 0; $i < $this->depth; $i++)
			$path .= '*/';
		$path .= 'typo3conf';
		$founds = glob($path, GLOB_ONLYDIR);
		if(count($founds) == 0) throw new Exception('No Instances found');
		return $founds;
	}

	/**
	 * function buildCronRow
	 * Creates from all existing informations a single cron
	 * line.
	 *
	 * @param string $path the path to a single TYPO3 intance
	 * @return string a single cronjob line
	 */
	protected function buildCronRow($path) {
		$path = str_replace('typo3conf', '', $path);
		$owner = $this->processUser;
		$fileOwner = posix_getpwuid(fileowner($path));
		if($this->useFileOwner) $owner = $fileOwner['name'];
		$str = '*/'.$this->everyMinutes.' * * * * '.$owner.' '.$path.'typo3/cli_dispatch.phpsh scheduler'.PHP_EOL;
		return $str;
	}

	/**
	 * function buildCronFile
	 * Reads copyright information
	 * Iterates through $founds and creates a cronjob line for each
	 *
	 * @param array<string> $founds the founded TYPO3 instances
	 * @return string the cron-files content
	 */
	protected function buildCronFile($founds) {
		$str = $this->getCopyRight();
		foreach($founds as $found)
			$str .= $this->buildCronRow($found);
		return $str;
	}

	protected function writeCronFile($filename, $content) {
		return file_put_contents($filename, $content);
	}

	/**
	 * function getCopyRight
	 * As prefix for the cronfile to inform the origin of the cronjobs information
	 *
	 * @return string A CopyRight Information
	 */
	protected function getCopyRight() {
		$str  = '# '.PHP_EOL;
		$str .= '# TYPO3 Cron Generator'.PHP_EOL;
		$str .= '# By Bastian Bringenberg <mail@bastian-bringenberg.de>'.PHP_EOL;
		$str .= '# '.PHP_EOL;
		$str .= '# More Informations:'.PHP_EOL;
		$str .= '# https://github.com/bbnetz/TYPO3CronFinder'.PHP_EOL;
		$str .= '# '.PHP_EOL;
		$str .= ''.PHP_EOL;
		$str .= ''.PHP_EOL;
		return $str;
	}

}
$run = new TYPO3CronFinder($argv);
$run->run();

?>
