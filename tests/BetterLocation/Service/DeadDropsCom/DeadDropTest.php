<?php declare(strict_types=1);

namespace Tests\BetterLocation\Service\DeadDropsCom;

use App\BetterLocation\Service\DeadDropsCom\DeadDrop;
use DJTommek\Coordinates\CoordinatesImmutable;
use PHPUnit\Framework\TestCase;

final class DeadDropTest extends TestCase
{
	private const string FIXTURES_DIR = __DIR__ . '/../../../fixtures/httpTestClient/deaddrops_com';

	public static function isWorkingProvider(): array
	{
		return [
			[true, DeadDrop::STATUS_WORKING],
			[false, DeadDrop::STATUS_UNCONFIRMED],
			[false, DeadDrop::STATUS_BROKEN],
			[false, 'some unknown status'],
			[false, null],
		];
	}

	/**
	 * @dataProvider isWorkingProvider
	 */
	public function testIsWorking(bool $expected, ?string $status): void
	{
		$deadDrop = self::createDeadDrop(status: $status);
		$this->assertSame($expected, $deadDrop->isWorking());
	}

	public static function isStatusOutdatedProvider(): array
	{
		return [
			'no date' => [false, null, '2026-10-22 12:00:00', 'P1Y'],
			'recently changed' => [false, '2026-09-25 20:50:33', '2026-10-22 12:00:00', 'P1Y'],
			'exactly on threshold' => [false, '2025-10-22 12:00:00', '2026-10-22 12:00:00', 'P1Y'],
			'one second over threshold' => [true, '2025-10-22 11:59:59', '2026-10-22 12:00:00', 'P1Y'],
			'long time ago' => [true, '2021-12-17 20:42:11', '2026-10-22 12:00:00', 'P1Y'],
			'custom interval, not outdated' => [false, '2026-10-20 12:00:00', '2026-10-22 12:00:00', 'P7D'],
			'custom interval, outdated' => [true, '2026-10-01 12:00:00', '2026-10-22 12:00:00', 'P7D'],
		];
	}

	/**
	 * @dataProvider isStatusOutdatedProvider
	 */
	public function testIsStatusOutdated(bool $expected, ?string $statusChanged, string $now, string $maxAge): void
	{
		$utc = new \DateTimeZone('UTC');
		$deadDrop = self::createDeadDrop(
			status: DeadDrop::STATUS_WORKING,
			statusChanged: $statusChanged === null ? null : new \DateTimeImmutable($statusChanged, $utc),
		);
		$this->assertSame($expected, $deadDrop->isStatusOutdated(new \DateTimeImmutable($now, $utc), new \DateInterval($maxAge)));
	}

	/**
	 * USB drop with all information filled, status "working" and three pictures.
	 */
	public function testFromHtmlFull(): void
	{
		$deadDrop = DeadDrop::fromHtml(self::fixture(2413));
		$this->assertNotNull($deadDrop);

		$this->assertSame(48.8105, $deadDrop->coords->lat);
		$this->assertSame(14.3183, $deadDrop->coords->lon);
		$this->assertSame('USB v Myší díře', $deadDrop->name);
		$this->assertSame('USB drop', $deadDrop->type);
		$this->assertSame('16 GB', $deadDrop->size);
		$this->assertSame('2019-08-28T00:00:00+00:00', $deadDrop->dateCreated?->format(DATE_ATOM));
		$this->assertSame('Myší díra, Český Krumlov Jihočeský kraj, CZ', $deadDrop->address);
		$this->assertSame(DeadDrop::STATUS_WORKING, $deadDrop->status);
		$this->assertSame('2026-09-25T20:50:33+00:00', $deadDrop->statusChanged?->format(DATE_ATOM));
		$this->assertSame('USB se nachází u „Myší díry“ a je určena pro vše možné!', $deadDrop->about);
		$this->assertSame(
			[
				'Overview' => 'https://deaddrops.com/db/images/fullsize/2413/172689.jpg',
				'Medium distance' => 'https://deaddrops.com/db/images/fullsize/2413/210934.jpg',
				'Closeup' => 'https://deaddrops.com/db/images/fullsize/2413/665303.jpg',
			],
			$deadDrop->pictures,
		);
		$this->assertTrue($deadDrop->isWorking());
	}

