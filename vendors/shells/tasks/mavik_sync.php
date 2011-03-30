<?php
/**
 * Sync Task File
 * *
 * Distributed under the terms of the MIT License.
 * Redistributions of files must retain the above copyright notice.
 *
 * PHP version 5
 * CakePHP version 1.3
 *
 */

/**
 * Sync Task Class
 *
 * By default the task starts in interactive mode. Below youÕll find an example
 * for a cronjob for invoking the task on a regular basis to automate s3 uploads
 * {{{
 *     #!/bin/bash
 *
 *     CAKE_CORE_INCLUDE_PATH=/usr/local/share/cake-1.3.x.x
 *     CAKE_CONSOLE=${CAKE_CORE_INCLUDE_PATH}/cake/console/cake
 *     APP="/path/to/your/app"
 *
 *     if [ ! -x $CAKE_CONSOLE ] || [ ! -x $APP ]; then
 *     exit 1
 *     fi
 *
 *     for MODEL in $MODELS; do
 *         $CAKE_CONSOLE mavik sync -app $APP -quiet
 *         test $? != 0 && exit 1
 *     done
 *
 *     exit 0
 * }}}
 *
 * @package    media
 * @subpackage media.shells.tasks
 */
class MavikSyncTask extends MavikShell {

/**
 * Default answer to use if prompted for input
 *
 * @var string
 * @access protected
 */
	var $_answer = 'n';

/**
 * Verbosity of output, control via argument `-quiet`
 *
 * @var boolean
 * @access protected
 */
	var $_quiet;

/**
 * Main execution method
 *
 * @access public
 * @return boolean
 */
	function execute() {
	
		App::import('Vendor', 'Mavik.S3', array('file' => 's3/S3.php'));

		//instantiate the class
		$s3 = new S3(Configure::read('Mavik.s3_access_key'), Configure::read('Mavik.s3_secret'));
		
		//create a new bucket
		$s3->putBucket(Configure::read('Mavik.s3_bucket'), S3::ACL_PUBLIC_READ);

		$folder = new Folder(Configure::read('App.www_root') . Configure::read('Mavik.thumb_cache'));
		$files = $folder->findRecursive('.*_s3$');
		
		$this->out(count($files) . ' files to upload.');
		
		if ($files) {
			$this->progress(count($files));
			$cur = 0;
		}
		
		foreach ($files as $filepath) {

			$flagPath = $filepath;

			$thumbRelPath = str_replace(Configure::read('App.www_root') . Configure::read('Mavik.thumb_cache') . DS, '', $filepath);

			$thumbPath = Configure::read('App.www_root') . Configure::read('Mavik.thumb_cache') . DS . str_replace( '_s3', '', $thumbRelPath);

			$destFile = Configure::read('Mavik.s3_root') . '/' . str_replace( '_s3', '', $thumbRelPath);

			$this->progress($cur++, 'Uploading ' . $filepath);
			
			// check if file already exists
			if (!$s3->getObjectInfo(Configure::read('Mavik.s3_bucket'), $destFile, false)) {

				//move the file

				if ($s3->putObjectFile($thumbPath, Configure::read('Mavik.s3_bucket'), $destFile, S3::ACL_PUBLIC_READ)) {
	
					rename($flagPath, $flagPath.'@');				
				
				} else {
				
					$this->out('Could not upload ' . $thumbPath . ' to S3.');
					$this->out();
					
					rename($flagPath, $flagPath.'_err');
					
				}	

			} else {

				rename($flagPath, $flagPath.'@');				
			
			}
					
		}
				
		$this->out('Upload completed.');
		$this->out();
		
		return true;
	}	

}
?>