<?php declare(strict_types=1);

namespace App\DiscordCustomWrapper;

use App\Address\AddressInterface;
use App\BetterLocation\Description;
use App\BetterLocation\MessageGeneratorInterface;
use App\BetterLocation\Service\AbstractService;
use App\Icons;
use App\TelegramCustomWrapper\BetterLocationMessageSettings;
use App\TelegramCustomWrapper\TelegramHelper as TG;
use App\Utils\Utils;
use DJTommek\Coordinates\CoordinatesInterface;
use Tracy\Debugger;

readonly class DiscordMessageGenerator implements MessageGeneratorInterface
{
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
		// @TODO 2025-07-05 Hack to convert HTML-links into markdown, until all services will start returning some better
		//       objects, that can be formatted into both HTML or Markdown
		$result = Utils::htmlToMarkdown(
			html: $prefixMessage,
			emojiReplacement: [self::class, 'emojiReplacement'],
			allowLinkPreview: false,
		);

		// @TODO 2025-07-05 Screenshotter is not formatting correctly on Discord, so it is disabled for now
		//		$screenshotLink = $this->generateScreenshotLink($coordinates, $settings);
		//		if ($screenshotLink) {
		//			$result .= ' ' . self::href($screenshotLink, 'Map');
		//		}

		$copyableTexts = $this->generateCobyableTexts($coordinates, $settings);
		$result .= ' ' . implode(' | ', $copyableTexts);

		if ($coordinatesSuffixMessage !== null) {
			$result .= ' ' . $coordinatesSuffixMessage;
		}
		$result .= TG::NL;

		// Generate share links
		$textLinks = $this->generateLinks($coordinates, $settings, $pregeneratedLinks);
		$result .= join(' | ', $textLinks) . TG::NL;

		if ($settings->showAddress() && $address !== null) {
			$result .= $address->getAddress()->toString(true) . TG::NL;
		}

		foreach ($descriptions as $description) {
			$result .= $description . TG::NL;
		}

		return $result . TG::NL;
	}

	/**
	 * Emojis cannot be used inside of links on Discord so replace them with text in brackets instead
	 *
	 * @param array{string} $matches
	 */
	public static function emojiReplacement(array $matches): string
	{
		return match ($matches[0]) {
			Icons::INGRESS_INTEL => '[Intel]',
			Icons::INGRESS_PORTAL_IMAGE => '[Image]',
			Icons::INGRESS_PRIME => '[Prime]',
			default => '[E]',
		};
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
				$copyableText = sprintf('`%s`', $service::getShareText($coordinates->getLat(), $coordinates->getLon()));
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
					$textLinks[] = self::href($link, $service::getName(true),  allowPreview: false);
				}
			} catch (\Exception $exception) {
				Debugger::log($exception, Debugger::EXCEPTION);
			}
		}
		return $textLinks;
	}

	/**
	 * @param bool $allowPreview Set true to let Discord generate preview of the link under the message.
	 */
	private static function href(string $link, string $text, bool $allowPreview): string
	{
		return sprintf(
			'[%s](%s%s%s)',
			$text,
			$allowPreview ? '' : '<',
			$link,
			$allowPreview ? '' : '>',
		);
	}
}
