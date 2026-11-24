<?php declare(strict_types=1);

namespace Tests\IngressPortalnik\Types;

use App\Ingress\Faction;
use App\Ingress\MissionKind;
use App\Ingress\WaypointKind;
use App\Ingress\WaypointObjective;
use App\IngressPortalnik\Types\MissionType;
use DJTommek\Coordinates\CoordinatesInterface;
use Nette\Utils\Json;
use PHPUnit\Framework\TestCase;

/**
 * Parsing edge-cases, which are not (reliably) available in real API data.
 */
final class MissionTypeTest extends TestCase
{
	private static function missionData(array $override = []): \stdClass
	{
		return Json::decode(Json::encode($override + [
			'guid' => 'b9d1285bdf184780a79f1a00b68e893e.1c',
			'title' => ' Some mission ',
			'image_url' => null,
			'description' => '',
			'author_name' => 'Agent',
			'author_faction' => 'enlightened',
			'type' => 'hidden',
			'has_details' => true,
			'first_seen_at' => '2020-01-02T03:04:05.000Z',
			'last_seen_at' => '2026-11-20T10:00:00.000Z',
			'updated_at' => '2025-06-01T12:00:00.000Z',
		]));
	}

	private static function waypointData(int $position, ?float $lat, ?float $lng, array $override = []): \stdClass
	{
		return Json::decode(Json::encode($override + [
			'position' => $position,
			'type' => 'portal',
			'objective' => 'hack',
			'title' => 'Waypoint ' . $position,
			'hidden' => $lat === null,
			'lat' => $lat,
			'lng' => $lng,
			'portal_guid' => null,
		]));
	}

	public function testBasic(): void
	{
		$mission = MissionType::createFromVariable(self::missionData());
		$this->assertSame('Some mission', $mission->getTitle());
		$this->assertNull($mission->getDescription());
		$this->assertSame(Faction::Enlightened, $mission->getAuthorFaction());
		$this->assertSame(MissionKind::Hidden, $mission->getKind());
		$this->assertNull($mission->getImageLink(100));
		$this->assertSame([], $mission->getWaypoints());
		$this->assertNull($mission->getStartWaypoint());
	}

	public function testUnknownEnumValuesAreNull(): void
	{
		$mission = MissionType::createFromVariable(
			self::missionData(['author_faction' => 'some-new-faction', 'type' => null]),
			[self::waypointData(0, 50.087451, 14.420671, ['type' => 'some-new-type', 'objective' => null])],
		);
		$this->assertNull($mission->getAuthorFaction());
		$this->assertNull($mission->getKind());
		$this->assertNull($mission->getWaypoints()[0]->getKind());
		$this->assertNull($mission->getWaypoints()[0]->getObjective());
	}

	/**
	 * Waypoints are sorted by position and start waypoint is first waypoint with known location.
	 */
	public function testHiddenAndUnsortedWaypoints(): void
	{
		$mission = MissionType::createFromVariable(self::missionData(), [
			self::waypointData(2, 50.087354, 14.421306, ['objective' => 'enter_passphrase']),
			self::waypointData(0, null, null, ['type' => null, 'objective' => null, 'title' => null]),
			self::waypointData(1, 50.087451, 14.420671, ['type' => 'field_trip', 'objective' => 'view_field_trip']),
		]);

		$waypoints = $mission->getWaypoints();
		$this->assertSame([0, 1, 2], array_map(fn($waypoint) => $waypoint->getPosition(), $waypoints));

		$this->assertTrue($waypoints[0]->isHidden());
		$this->assertNull($waypoints[0]->getCoordinates());
		$this->assertNull($waypoints[0]->getTitle());

		$this->assertSame(WaypointKind::FieldTrip, $waypoints[1]->getKind());
		$this->assertSame(WaypointObjective::ViewFieldTrip, $waypoints[1]->getObjective());
		$this->assertInstanceOf(CoordinatesInterface::class, $waypoints[1]->getCoordinates());

		$this->assertSame(WaypointObjective::EnterPassphrase, $waypoints[2]->getObjective());

		$this->assertSame($waypoints[1], $mission->getStartWaypoint());
	}
}
