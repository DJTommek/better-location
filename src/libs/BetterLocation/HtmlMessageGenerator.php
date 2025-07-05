<?php declare(strict_types=1);

namespace App\BetterLocation;

use App\Address\AddressInterface;
use App\BetterLocation\Service\AbstractService;
use App\Icons;
use App\TelegramCustomWrapper\BetterLocationMessageSettings;
use DJTommek\Coordinates\CoordinatesInterface;
use Tracy\Debugger;

readonly class HtmlMessageGenerator implements MessageGeneratorInterface
{
	protected const NEWLINE = '<br>';

	public function __construct(
		private ServicesManager $servicesManager,
	) {
	}

	/**
	 * @param array<class-string<AbstractService>,string> $pregeneratedLinks
	 * @param list<Description> $descriptions
	 */
	public function generate(
		CoordinatesInterface $coordinates,
		BetterLocationMessageSettings $settings,
		string $prefixMessage,
		?string $coordinatesSuffixMessage = null,
		array $pregeneratedLinks = [],
		array $descriptions = [],
		?AddressInterface $address = null,
	): string {
		$result = $prefixMessage;

		$screenshotLink = $this->generateScreenshotLink($coordinates, $settings);
		if ($screenshotLink) {
			$result .= ' ' . self::href($screenshotLink, Icons::MAP_SCREEN);
		}

		$copyableTexts = $this->generateCobyableTexts($coordinates, $settings);
		$result .= ' ' . implode(' | ', $copyableTexts);

		if ($coordinatesSuffixMessage !== null) {
			$result .= ' ' . $coordinatesSuffixMessage;
		}
		$result .= static::NEWLINE;

		// Generate share links
		$textLinks = $this->generateLinks($coordinates, $settings, $pregeneratedLinks);
		$result .= join(' | ', $textLinks) . static::NEWLINE;

		if ($settings->showAddress() && $address !== null) {
			$result .= $address->getAddress()->toString(true) . static::NEWLINE;
		}

		foreach ($descriptions as $description) {
			$result .= $description . static::NEWLINE;
		}

		return $result . static::NEWLINE;
	}

	private function generateScreenshotLink(CoordinatesInterface $coordinates, BetterLocationMessageSettings $settings): ?string
	{
		$screenshotServiceReference = $settings->getScreenshotLinkService();
		$screenshotService = $this->servicesManager->getServiceInstance($screenshotServiceReference);
		try { // Catch exceptions to prevent one faulty service to block other potentially good services
			return $screenshotService->getScreenshotLink($coordinates);
		} catch (\Exception $exception) {
			Debugger::log($exception, Debugger::EXCEPTION);
			return null;
		}
	}

	/**
	 * Generate copyable text representing location
	 *
	 * @return list<string>
	 */
	private function generateCobyableTexts(CoordinatesInterface $coordinates, BetterLocationMessageSettings $settings): array
	{
		$texts = [];
		foreach ($settings->getTextServices() as $service) {
			try { // Catch exceptions to prevent one faulty service to block other potentially good services
				$copyableText = sprintf('<code>%s</code>', $service::getShareText($coordinates->getLat(), $coordinates->getLon()));
				$texts[] = $copyableText;
			} catch (\Exception $exception) {
				Debugger::log($exception, Debugger::EXCEPTION);
			}
		}
		return $texts;
	}

	/**
	 * @param array<class-string<AbstractService>,string> $pregeneratedLinks
	 * @return list<string>
	 */
	private function generateLinks(CoordinatesInterface $coordinates, BetterLocationMessageSettings $settings, array $pregeneratedLinks): array
	{
		$textLinks = [];
		foreach ($settings->getLinkServices() as $service) {
			try { // Catch exceptions to prevent one faulty service to block other potentially good services
				$link = $pregeneratedLinks[$service] ?? $service::getShareLink($coordinates->getLat(), $coordinates->getLon());
				if ($link !== null) {
					$textLinks[] = self::href($link, $service::getName(true));
				}
			} catch (\Exception $exception) {
				Debugger::log($exception, Debugger::EXCEPTION);
			}
		}
		return $textLinks;
	}

	private static function href(string $link, string $text): string
	{
		return sprintf('<a href="%s" target="_blank">%s</a>', $link, $text);
	}
}