	/**
	 * Wireless drop without size, about and pictures and with negative longitude.
	 */
	public function testFromHtmlMissingOptionalValues(): void
	{
		$deadDrop = DeadDrop::fromHtml(self::fixture(1579));
		$this->assertNotNull($deadDrop);

		$this->assertSame(57.9719, $deadDrop->coords->lat);
		$this->assertSame(-3.98007, $deadDrop->coords->lon);
		$this->assertSame('Golspie Dead Drop', $deadDrop->name);
		$this->assertSame('Wireless drop', $deadDrop->type);
		$this->assertNull($deadDrop->size);
		$this->assertSame('2015-01-28T00:00:00+00:00', $deadDrop->dateCreated?->format(DATE_ATOM));
		$this->assertSame('Main Street, Golspie Sutherland, GB', $deadDrop->address);
		$this->assertSame(DeadDrop::STATUS_UNCONFIRMED, $deadDrop->status);
		$this->assertSame('2024-12-11T00:31:09+00:00', $deadDrop->statusChanged?->format(DATE_ATOM));
		$this->assertNull($deadDrop->about);
		$this->assertSame([], $deadDrop->pictures);
		$this->assertFalse($deadDrop->isWorking());
	}

	/**
	 * Broken drop with only one picture.
	 */
	public function testFromHtmlBrokenOnePicture(): void
	{
		$deadDrop = DeadDrop::fromHtml(self::fixture(1580));
		$this->assertNotNull($deadDrop);

		$this->assertSame('Julia', $deadDrop->name);
		$this->assertSame(DeadDrop::STATUS_BROKEN, $deadDrop->status);
		$this->assertSame('2021-12-17T21:08:12+00:00', $deadDrop->statusChanged?->format(DATE_ATOM));
		$this->assertSame('do not let your fears take place of your dreams', $deadDrop->about);
		$this->assertSame(['Overview' => 'https://deaddrops.com/db/images/fullsize/1580/234888.jpg'], $deadDrop->pictures);
	}

	/**
	 * Name with HTML entities is decoded.
	 */
	public function testFromHtmlNameWithEntities(): void
	{
		$deadDrop = DeadDrop::fromHtml(self::fixture(500));
		$this->assertNotNull($deadDrop);

		$this->assertSame('"Tripoli"', $deadDrop->name);
		$this->assertSame(51.4754, $deadDrop->coords->lat);
		$this->assertSame(-0.037693, $deadDrop->coords->lon);
	}

	/**
	 * Page of dead drop that does not exist.
	 */
	public function testFromHtmlNotExisting(): void
	{
		$this->assertNull(DeadDrop::fromHtml(self::fixture(9999999)));
	}

	public function testFromHtmlMissingCoordinates(): void
	{
		$this->assertNull(DeadDrop::fromHtml(self::html(coordsHref: null)));
		$this->assertNull(DeadDrop::fromHtml(self::html(coordsHref: 'https://www.openstreetmap.org/?mlat=48.8105')));
		$this->assertNull(DeadDrop::fromHtml(self::html(coordsHref: 'https://www.openstreetmap.org/#map=17/48.8105/14.3183')));
	}

	public function testFromHtmlMinimal(): void
	{
		$deadDrop = DeadDrop::fromHtml(self::html());
		$this->assertNotNull($deadDrop);

		$this->assertSame(50.087451, $deadDrop->coords->lat);
		$this->assertSame(-14.420671, $deadDrop->coords->lon);
		$this->assertNull($deadDrop->name);
		$this->assertNull($deadDrop->type);
		$this->assertNull($deadDrop->size);
		$this->assertNull($deadDrop->dateCreated);
		$this->assertNull($deadDrop->address);
		$this->assertNull($deadDrop->status);
		$this->assertNull($deadDrop->statusChanged);
		$this->assertNull($deadDrop->about);
		$this->assertSame([], $deadDrop->pictures);
	}

