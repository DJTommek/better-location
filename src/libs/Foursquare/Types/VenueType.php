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
	/** @var VenueContact */
	public $contact;
	/** @var VenueLocationType */
	public $location;
	/** @var string */
	public $canonicalUrl;
	/** @var VenueCategory[] */
	public $categories;
	/** @var bool */
	public $verified;
	/** @var VenueStats */
	public $stats;
	/** @var string */
	public $url;
	/** @var VenuePrice */
	public $price;
	/** @var VenueLikes */
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
	/** @var VenueBeenHere */
	public $beenHere;
	/** @var VenueSpecials */
	public $specials;
	/** @var VenuePhotos */
	public $photos;
	/** @var VenueReasons */
	public $reasons;
	/** @var VenueHereNow */
	public $hereNow;
	/** @var \DateTimeImmutable */
	public $createdAt;
	/** @var VenueTips */
	public $tips;
	/** @var string */
	public $shortUrl;
	/** @var string */
	public $timeZone;
	/** @var VenueListed */
	public $listed;
	/** @var VenueHours */
	public $hours;
	/** @var VenuePopular */
	public $popular;
	/** @var VenueSeasonHours */
	public $seasonalHours;
	/** @var VenueDefaultHours */
	public $defaultHours;
	/** @var VenuePageUpdates */
	public $pageUpdates;
	/** @var VenueInbox */
	public $inbox;
	/** @var VenueAttributes */
	public $attributes;
	/** @var VenueBestPhoto */
	public $bestPhoto;
	/** @var VenueColors */
	public $colors;
}
