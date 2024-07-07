<?php declare(strict_types=1);

namespace App\Web\Favorites;

use App\Repository\FavouritesEntity;
use App\Web\LayoutTemplate;

class FavoritesTemplate extends LayoutTemplate
{
	/**
	 * @var array<FavouritesEntity>
	 */
	public array $favorites;

	#[\Latte\Attributes\TemplateFunction]
	public function allLatLon(): string
	{
		$latLons = array_map(fn(FavouritesEntity $favorite) => $favorite->getLatLon(), $this->favorites);
		return implode(';', $latLons);
	}
}

