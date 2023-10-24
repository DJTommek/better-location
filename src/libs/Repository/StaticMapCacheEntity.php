<?php declare(strict_types=1);

namespace App\Repository;

class StaticMapCacheEntity extends Entity
{
	public readonly string $id;
	/**
	 * @see Warning: Nette\Http\Url cant be used, see https://github.com/nette/http/issues/178
	 */
	public readonly string $url;

	public static function fromRow(array $row): self
	{
		$entity = new self();
		$entity->id = $row['id'];
		$entity->url = $row['url'];
		return $entity;
	}
}
