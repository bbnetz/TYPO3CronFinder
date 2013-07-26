<?php
/**
 * TYPO3CronFinder Class
 *
 * 
 * @todo different processUsers
 * @todo enable logFiles
 */
class TYPO3CronFinder {
	protected $path = '/var/www/';
	protected $depth = 2;
	protected $everyMinutes = 5;
	protected $cronPath = '/etc/cron.d/typo3';
	protected $writeCron = false;
	protected $processUser = 'www-data';

	public function __construct() {
		/**
		 * @todo SET $this vars in here
		 */
	}

	public function run() {
		$founds = $this->getTYPO3Instances();
		if($this->writeCron) {
			if(writeCronFile($this->cronPath, $this->buildCronFile($founds))) {
				echo 'CronFile written to '.$this->cronPath;
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

	protected function getTYPO3Instances() {
		$path = $this->path;
		for($i = 0; $i < $this->depth; $i++)
			$path .= '*/';
		$path .= 'typo3conf';
		$founds = glob($path, GLOB_ONLYDIR);
		if(count($founds) == 0) throw new Exception('No Instances found');
		return $founds;
	}

	protected function buildCronRow($path) {
		$path = str_replace('typo3conf', '', $path);
		$str = '*/'.$this->everyMinutes.' * * * * '.$this->processUser.' '.$path.'typo3/cli_dispatch.phpsh scheduler'.PHP_EOL;
		return $str;
	}

	protected function buildCronFile($founds) {
		$str = $this->getCopyRight();
		foreach($founds as $found)
			$str .= $this->buildCronRow($found);
		return $str;
	}

	protected function writeCronFile($filename, $content) {
		/**
		 * @todo write WriteConfFile
		 */
	}

	protected function getCopyRight() {
		$str = '# ';
		return $str;
	}

}

$run = new TYPO3CronFinder();
$run->run();
