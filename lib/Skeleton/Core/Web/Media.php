<?php
/**
 * Media detection and serving of media files
 *
 * @author Christophe Gosiau <christophe@tigron.be>
 * @author Gerry Demaret <gerry@tigron.be>
 * @author David Vandemaele <david@tigron.be>
 */

namespace Skeleton\Core\Web;

use Skeleton\Core\Application;

class Media {

	/**
	 * Request uri
	 *
	 * @access private
	 * @var string $request_uri
	 */
	protected $request_uri = null;

	/**
	 * Path
	 *
	 * @access private
	 * @var string $path
	 */
	protected $path = null;

	/**
	 * Mtime
	 *
	 * @access private
	 * @var int $mtime
	 */
	protected $mtime = null;

	/**
	 * Image extensions
	 *
	 * @var array $filetypes
	 * @access protected
	 */
	protected static $filetypes = [
		'css' => [
			'css',
			'map',
		],
		'doc' => [
			'pdf',
			'txt',
		],
		'font' => [
			'woff',
			'woff2',
			'ttf',
			'otf',
			'eot'
		],
		'image' => [
			'gif',
			'jpg',
			'jpeg',
			'png',
			'ico',
			'svg',
		],
		'javascript' => [
			'js',
		],
		'tools' => [
			'html',
			'htm',
		],
		'video' => [
			'mp4',
			'mkv',
		]
	];

	/**
	 * Constructor
	 *
	 * @access public
	 * @param string $request_uri
	 */
	public function __construct($request_uri) {
		$this->request_uri = $request_uri;
	}

