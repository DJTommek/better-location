<?php declare(strict_types=1);

namespace App\Repository;

class StaticMapCacheEntity extends Entity
{
	/**
	 * @var int
	 * @readonly
	 */
	public $id;
	/**
	 * @var string
	 * @see Warning: Nette\Http\Url cant be used, see https://github.com/nette/http/issues/178
	 * @readonly
	 */
	public $url;

	public static function fromRow(array $row): self
	{
		$entity = new self();
		$entity->id = $row['id'];
		$entity->url = $row['url'];
		return $entity;
	}
}
