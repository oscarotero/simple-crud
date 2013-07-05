<?php
/**
 * SimpleCrud\Uploads
 * 
 * Provides basic upload operations (save files from user upload or url).
 */
namespace SimpleCrud;

trait Uploads {
	protected static $__assets;


	/**
	 * Save a file
	 * 
	 * @param string/array $file The file to save. Can be from $_FILES or url
	 * @param string $filename An optional new filename
	 * 
	 * @return string The upload filename.
	 * @return null If no file has been uploaded
	 * @return false If there was an error
	 */
	public static function saveFile ($file, $filename = null) {
		if (is_array($file)) {
			return static::saveUploadedFile($file, $filename);
		}

		if (is_string($file)) {
			return static::saveFileFromUrl($file, $filename);
		}
	}

	
	/**
	 * Save an uploaded file
	 * 
	 * @param array $file The uploaded file (from $_FILES array)
	 * @param string $filename An optional new filename
	 * 
	 * @return string The upload filename.
	 * @return null If no file has been uploaded
	 * @return false If there was an error
	 */
	public static function saveUploadedFile (array $file, $filename = null) {
		if (!empty($file['tmp_name']) && empty($file['error'])) {
			if ($filename === null) {
				$filename = $file['name'];
			}

			if (!pathinfo($filename, PATHINFO_EXTENSION) && ($extension = pathinfo($file['name'], PATHINFO_EXTENSION))) {
				$filename .= ".$extension";
			}

			$destination = static::$__assets.$filename;

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
	 * @param string $filename An optional new filename
	 * 
	 * @return string The upload filename.
	 * @return null If no file has been uploaded
	 * @return false If there was an error
	 */
	public static function saveFileFromUrl ($file, $filename = null) {
		if (!empty($file) && strpos($file, '://')) {
			if ($filename === null) {
				$filename = pathinfo($file, PATHINFO_BASENAME);
			} else if (!pathinfo($filename, PATHINFO_EXTENSION) && ($extension = pathinfo(parse_url($file, PHP_URL_PATH), PATHINFO_EXTENSION))) {
				$filename .= ".$extension";
			}

			try {
				$content = file_get_contents($file);

				if (!file_put_contents(static::$__assets.$filename, $content)) {
					return false;
				}
			} catch (\Exception $Exception) {
				return false;
			}

			return $filename;
		}
	}
}
