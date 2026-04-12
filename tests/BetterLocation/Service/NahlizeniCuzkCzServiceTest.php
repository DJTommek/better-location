<?php declare(strict_types=1);

namespace Tests\BetterLocation\Service;

use App\BetterLocation\Service\NahlizeniCuzkCzService;

final class NahlizeniCuzkCzServiceTest extends AbstractServiceTestCase
{
	protected bool $revalidateGeneratedShareLink = false;

	protected function getServiceClass(): string
	{
		return NahlizeniCuzkCzService::class;
	}

	protected function getShareLinks(): array
	{
		return [
			'https://nahlizenidokn.cuzk.gov.cz/MapaIdentifikace.aspx?l=KN&x=-742851&y=-1043009',
			'https://nahlizenidokn.cuzk.gov.cz/MapaIdentifikace.aspx?l=KN&x=-737038&y=-1042397',
			'https://nahlizenidokn.cuzk.gov.cz/MapaIdentifikace.aspx?l=KN&x=-2081295&y=-17312015',
			'https://nahlizenidokn.cuzk.gov.cz/MapaIdentifikace.aspx?l=KN&x=-2694508&y=-330895',
			'https://nahlizenidokn.cuzk.gov.cz/MapaIdentifikace.aspx?l=KN&x=-8358203&y=-17001769',
		];
	}

	protected function getDriveLinks(): array
	{
		return [];
	}
}
