<?php
/**
 * SimpleCrud\Uploads
 * 
 * Provides basic upload operations (save files from user upload or url).
 * Example:
 * 
 * class Item extends SimpleCrud\Item {
 * 	use SimpleCrud\Uploads;
 * 
 *  static $uploadsPath = 'www/uploadsfolder/';
 *  static $uploadsUrl = '/uploadsfolder/';
 * }
 * 
 * $Item = new Item();
 * 
 * $Item::saveFile('http://domain.com/picture.jpg', 'pictures');
 */
namespace SimpleCrud;

trait Uploads {
	protected static $uploadsPath;
	protected static $uploadsUrl;


	/**
	 * Save a file
	 * 
	 * @param string/array $file The file to save. Can be from $_FILES or url
	 * @param string $path The path for the file (starting from uploads folder)
	 * @param string $filename An optional new filename
	 * 
	 * @return string The upload filename.
	 * @return null If no file has been uploaded
	 * @return false If there was an error
	 */
	public static function saveFile ($file, $path, $filename = null) {
		if (is_array($file)) {
			return static::saveUploadedFile($file, $path, $filename);
		}
		if (is_string($file)) {
			return static::saveFileFromUrl($file, $path, $filename);
		}
	}

	
	/**
	 * Returns the full path of a file
	 * 
	 * @param string $path The path for the file (starting from uploads folder)
	 * @param string $filename An optional new filename
	 * 
	 * @return string The file path
	 */
	public static function getFilePath ($path, $filename = null) {
		return static::$uploadsPath.$path.$filename;
	}

	
	/**
	 * Returns the url of a file 
	 * 
	 * @param string $path The path for the file (starting from uploads url)
	 * @param string $filename An optional new filename
	 * 
	 * @return string The file url
	 */
	public static function getFileUrl ($path, $filename = null) {
		return static::$uploadsUrl.$path.$filename;
	}

	
	/**
	 * Save an uploaded file
	 * 
	 * @param array $file The uploaded file (from $_FILES array)
	 * @param string $path The path for the file (starting from uploads folder)
	 * @param string $filename An optional new filename
	 * 
	 * @return string The upload filename.
	 * @return null If no file has been uploaded
	 * @return false If there was an error
	 */
	public static function saveUploadedFile (array $file, $path, $filename = null) {
		if (!empty($file['tmp_name']) && empty($file['error'])) {
			if ($filename === null) {
				$filename = $file['name'];
			}

			if (!pathinfo($filename, PATHINFO_EXTENSION) && ($extension = pathinfo($file['name'], PATHINFO_EXTENSION))) {
				$filename .= ".$extension";
			}

			$destination = static::getFilePath($path, $filename);

			if (!rename($file['tmp_name'], $destination)) {
				return false;
			}

			chmod($destination, 0666);

			return $filename;
		}
	}


	/**
	 * Save a file from an URL
	 * 
	 * @param string $file The url of the file
	 * @param string $path The path for the file (starting from uploads folder)
	 * @param string $filename An optional new filename
	 * 
	 * @return string The upload filename.
	 * @return null If no file has been uploaded
	 * @return false If there was an error
	 */
	public static function saveFileFromUrl ($file, $path, $filename = null) {
		if (!empty($file) && strpos($file, '://')) {
			if ($filename === null) {
				$filename = pathinfo($file, PATHINFO_BASENAME);
			} else if (!pathinfo($filename, PATHINFO_EXTENSION) && ($extension = pathinfo(parse_url($file, PHP_URL_PATH), PATHINFO_EXTENSION))) {
				$filename .= ".$extension";
			}

			$destination = static::getFilePath($path, $filename);

			try {
				$content = file_get_contents($file);
				file_put_contents($destination, $content);
			} catch (\Exception $Exception) {
				return false;
			}

			return $filename;
		}
	}
}
?>
