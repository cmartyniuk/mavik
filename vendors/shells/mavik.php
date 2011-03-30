<?php
/**
 * Mavik Shell File
 *
 * Distributed under the terms of the MIT License.
 * Redistributions of files must retain the above copyright notice.
 *
 * PHP version 5
 * CakePHP version 1.3
 *
 */
require_once App::pluginPath('mavik') . 'config' . DS . 'core.php';
Configure::write('Cache.disable', true);

/**
 * Mavik Shell Class
 *
 * @package    mavik
 * @subpackage mavik.shells
 */
class MavikShell extends Shell {

/**
 * Tasks
 *
 * @var string
 * @access public
 */
	var $tasks = array('MavikSync');

/**
 * Verbose mode
 *
 * @var boolean
 * @access public
 */
	var $verbose = false;

/**
 * Quiet mode
 *
 * @var boolean
 * @access public
 */
	var $quiet = false;

/**
 * Startup
 *
 * @access public
 * @return void
 */
	 function startup() {
		$this->verbose = isset($this->params['verbose']);
		$this->quiet = isset($this->params['quiet']);
		parent::startup();
	}

/**
 * Welcome
 *
 * @access protected
 * @return void
 */
	function _welcome() {
		$this->hr();
		$this->out('Mavik Shell');
		$this->hr();
	}

/**
 * Main
 *
 * @access public
 * @return void
 */
	 function main() {
		$this->out('[I]nitialize Mavik Thumb Cache Directory');
		$this->out('[S]ynchronize with S3');
		$this->out('[H]elp');
		$this->out('[Q]uit');

		$action = $this->in(
			__('What would you like to do?', true),
			array('I', 'S', 'H', 'Q'),
			'q'
		);

		$this->out();

		switch (strtoupper($action)) {
			case 'I':
				$this->init();
				break;
			case 'S':
				$this->MavikSync->execute();
				break;
			case 'H':
				$this->help();
				break;
			case 'Q':
				$this->_stop();
		}
		$this->main();
	}

/**
 * Initializes directory structure
 *
 * @access public
 * @return void
 */
	function init() {
		$message = 'Do you want to create missing thumb cache directory now?';

		if ($this->in($message, 'y,n', 'n') == 'n') {
			return false;
		}

		$dir = Configure::read('App.www_root') . Configure::read('Mavik.thumb_cache');
				
		new Folder($dir, true);

		if (is_dir($dir)) {
			$result = 'OK';
		} else {
			$result = 'FAIL';
		}

		$this->out($dir . ' -> ' . $result);
		$this->out('Remember to set the correct permissions on the thumb cache directory.');
	}

/**
 * Displays help contents
 *
 * @access public
 */
	function help() {
		// 63 chars ===============================================================
		$this->out("NAME");
		$this->out("\tmavik -- mavik thumbnail and watermark generator PLUS s3 synchronization");
		$this->out('');
		$this->out("SYNOPSIS");
		$this->out("\tcake mavik <params> <command> <args>");
		$this->out('');
		$this->out("COMMANDS");
		$this->out("\tinit");
		$this->out("\t\tInitializes the mavik directory structure.");
		$this->out('');
		$this->out("\tsync");
		$this->out("\t\tSynchronize S3 with local cache.");
		$this->out('');
		$this->out("\thelp");
		$this->out("\t\tShows this help message.");
		$this->out('');
		$this->out("OPTIONS");
		$this->out("\t-verbose");
		$this->out("\t-quiet");
		$this->out('');
	}

/**
 * progress
 *
 * Start with progress(target value)
 * Update with progress(current value, text)
 *
 * @param mixed $value
 * @param mixed $text
 * @access public
 * @return void
 */
	function progress($value, $text = null) {
		static $target = 0;

		if ($this->quiet) {
			return null;
		}

		if ($text === null) {
			$target = $value;
		} else {
			$out = sprintf('%\' 6.2f%% %s', ($value * 100) / $target, $text);
			$this->out($out);
		}
	}
}
?>