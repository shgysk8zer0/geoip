<?php
namespace shgysk8zer0\GeoIP;

use \shgysk8zer0\PHPAPI\{NullLogger, URL};
use \shgysk8zer0\PHPAPI\Interfaces\{LoggerAwareInterface, LoggerInterface};
use \shgysk8zer0\PHPAPI\Traits\{LoggerAwareTrait};
use \Throwable;
use \Exception;
use \InvalidArgumentException;

final class GeoIPRequest implements LoggerAwareInterface
{
	use LoggerAwareTrait;

	private const ENDPOINT = 'http://api.ipstack.com';

	private $_key    = null;

	private $_ip     = null;

	private $_secure = false;

	final public function __construct(
		?string          $key    = null,
		?string          $ip     = null,
		?bool            $secure = null,
		?LoggerInterface $logger = null
	)
	{
		$this->setLogger($logger ?? new NullLogger());

		$this->setkey($key);
		$this->setIP($ip);

		if (isset($secure)) {
			$this->setSecure($secure);
		}
	}

	final public function __debugInfo(): array
	{
		return [
			'key' => $this->getKey(),
			'ip'  => $this->getIP(),
		];
	}

	final public function getIP():? string
	{
		return $this->_ip;
	}

	final public function setIP(?string $val): void
	{
		if (isset($val) and filter_var($val, FILTER_VALIDATE_IP) === false) {
			throw new InvalidArgumentException(sprintf('%s is not a valid IP address', $val));
		} else {
			$this->_ip = $val;
		}
	}

	final public function getKey():? string
	{
		return $this->_key;
	}

	final public function setKey(?string $val = null): void
	{
		$this->_key = $val;
	}

	final public function getSecure(): bool
	{
		return $this->_secure;
	}

	final public function setSecure(bool $val = true): void
	{
		$this->_secure = $val;
	}

	final public function send():? GeoIPResponse
	{
		$ip  = $this->getIP();
		$key = $this->getKey();

		if (isset($ip, $key)) {
			$url = new URL(self::ENDPOINT);
			$url->pathname = $ip;
			$url->searchParams->set('access_key', $key);

			if ($this->getSecure()) {
				$url->protocol = 'https:';
			}

			$this->logger->debug($url);
			$ch = curl_init($url);

			curl_setopt_array($ch, [
				CURLOPT_HEADER         => false,
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_CONNECTTIMEOUT => 120,
				CURLOPT_TIMEOUT        => 120,
				CURLOPT_FAILONERROR    => false,
				CURLOPT_HTTPHEADER     => ['Accept: application/json'],
			]);

			if ($result = curl_exec($ch) and $data = json_decode($result)) {
				curl_close($ch);

				return new GeoIPResponse($data, $this->logger);
			} else {
				curl_close($ch);

				$this->logger->error('[cURL error {errno}] {err}', [
					'errno' => curl_errno($ch),
					'err'   => curl_error($ch),
				]);
				return null;
			}
		} else {
			$this->logger->warning('Missing key or IP address');
			return null;
		}
	}
}
