<?php
namespace shgysk8zer0\GeoIP;

use \shgysk8zer0\PHPAPI\{NullLogger};
use \shgysk8zer0\PHPAPI\Interfaces\{LoggerAwareInterface, LoggerInterface};
use \shgysk8zer0\PHPAPI\Traits\{LoggerAwareTrait};
use \shgysk8zer0\PHPAPI\Abstracts\{HTTPStatusCodes as HTTP};
use \shgysk8zer0\PHPSchema\{PostalAddress, GeoCoordinates};
use \shgysk8zer0\PHPSchema\Interfaces\{PostalAddressInterface, GeoCoordinatesInterface};
use \JsonSerializable;
use \Throwable;

final class GeoIPResponse implements JsonSerializable, LoggerAwareInterface
{
	use LoggerAwareTrait;

	private $_ip = '';

	private $_address = null;

	private $_geo = null;

	private $_error = null;

	final public function __construct(object $data, ?LoggerInterface $logger = null)
	{
		if (isset($logger)) {
			$this->setLogger($logger);
		} else {
			$this->setLogger(new NullLogger());
		}

		$this->_address = new PostalAddress();
		$this->_geo     = new GeoCoordinates();

		if (isset($data, $data->success, $data->error) and $data->success === false) {
			$this->_error = new GeoIPException($data->error->info, $data->error->code, $data->error->type);
		} elseif (isset($data)) {
			$this->_setData($data);
		}
	}

	final public function __debugInfo(): array
	{
		return [
			'address' => $this->getAddress(),
			'geo'     => $this->getGeo(),
			'ip'      => $this->getIP(),
			'error'   => $this->getError(),
		];
	}

	final public function jsonSerialize(): array
	{
		return [
			'status'  => $this->getStatus(),
			'address' => $this->getAddress(),
			'geo'     => $this->getGeo(),
			'ip'      => $this->getIP(),
			'error'   => $this->getError(),
		];
	}

	final public function getAddress(): PostalAddressInterface
	{
		return $this->_address;
	}

	final public function getGeo(): GeoCoordinatesInterface
	{
		return $this->_geo;
	}

	final public function getIP(): string
	{
		return $this->_ip;
	}

	final public function hasError(): bool
	{
		return isset($this->_error);
	}

	final public function getError():? Throwable
	{
		return $this->_error;
	}

	final public function getStatus(): int
	{
		if ($this->getIP() !== '') {
			return HTTP::OK;
		} elseif ($this->hasError()) {
			return HTTP::INTERNAL_SERVER_ERROR;
		} else {
			return HTTP::BAD_GATEWAY;
		}
	}

	final protected function _setData(object $data): void
	{
		$this->_ip = $data->ip;

		$this->_address->setAddressLocality($data->city);
		$this->_address->setAddressRegion($data->region_code);
		$this->_address->setPostalCode($data->zip);
		$this->_address->setAddressCountry($data->country_code);

		$this->_geo->setLatitude($data->latitude);
		$this->_geo->setLongitude($data->longitude);
	}
}
