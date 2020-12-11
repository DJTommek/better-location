<?php declare(strict_types=1);

namespace App\Foursquare\Types;

/**
 * @see https://developer.foursquare.com/docs/api-reference/venues/details/#response-fields
 */
class VenueType extends Type
{
	/** @var string A unique string identifier for this venue. */
	public $id;
	/** @var string The best known name for this venue. */
	public $name;
	/** @var \stdClass */
	public $contact;
	/** @var VenueLocationType */
	public $location;
	/** @var string */
	public $canonicalUrl;
	/** @var \stdClass[] */
	public $categories;
	/** @var bool */
	public $verified;
	/** @var bool */
	public $venueRatingBlacklisted;
	/** @var \stdClass */
	public $stats;
	/** @var string */
	public $url;
	/** @var \stdClass */
	public $price;
	/** @var \stdClass */
	public $likes;
	/** @var bool */
	public $dislike;
	/** @var bool */
	public $ok;
	/** @var float */
	public $rating;
	/** @var string */
	public $ratingColor;
	/** @var int */
	public $ratingSignals;
	/** @var bool */
	public $allowMenuUrlEdit;
	/** @var \stdClass */
	public $beenHere;
	/** @var \stdClass */
	public $specials;
	/** @var \stdClass */
	public $photos;
	/** @var \stdClass */
	public $reasons;
	/** @var \stdClass */
	public $hereNow;
	/** @var \DateTimeImmutable */
	public $createdAt;
	/** @var \stdClass */
	public $tips;
	/** @var string */
	public $shortUrl;
	/** @var string */
	public $timeZone;
	/** @var \stdClass */
	public $listed;
	/** @var \stdClass */
	public $hours;
	/** @var \stdClass */
	public $popular;
	/** @var \stdClass */
	public $seasonalHours;
	/** @var \stdClass */
	public $defaultHours;
	/** @var \stdClass */
	public $pageUpdates;
	/** @var \stdClass */
	public $venuePage;
	/** @var \stdClass */
	public $page;
	/** @var string */
	public $description;
	/** @var \stdClass */
	public $inbox;
	/** @var \stdClass */
	public $attributes;
	/** @var \stdClass */
	public $bestPhoto;
	/** @var \stdClass */
	public $colors;
}