	/**
	 * Empty values are ignored, whitespaces are normalized and invalid date is ignored.
	 */
	public function testFromHtmlInformation(): void
	{
		$deadDrop = DeadDrop::fromHtml(self::html(information: [
			'Name' => "  Some \n\t  name  ",
			'Drop-Type' => 'USB drop',
			'Size' => '   ',
			'Date Created' => '0000-00-00 invalid',
		]));
		$this->assertNotNull($deadDrop);

		$this->assertSame('Some name', $deadDrop->name);
		$this->assertSame('USB drop', $deadDrop->type);
		$this->assertNull($deadDrop->size);
		$this->assertNull($deadDrop->dateCreated);
	}

	public function testFromHtmlStatusWithoutDate(): void
	{
		$deadDrop = DeadDrop::fromHtml(self::html(status: 'Status was set to <em>broken/dead/stolen/gone</em>'));
		$this->assertNotNull($deadDrop);

		$this->assertSame(DeadDrop::STATUS_BROKEN, $deadDrop->status);
		$this->assertNull($deadDrop->statusChanged);
	}

	public function testFromHtmlPictures(): void
	{
		$deadDrop = DeadDrop::fromHtml(self::html(pictures: [
			'Relative' => 'images/fullsize/1/1.jpg',
			'Relative with dot' => './images/fullsize/1/2.jpg',
			'Absolute' => 'https://example.com/picture.jpg',
			'Without link' => null,
		]));
		$this->assertNotNull($deadDrop);

		$this->assertSame(
			[
				'Relative' => 'https://deaddrops.com/db/images/fullsize/1/1.jpg',
				'Relative with dot' => 'https://deaddrops.com/db/images/fullsize/1/2.jpg',
				'Absolute' => 'https://example.com/picture.jpg',
			],
			$deadDrop->pictures,
		);
	}

	private static function fixture(int $id): string
	{
		$files = glob(sprintf('%s/deaddrops_com_db_page_view_id_%d_*.response', self::FIXTURES_DIR, $id));
		assert($files !== false && count($files) === 1);
		return file_get_contents($files[0]);
	}

	/**
	 * Build minimal page with the same structure as real dead drop detail page.
	 *
	 * @param array<string, string> $information
	 * @param array<string, ?string> $pictures Label => link to fullsize picture (null to omit link)
	 */
	private static function html(
		?string $coordsHref = 'https://www.openstreetmap.org/?mlat=50.087451&mlon=-14.420671#map=17/50.087451/-14.420671',
		array $information = [],
		?string $status = null,
		array $pictures = [],
	): string {
		$rows = '';
		foreach ($information as $label => $value) {
			$rows .= sprintf('<tr><td><b>%s: </b></td><td>%s</td></tr>', $label, $value);
		}
		if ($coordsHref !== null) {
			$rows .= sprintf('<tr><td><b>Coordinates: </b></td><td><a id="coord_link" href="%s">coords</a></td></tr>', htmlspecialchars($coordsHref));
		}

		$pictureCards = '';
		foreach ($pictures as $label => $href) {
			$link = $href === null ? '' : sprintf('<a href="%s"><img src="thumb.jpg"></a>', $href);
			$pictureCards .= sprintf('<div class="picture-card"><div class="picture-label">%s</div>%s</div>', $label, $link);
		}

		$statusTable = $status === null ? '' : sprintf('<table><tr><td class="td_menu" colspan="2"><b>Status</b></td></tr><tr><td>%s</td><td></td></tr></table>', $status);

		return <<<HTML
			<!DOCTYPE html>
			<html><head><meta charset="utf-8"></head><body>
			<table><tr><td colspan="2" class="td_menu"><b>Information</b></td></tr>$rows</table>
			<div class="pictures-grid">$pictureCards</div>
			$statusTable
			</body></html>
			HTML;
	}

	private static function createDeadDrop(?string $status = null, ?\DateTimeImmutable $statusChanged = null): DeadDrop
	{
		return new DeadDrop(
			coords: new CoordinatesImmutable(50, 14),
			name: null,
			type: null,
			size: null,
			dateCreated: null,
			address: null,
			status: $status,
			statusChanged: $statusChanged,
			about: null,
			pictures: [],
		);
	}
}