	/**
	 * check for known extension
	 *
	 * @access public
	 * @return known extension
	 */
	public function has_known_extension() {
		$pathinfo = pathinfo($this->request_uri);
		if (!isset($pathinfo['extension'])) {
			return false;
		}

		$known_extension = false;
		foreach (self::$filetypes as $filetype => $extensions) {
			if (in_array($pathinfo['extension'], $extensions)) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Get path
	 *
	 * @access private
	 */
	protected function get_path() {
		if ($this->path === null) {
			$pathinfo = pathinfo($this->request_uri);
			$filepaths = [];

			// Add the media_path from the current application
			foreach (self::$filetypes as $filetype => $extensions) {
				if (!in_array($pathinfo['extension'], $extensions)) {
					continue;
				}
				$filepaths[] = Application::get()->media_path . '/' . $filetype . '/' . $pathinfo['dirname'] . '/' . $pathinfo['basename'];
			}

			// Add the global asset directory
			$filepaths[] = \Skeleton\Core\Config::$asset_dir . '/' . $pathinfo['dirname'] . '/' . $pathinfo['basename'];

			// Add the asset path of every package
			$packages = \Skeleton\Core\Package::get_all();

			foreach ($packages as $package) {
				$path_parts = array_values(array_filter(explode('/', $this->request_uri)));
				if (!isset($path_parts[0]) || $path_parts[0] != $package->name) {
					continue;
				}

				foreach (self::$filetypes as $filetype => $extensions) {
					if (!in_array($pathinfo['extension'], $extensions)) {
						continue;
					}

					unset($path_parts[0]);
					$package_path = $package->asset_path . '/' . $filetype . '/' . $pathinfo['basename'];
					$filepaths[] = $package_path;
				}
			}

			// Search for the file in order provided in $filepaths
			foreach ($filepaths as $filepath) {
				if (file_exists($filepath)) {
					$this->path = $filepath;
				}
			}

			if ($this->path === null) {
				return self::fail();
			}
		}

		return $this->path;
	}

	/**
	 * Serve the media
	 *
	 * @access public
	 */
	public function serve() {
		// Send the Etag before potentially replying with 304
		header('Etag: ' . crc32($this->get_mtime()) . '-' . sha1($this->get_path()));
		$this->http_if_modified();
		$this->serve_cache();
		$this->serve_content();
		exit;
	}

	/**
	 * Send cache headers
	 *
	 * @access private
	 */
	private function serve_cache() {
		$gmt_mtime = gmdate('D, d M Y H:i:s', $this->get_mtime()).' GMT';

		header('Cache-Control: public');
		header('Pragma: public');
		header('Last-Modified: '. $gmt_mtime);
		header('Expires: ' . gmdate('D, d M Y H:i:s', strtotime('+30 minutes')).' GMT');
	}

	/**
	 * Serve the file at $filename. Supports HTTP ranges if the browser requests
	 * them.
	 *
	 * When there is a request for a single range, the content is transmitted
	 * with a Content-Range header, and a Content-Length header showing the
	 * number of bytes actually transferred.
	 *
	 * When there is a request for multiple ranges, these are transmitted as a
	 * multipart message. The multipart media type used for this purpose is
	 * "multipart/byteranges".
	 *
	 * The HTTP range support is based on the work of rvflorian@github and
	 * DannyNiu@github. See https://github.com/rvflorian/byte-serving-php/
	 *
	 * @access private
	 */
	private function serve_content() {
		$filename = $this->get_path();
		$filesize = filesize($filename);

		$mimetype = $this->get_mime_type();

		// open the file for reading in binary mode
		$file = fopen($filename, 'rb');

		// reset the pointer to make sure we're at the beginning
		fseek($file, 0, SEEK_SET);

		$ranges = null;
		if (
			$_SERVER['REQUEST_METHOD'] === 'GET' &&
			isset($_SERVER['HTTP_RANGE']) &&
			$range = stristr(trim($_SERVER['HTTP_RANGE']), 'bytes=')
		) {
			$range = substr($range, 6); // 6 == strlen('bytes=')
			$boundary = bin2hex(random_bytes(48)); // generate a random boundary
			$ranges = explode(',', $range);
		}

		if ($ranges !== null && count($ranges)) {
			http_response_code(206);
			header('Accept-Ranges: bytes');

			if (count($ranges) > 1) {
				// more than one range is requested

				// compute content length
				$content_length = 0;
				foreach ($ranges as $range){
					$first = $last = 0;
					$this->serve_content_set_range($range, $filesize, $first, $last);
					$content_length += strlen("\r\n--" . $boundary . "\r\n");
					$content_length += strlen('Content-Type: ' . $mimetype . "\r\n");
					$content_length += strlen('Content-Range: bytes ' . $first . '-' . $last . '/' . $filesize . "\r\n\r\n");
					$content_length += $last - $first + 1;
				}

				$content_length += strlen("\r\n--" . $boundary . "--\r\n");

				// output headers
				header('Content-Length: ' . $content_length);

				// see http://httpd.apache.org/docs/misc/known_client_problems.html
				// and https://docs.oracle.com/cd/B14098_01/web.1012/q20206/misc/known_client_problems.html
				// for a discussion on x-byteranges vs. byteranges
				header('Content-Type: multipart/x-byteranges; boundary=' . $boundary);

				// output the content
				foreach ($ranges as $range) {
					$first = $last = 0;
					$this->serve_content_set_range($range, $filesize, $first, $last);
					echo "\r\n--" . $boundary . "\r\n";
					echo 'Content-Type: ' . $mimetype . "\r\n";
					echo 'Content-Range: bytes ' . $first . '-' . $last . '/' . $filesize . "\r\n\r\n";
					fseek($file, $first);
					$this->serve_content_buffered_read($file, $last - $first + 1);
				}

				echo "\r\n--" . $boundary . "--\r\n";
			} else {
				// a single range is requested
				$range = $ranges[0];

				$first = $last = 0;
				$this->serve_content_set_range($range, $filesize, $first, $last);

				header('Content-Length: ' . ($last - $first + 1));
				header('Content-Range: bytes ' . $first . '-' . $last . '/' . $filesize);
				header('Content-Type: '. $mimetype);

				fseek($file, $first);
				$this->serve_content_buffered_read($file, $last - $first + 1);
			}
		} else {
			// no byteserving
			header('Accept-Ranges: bytes');
			header('Content-Length: ' . $filesize);
			header('Content-Type: ' . $mimetype);

			fseek($file, 0);
			$this->serve_content_buffered_read($file, $filesize);
		}

		fclose($file);
		return;
	}

	/**
	* Sets the first and last bytes of a range, given a range expressed as a
	* string and the size of the file.
	*
	* If the end of the range is not specified, or the end of the range is
	* greater than the length of the file, $last is set as the end of the file.
	*
	* If the begining of the range is not specified, the meaning of the value
	* after the dash is "get the last n bytes of the file".
	*
	* If $first is greater than $last, the range is not satisfiable, and we
	* should return a response with a status of 416 (Requested range not
	* satisfiable).
	*
	* Examples:
	* $range='0-499', $filesize=1000 => $first=0, $last=499
	* $range='500-', $filesize=1000 => $first=500, $last=999
	* $range='500-1200', $filesize=1000 => $first=500, $last=999
	* $range='-200', $filesize=1000 => $first=800, $last=999
	*
	* @access private
	* @param string $range
	* @param int $filezise
	* @param int $first
	* @param int $last
	*/
	private function serve_content_set_range(string $range, int $filesize, int &$first, int &$last) {
		$dash = strpos($range, '-');
		$first = trim(substr($range, 0, $dash));
		$last = trim(substr($range, $dash + 1));

		if ($first == '') {
			// suffix byte range: gets last n bytes
			$suffix = $last;
			$last = $filesize - 1;
			$first = $filesize - $suffix;

			if ($first < 0) {
				$first = 0;
			}
		} elseif ($last=='' || $last > $filesize - 1) {
			$last = $filesize - 1;
		}

		if ($first > $last) {
			// unsatisfiable range
			http_response_code(416);
			header('Status: 416 Requested range not satisfiable');
			header('Content-Range: */' . $filesize);
			die(); // FIXME
		}
	}

	/**
	* Outputs up to $bytes from the file $file to standard output, $buffer_size
	* bytes at a time. $file may be pre-seeked to a sub-range of a larger file.
	*
	* @access private
	* @param resource $file
	* @param int bytes
	* @param int buffer_size
	*/
	private function serve_content_buffered_read($file, int $bytes, int $buffer_size = 1024) {
		$bytes_left = $bytes;

		while ($bytes_left > 0 && !feof($file)) {
			$bytes_to_read = min($buffer_size, $bytes_left);
			$bytes_left -= $bytes_to_read;
			$contents = fread($file, $bytes_to_read);
			echo $contents;
			flush();
		}
	}

	/**
	 * Handle HTTP_IF_MODIFIED
	 *
	 * @access private
	 */
	private function http_if_modified() {
		if (!isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])) {
			return;
		}

		if ($_SERVER['HTTP_IF_MODIFIED_SINCE'] == gmdate('D, d M Y H:i:s', $this->get_mtime()).' GMT') {
			header('Expires: ');
			HTTP\Status::code_304();
		}
	}

