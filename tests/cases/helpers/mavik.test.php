<?php

//Import the helper to be tested.
//If the tested helper were using some other helper, like Html, 
//it should be impoorted in this line, and instantialized in startTest().

App::import('Helper', 'Html');
App::import('Helper', 'Mavik.Mavik');

class MavikTest extends CakeTestCase {
    private $mavik = null;

		private $testImagePath;
		private $testWatermarkPath;
		
    public function startTest() {

      $this->mavik = new MavikHelper();
			$this->mavik->Html = new HtmlHelper();

			Configure::write('Mavik.thumb_cache', Configure::read('Mavik.thumb_cache') . DS . 'tests');
			
			// clear out old test thumbs
			$folder = new Folder(Configure::read('App.www_root') . Configure::read('Mavik.thumb_cache'), true);
			$files = $folder->find('.*');

			foreach ($files as $filename) {
				$file = new File($folder->path . DS . $filename);
				$file->delete();			
			}
						
			// copy test watermark to img dir
			$file = new File(APP . 'plugins/mavik/tests/cases/helpers/watermark.png');			
			$this->testWatermarkPath = Configure::read('App.www_root') . IMAGES_URL . DS . 'watermark.mavik.test.png';
			$file->copy($this->testWatermarkPath, true);

			// copy test image to img dir
			$file = new File(APP . 'plugins/mavik/tests/cases/helpers/happy_guy.png');			
			$this->testImagePath = Configure::read('App.www_root') . IMAGES_URL . DS . 'happy_guy.mavik.test.png';
			$file->copy($this->testImagePath, true);
			
			Configure::write('Mavik.upload_default', false);
			Configure::write('Mavik.hash_default', false);
			
    }

		public function endTest() {

			$file = new File($this->testWatermarkPath);
			$file->delete();

			$file = new File($this->testImagePath);
			$file->delete();
		
		}

    public function testMavik() {

			$this->basic();

			Configure::write('Mavik.upload_default', true);
			
			$this->basic();
			
		}
		