	/**
	 * Get the modified time of the media
	 *
	 * @access private
	 * @return int $mtime
	 */
	private function get_mtime() {
		if ($this->mtime === null) {
			clearstatcache(true, $this->get_path());
			$this->mtime = filemtime($this->get_path());
		}

		return $this->mtime;
	}

	/**
	 * Get the mime type of a file
	 *
	 * @access private
	 * @return string $mime_type
	 */
	private function get_mime_type() {
		$pathinfo = pathinfo($this->request_uri);
		$mime_type = '';
		switch ($pathinfo['extension']) {
			case 'htm' :
			case 'html': $mime_type = 'text/html';
			             break;

			case 'css' : $mime_type = 'text/css';
			             break;

			case 'ico' : $mime_type = 'image/x-icon';
			             break;

			case 'js'  : $mime_type = 'text/javascript';
			             break;

			case 'png' : $mime_type = 'image/png';
			             break;

			case 'gif' : $mime_type = 'image/gif';
			             break;

			case 'jpg' :
			case 'jpeg': $mime_type = 'image/jpeg';
			             break;

			case 'pdf' : $mime_type = 'application/pdf';
						 break;

			case 'txt' : $mime_type = 'text/plain';
						 break;

			case 'svg' : $mime_type = 'image/svg+xml';
						 break;

			default    : $mime_type = 'application/octet-stream';
		}

		return $mime_type;
	}

	/**
	 * Fail
	 *
	 * @access protected
	 */
	private static function fail() {
		$application = \Skeleton\Core\Application::get();
		if ($application->event_exists('media', 'not_found')) {
			$application->call_event_if_exists('media', 'not_found');
		} else {
			throw new \Skeleton\Core\Exception\Media\Not\Found('File not found');
		}

		exit;
	}

	/**
	 * Detect if the request is a request for media
	 *
	 * @param $request array
	 * @access public
	 */
	public static function detect($request_uri): bool {
		// Don't bother looking up /
		if ($request_uri == '/') {
			return false;
		}

		$request = pathinfo($request_uri);

		if (!isset($request['extension'])) {
			return false;
		}

		$classname = get_called_class();
		$media = new $classname($request_uri);
		if (!$media->has_known_extension()) {
			return false;
		}

		$media->serve();
		return true;
	}
}