    public function basic() {
		
			// plain
			$result = $this->mavik->image('happy_guy.mavik.test.png');
			$pattern = array(
        'img' => array('src' => 'img/happy_guy.mavik.test.png'),
	    );

			$this->assertTags($result, $pattern);

			// plain with absolute path
			$result = $this->mavik->image('/img/happy_guy.mavik.test.png');
			$pattern = array(
        'img' => array('src' => '/img/happy_guy.mavik.test.png'),
	    );

			$this->assertTags($result, $pattern);

			// plain, no thumb
			$result = $this->mavik->image('happy_guy.mavik.test.png', array(
				'thumb' => false,
			));
			$pattern = array(
        'img' => array('src' => 'img/happy_guy.mavik.test.png'),
	    );
 
			$this->assertTags($result, $pattern);

			// plain with same dims
			$result = $this->mavik->image('/img/happy_guy.mavik.test.png', array(
				'width' => 400,
				'height' => 300,
			));

			$pattern = array(
        'img' => array(
        	'src' => '/img/happy_guy.mavik.test.png',
					'width' => 400,
					'height' => 300,
        	),
	    );
 
			$this->assertTags($result, $pattern);

			// plain with same dims, no thumb
			$result = $this->mavik->image('/img/happy_guy.mavik.test.png', array(
				'width' => 400,
				'height' => 300,
				'thumb' => false,
			));

			$pattern = array(
        'img' => array(
        	'src' => '/img/happy_guy.mavik.test.png',
					'width' => 400,
					'height' => 300,
        	),
	    );
 
			$this->assertTags($result, $pattern);

			// plain with new dims
			$result = $this->mavik->image('/img/happy_guy.mavik.test.png', array(
				'width' => 100,
				'height' => 120,
			));

			$pattern = array(
        'img' => array(
        	'src' => Configure::read('Mavik.thumb_cache').'/happy_guy-mavik-test-png-7bbba8c6-100x120.png',
					'width' => 100,
					'height' => 120,
        	),
	    );

			$this->assertTags($result, $pattern);

			// plain with new dims, no thumb
			$result = $this->mavik->image('/img/happy_guy.mavik.test.png', array(
				'width' => 300,
				'height' => 200,
				'thumb' => false,
			));

			$pattern = array(
        'img' => array(
        	'src' => '/img/happy_guy.mavik.test.png',
					'width' => 300,
					'height' => 200,
        	),
	    );
 
			$this->assertTags($result, $pattern);

			// plain with single dim, width only
			$result = $this->mavik->image('/img/happy_guy.mavik.test.png', array(
				'width' => 300,
			));

			$pattern = array(
        'img' => array(
        	'src' => Configure::read('Mavik.thumb_cache').'/happy_guy-mavik-test-png-7bbba8c6-300x225.png',
					'width' => 300,
					'height' => 225,
        	),
	    );
 
			$this->assertTags($result, $pattern);
			
			// plain with single dim, height only
			$result = $this->mavik->image('/img/happy_guy.mavik.test.png', array(
				'height' => 150,
			));

			$pattern = array(
        'img' => array(
        	'src' => Configure::read('Mavik.thumb_cache').'/happy_guy-mavik-test-png-7bbba8c6-200x150.png',
					'width' => 200,
					'height' => 150,
        	),
	    );
 
			$this->assertTags($result, $pattern);

			// make bigger, one dim height provided
			$result = $this->mavik->image('happy_guy.mavik.test.png', array(
				'height' => 400,
			));

			$pattern = array(
        'img' => array(
        	'src' => Configure::read('Mavik.thumb_cache').'/happy_guy-mavik-test-png-7bbba8c6-533x400.png',
					'width' => 533,
					'height' => 400,
        	),
	    );
 
			$this->assertTags($result, $pattern);

			// make bigger, one dim width provided
			$result = $this->mavik->image('happy_guy.mavik.test.png', array(
				'width' => 600,
			));

			$pattern = array(
        'img' => array(
        	'src' => Configure::read('Mavik.thumb_cache').'/happy_guy-mavik-test-png-7bbba8c6-600x450.png',
					'width' => 600,
					'height' => 450,
        	),
	    );
 
			$this->assertTags($result, $pattern);

			// plain, with watermark as string
			$result = $this->mavik->image('happy_guy.mavik.test.png', array(
				'watermark' => 'watermark.mavik.test.png',
			));
			
			$pattern = array(
        'img' => array('src' => Configure::read('Mavik.thumb_cache').'/happy_guy-mavik-test-png-7bbba8c6-watermark-mavik-test-png.png'),
	    );
 
			$this->assertTags($result, $pattern);

			// plain, with watermark as string, invalid watermark
			$result = $this->mavik->image('happy_guy.mavik.test.png', array(
				'watermark' => 'watermark.mavik.test.not-exist.png',
			));

			$pattern = array(
        'img' => array('src' => 'img/happy_guy.mavik.test.png'),
	    );
 
			$this->assertTags($result, $pattern);
			
			// plain, with watermark as array
			$result = $this->mavik->image('happy_guy.mavik.test.png', array(
				'watermark' => array('path' => 'watermark.mavik.test.png'),
			));
			
			$pattern = array(
        'img' => array('src' => Configure::read('Mavik.thumb_cache').'/happy_guy-mavik-test-png-7bbba8c6-watermark-mavik-test-png.png'),
	    );
 
			$this->assertTags($result, $pattern);

			// plain, thumb with watermark as string
			$result = $this->mavik->image('happy_guy.mavik.test.png', array(
				'watermark' => 'watermark.mavik.test.png',
				'width' => 100,
				'height' => 100,
			));

			$pattern = array(
        'img' => array('src' => Configure::read('Mavik.thumb_cache').'/happy_guy-mavik-test-png-7bbba8c6-100x100-watermark-mavik-test-png.png',
					'width' => 100,
					'height' => 100,
        ),
	    );
 
			$this->assertTags($result, $pattern);

			// plain, decorated
			$result = $this->mavik->image('happy_guy.mavik.test.png', array(
				'decorate' => true,
			));

			$pattern = array(
				'a' => array('href' => '/img/happy_guy.mavik.test.png',
					'rel' => 'lightbox[]',
				),
				
        'img' => array('src' => 'img/happy_guy.mavik.test.png',
        ),
	    );
 
			$this->assertTags($result, $pattern);

			// plain, thumb decorated
			$result = $this->mavik->image('happy_guy.mavik.test.png', array(
				'width' => 100,
				'height' => 100,
				'decorate' => true,
			));

			$pattern = array(
				'a' => array('href' => '/img/happy_guy.mavik.test.png', // confirm link is to original image
					'rel' => 'lightbox[]',
				),
				
        'img' => array('src' => Configure::read('Mavik.thumb_cache').'/happy_guy-mavik-test-png-7bbba8c6-100x100.png',
					'width' => 100,
					'height' => 100,
        ),
	    );
 
			$this->assertTags($result, $pattern);

			// plain, thumb decorated with group
			$result = $this->mavik->image('happy_guy.mavik.test.png', array(
				'width' => 100,
				'height' => 100,
				'decorate' => 'gallery1',
			));

			$pattern = array(
				'a' => array('href' => '/img/happy_guy.mavik.test.png', // confirm link is to original image
					'rel' => 'lightbox[gallery1]',
				),
				
        'img' => array('src' => Configure::read('Mavik.thumb_cache').'/happy_guy-mavik-test-png-7bbba8c6-100x100.png',
					'width' => 100,
					'height' => 100,
        ),
	    );
 
			$this->assertTags($result, $pattern);
			
			// plain, thumb with class
			$result = $this->mavik->image('happy_guy.mavik.test.png', array(
				'width' => 100,
				'height' => 100,
				'class' => 'superthumb',
			));

			$pattern = array(
        'img' => array('src' => Configure::read('Mavik.thumb_cache').'/happy_guy-mavik-test-png-7bbba8c6-100x100.png',
					'class' => 'superthumb',
					'width' => 100,
					'height' => 100,
        ),
	    );
 
			$this->assertTags($result, $pattern);

			// plain, thumb with style
			$result = $this->mavik->image('happy_guy.mavik.test.png', array(
				'width' => 100,
				'height' => 100,
				'style' => 'float: left;',
			));

			$pattern = array(
        'img' => array('src' => Configure::read('Mavik.thumb_cache').'/happy_guy-mavik-test-png-7bbba8c6-100x100.png',
					'style' => 'float: left;',
					'width' => 100,
					'height' => 100,
        ),
	    );
 
			$this->assertTags($result, $pattern);			

			// plain, thumb with url
			$result = $this->mavik->image('happy_guy.mavik.test.png', array(
				'width' => 100,
				'height' => 100,
				'url' => 'http://google.com',
			));

			$pattern = array(
				'a' => array('href' => 'http://google.com', 
				),
				
        'img' => array('src' => Configure::read('Mavik.thumb_cache').'/happy_guy-mavik-test-png-7bbba8c6-100x100.png',
					'width' => 100,
					'height' => 100,
        ),
	    );
 
			$this->assertTags($result, $pattern);	

			// plain, thumb with zoom
			$result = $this->mavik->image('happy_guy.mavik.test.png', array(
				'zoom' => '25',
				'width' => 100,
				'height' => 100,
			));
			
			$pattern = array(
        'img' => array('src' => Configure::read('Mavik.thumb_cache').'/happy_guy-mavik-test-png-7bbba8c6-100x100-xyz25.png',
					'width' => 100,
					'height' => 100,
        ),
	    );
 
			$this->assertTags($result, $pattern);		

			// plain, thumb with biasY
			$result = $this->mavik->image('happy_guy.mavik.test.png', array(
				'biasY' => '10',
				'width' => 100,
				'height' => 100,
			));

			$pattern = array(
        'img' => array('src' => Configure::read('Mavik.thumb_cache').'/happy_guy-mavik-test-png-7bbba8c6-100x100-xy10z.png',
					'width' => 100,
					'height' => 100,
        ),
	    );
 
			$this->assertTags($result, $pattern);		

			// plain, no thumb with biasY & zoom
			$result = $this->mavik->image('happy_guy.mavik.test.png', array(
				'biasY' => '10',
				'zoom' => '10',
			));

			$pattern = array(
        'img' => array('src' => Configure::read('Mavik.thumb_cache').'/happy_guy-mavik-test-png-7bbba8c6-400x300-xy10z10.png',
					'width' => 400,
					'height' => 300,
        ),
	    );
 
			$this->assertTags($result, $pattern);		

			// plain, wider shorter
			$result = $this->mavik->image('happy_guy.mavik.test.png', array(
				'width' => '400',
				'height' => '100',
			));

			$pattern = array(
        'img' => array('src' => Configure::read('Mavik.thumb_cache').'/happy_guy-mavik-test-png-7bbba8c6-400x100.png',
					'width' => 400,
					'height' => 100,
        ),
	    );
 
			$this->assertTags($result, $pattern);

			// plain, wider shorter with biasy
			$result = $this->mavik->image('happy_guy.mavik.test.png', array(
				'width' => '400',
				'height' => '100',
				'biasY' => -10,
			));

			$pattern = array(
        'img' => array('src' => Configure::read('Mavik.thumb_cache').'/happy_guy-mavik-test-png-7bbba8c6-400x100-xy-10z.png',
					'width' => 400,
					'height' => 100,
        ),
	    );
 
			$this->assertTags($result, $pattern);

			// plain with new dims, faked s3 upload
			touch(Configure::read('App.www_root') . '/'.Configure::read('Mavik.thumb_cache').'/happy_guy-mavik-test-png-7bbba8c6-100x120.png_s3@'); // fake uploaded flag

			$result = $this->mavik->image('/img/happy_guy.mavik.test.png', array(
				'width' => 100,
				'height' => 120,
				'upload' => true,
			));

			$pattern = array(
        'img' => array(
        	'src' => 'http://'.Configure::read('Mavik.s3_bucket').'/'.Configure::read('Mavik.s3_root').'/happy_guy-mavik-test-png-7bbba8c6-100x120.png',
					'width' => 100,
					'height' => 120,
        	),
	    );

			$this->assertTags($result, $pattern);

			unlink(Configure::read('App.www_root') . '/'.Configure::read('Mavik.thumb_cache').'/happy_guy-mavik-test-png-7bbba8c6-100x120.png_s3@'); // remove fake uploaded flag

			// plain with new dims, hashed
			$result = $this->mavik->image('/img/happy_guy.mavik.test.png', array(
				'width' => 100,
				'height' => 130,
				'hash' => true,
			));

			$pattern = array(
        'img' => array(
        	'src' => Configure::read('Mavik.thumb_cache').'/' . md5('happy_guy-mavik-test-png-7bbba8c6-100x130.png') . '.png',
					'width' => 100,
					'height' => 130,
        	),
	    );

			$this->assertTags($result, $pattern);

			// plain with new dims, hashed, s3 faked
			touch(Configure::read('App.www_root') . '/'.Configure::read('Mavik.thumb_cache').'/' . md5('happy_guy-mavik-test-png-7bbba8c6-100x140.png') . '.png_s3@'); // fake uploaded flag

			$result = $this->mavik->image('/img/happy_guy.mavik.test.png', array(
				'width' => 100,
				'height' => 140,
				'upload' => true,
				'hash' => true,
			));

			$pattern = array(
        'img' => array(
        	'src' => 'http://'.Configure::read('Mavik.s3_bucket').'/'.Configure::read('Mavik.s3_root').'/'.md5('happy_guy-mavik-test-png-7bbba8c6-100x140.png') . '.png',
					'width' => 100,
					'height' => 140,
        	),
	    );

			$this->assertTags($result, $pattern);

			unlink(Configure::read('App.www_root') . '/'.Configure::read('Mavik.thumb_cache').'/' . md5('happy_guy-mavik-test-png-7bbba8c6-100x140.png') . '.png_s3@'); // remove fake uploaded flag
			  
  	}
    
}
