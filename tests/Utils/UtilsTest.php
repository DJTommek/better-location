<?php declare(strict_types=1);

namespace Tests\Utils;

use App\BetterLocation\Service\Exceptions\InvalidLocationException;
use App\Utils\Utils;
use PHPUnit\Framework\TestCase;

final class UtilsTest extends TestCase
{
	public static function htmlToMarkdownProvider(): array
	{
		return [
			['', ''],
			// Simple tags
			['some plaintext', 'some plaintext'],
			['příliš žluťoučký kůň', 'příliš žluťoučký kůň'],
			['příliš **žluťoučký** kůň', 'příliš <b>žluťoučký</b> kůň'],
			['aaa **bbb** ccc', 'aaa <b>bbb</b> ccc'],
			['aaa *bbb* ccc', 'aaa <i>bbb</i> ccc'],
			['aaa [bbb](https://tomas.palider.cz) ccc', 'aaa <a href="https://tomas.palider.cz">bbb</a> ccc'],
			// Combined tags
			['aaa ***bbb*** ccc', 'aaa <b><i>bbb</i></b> ccc'],
			['aaa **[bbb](https://tomas.palider.cz)** ccc', 'aaa <b><a href="https://tomas.palider.cz">bbb</a></a></b> ccc'],
			['aaa **[bbb](<https://tomas.palider.cz>)** ccc', 'aaa <b><a href="https://tomas.palider.cz">bbb</a></a></b> ccc', '', false],
			['aaa [**bbb**](https://tomas.palider.cz) ccc', 'aaa <a href="https://tomas.palider.cz"><b>bbb</b></a> ccc'],
			[
				'aaa [plain link, *italic link*, **bold link**, ***italic and bold link***](https://tomas.palider.cz) ccc',
				'aaa <a href="https://tomas.palider.cz">plain link, <i>italic link</i>, <b>bold link</b>, <b><i>italic and bold link</i></b></a> ccc',
			],
			// Remove emojis from link text
			['aaa [someemoji](https://tomas.palider.cz) bbb', 'aaa <a href="https://tomas.palider.cz">some😈emoji</a> bbb'],
			['aaa [some!!!emoji](https://tomas.palider.cz) bbb', 'aaa <a href="https://tomas.palider.cz">some😈emoji</a> bbb', '!!!'],
			[
				'aaa [emoji1 red heart and emoji2 green heart](https://dita.paliderova.cz/) bbb',
				'aaa <a href="https://dita.paliderova.cz/">emoji1 🏰 and emoji2 💚</a> bbb',
				fn($matches) => match ($matches[0]) {
					"\u{1F3F0}" => 'red heart',
					'💚' => 'green heart',
					default => 'EEE'
				},
				//				[self::class, 'emojiReplacement']
			],
			//			[
			//				'[Il Campanone📱](https://link.ingress.com/?link=https%3A%2F%2Fintel.ingress.com%2Fportal%2Fadafff0f75f24144905ecfec3c662d42.16&apn=com.nianticproject.ingress&isi=576505181&ibi=com.google.ingress&ifl=https%3A%2F%2Fapps.apple.com%2Fapp%2Fingress%2Fid576505181&ofl=https%3A%2F%2Fintel.ingress.com%2Fintel%3Fpll%3D45.703997%2C9.662381) [🖥](https://intel.ingress.com/intel?pll=45.703997,9.662381) [🖼](https://lh3.googleusercontent.com/IG9TGatrqDFj6WE7KDFNdmhbUcyXgH9jH5jUDeT01NkQ2MoNvMB9M395GjbwAdfK4zj0h0ouSdFWxTxWcRWU8-44tVw9=s10000) [🗺](https://en.mapy.cz/screenshoter?url=https%3A%2F%2Fmapy.cz%2Fzakladni%3Fy%3D45.703997%26x%3D9.662381%26source%3Dcoor%26id%3D9.662381%252C45.703997%26p%3D3%26l%3D0) `45.703997,9.662381`',
			//				'<a href="https://link.ingress.com/?link=https%3A%2F%2Fintel.ingress.com%2Fportal%2Fadafff0f75f24144905ecfec3c662d42.16&apn=com.nianticproject.ingress&isi=576505181&ibi=com.google.ingress&ifl=https%3A%2F%2Fapps.apple.com%2Fapp%2Fingress%2Fid576505181&ofl=https%3A%2F%2Fintel.ingress.com%2Fintel%3Fpll%3D45.703997%2C9.662381">Il Campanone📱</a> <a href="https://intel.ingress.com/intel?pll=45.703997,9.662381">🖥</a> <a href="https://lh3.googleusercontent.com/IG9TGatrqDFj6WE7KDFNdmhbUcyXgH9jH5jUDeT01NkQ2MoNvMB9M395GjbwAdfK4zj0h0ouSdFWxTxWcRWU8-44tVw9=s10000">🖼</a> <a href="https://en.mapy.cz/screenshoter?url=https%3A%2F%2Fmapy.cz%2Fzakladni%3Fy%3D45.703997%26x%3D9.662381%26source%3Dcoor%26id%3D9.662381%252C45.703997%26p%3D3%26l%3D0">🗺</a> <code>45.703997,9.662381</code>',
			//				fn($a) => match($a[0]) { '📱' => '', '💚' => 'green heart', default=> 'EEE'},
			//			]
			[
				'[Il Campanone ](https://link.ingress.com/?link=https%3A%2F%2Fintel.ingress.com%2Fportal%2Fadafff0f75f24144905ecfec3c662d42.16&apn=com.nianticproject.ingress&isi=576505181&ibi=com.google.ingress&ifl=https%3A%2F%2Fapps.apple.com%2Fapp%2Fingress%2Fid576505181&ofl=https%3A%2F%2Fintel.ingress.com%2Fintel%3Fpll%3D45.703997%2C9.662381) [Intel](https://intel.ingress.com/intel?pll=45.703997,9.662381) [Image](https://lh3.googleusercontent.com/IG9TGatrqDFj6WE7KDFNdmhbUcyXgH9jH5jUDeT01NkQ2MoNvMB9M395GjbwAdfK4zj0h0ouSdFWxTxWcRWU8-44tVw9=s10000) [](https://en.mapy.cz/screenshoter?url=https%3A%2F%2Fmapy.cz%2Fzakladni%3Fy%3D45.703997%26x%3D9.662381%26source%3Dcoor%26id%3D9.662381%252C45.703997%26p%3D3%26l%3D0) `45.703997,9.662381`',
				'<a href="https://link.ingress.com/?link=https%3A%2F%2Fintel.ingress.com%2Fportal%2Fadafff0f75f24144905ecfec3c662d42.16&apn=com.nianticproject.ingress&isi=576505181&ibi=com.google.ingress&ifl=https%3A%2F%2Fapps.apple.com%2Fapp%2Fingress%2Fid576505181&ofl=https%3A%2F%2Fintel.ingress.com%2Fintel%3Fpll%3D45.703997%2C9.662381">Il Campanone 📱</a> <a href="https://intel.ingress.com/intel?pll=45.703997,9.662381">🖥</a> <a href="https://lh3.googleusercontent.com/IG9TGatrqDFj6WE7KDFNdmhbUcyXgH9jH5jUDeT01NkQ2MoNvMB9M395GjbwAdfK4zj0h0ouSdFWxTxWcRWU8-44tVw9=s10000">🖼</a> <a href="https://en.mapy.cz/screenshoter?url=https%3A%2F%2Fmapy.cz%2Fzakladni%3Fy%3D45.703997%26x%3D9.662381%26source%3Dcoor%26id%3D9.662381%252C45.703997%26p%3D3%26l%3D0">🗺</a> <code>45.703997,9.662381</code>',
				[self::class, 'emojiReplacement']
			],
		];
	}

	/**
	 * @param list{string} $matches
	 * @return string
	 */
	public static function emojiReplacement(array $matches): string
	{
		return match ($matches[0]) {
			'📱', '🗺' => '',
			'🖼' => 'Image',
			'🖥' => 'Intel',
			default => 'EEE'
		};
	}

	/**
	 * @dataProvider htmlToMarkdownProvider
	 */
	public function testHtmlToMarkdown(
		string $expected,
		string $html,
		string|callable $emojiReplacement = '',
		bool $allowLinkPreview = true
	): void
	{
		$result = Utils::htmlToMarkdown($html, $emojiReplacement, $allowLinkPreview);
		$this->assertSame($expected, $result);
	}

	public function testCheckIfValueInHeaderMatchArray(): void
	{
		$this->assertTrue(Utils::checkIfValueInHeaderMatchArray('image/webp;charset=utf-8', ['image/jpeg', 'image/webp']));
		$this->assertTrue(Utils::checkIfValueInHeaderMatchArray('ImaGE/JpEg; CHarsEt=utF-8', ['image/jpeg', 'image/webp']));
	}

	public function testGetUrls(): void
	{
		$this->assertSame(Utils::getUrls('No link in this message...'), []);

		$this->assertSame(Utils::getUrls('https://tomas.palider.cz'), ['https://tomas.palider.cz']);
		$this->assertSame(Utils::getUrls('https://tomas.palider.cz/'), ['https://tomas.palider.cz/']);
		$this->assertSame(Utils::getUrls('bla https://tomas.palider.cz/ https://ladislav.palider.cz/'), ['https://tomas.palider.cz/', 'https://ladislav.palider.cz/']);
		$this->assertSame(Utils::getUrls('https://tomas.palider.cz/, blabla https://ladislav.palider.cz/'), ['https://tomas.palider.cz/', 'https://ladislav.palider.cz/']);
		$this->assertSame(Utils::getUrls('Hi there!https://tomas.palider.cz, http://ladislav.palider.cz/ haha'), ['https://tomas.palider.cz', 'http://ladislav.palider.cz/']);
		$this->assertSame(Utils::getUrls('Some link https://tomas.palider.cz this is real end.'), ['https://tomas.palider.cz']);
		$this->assertSame(Utils::getUrls('Some link https://tomas.palider.cz/ this is real end.'), ['https://tomas.palider.cz/']);
		$this->assertSame(Utils::getUrls('Some link from wikipedia https://cs.wikipedia.org/wiki/Piastovsk%C3%A1_v%C4%9B%C5%BE_(T%C4%9B%C5%A1%C3%ADn) this is real end.'), ['https://cs.wikipedia.org/wiki/Piastovsk%C3%A1_v%C4%9B%C5%BE_(T%C4%9B%C5%A1%C3%ADn)']);
		$this->assertSame(Utils::getUrls('Some link from wikipedia https://cs.wikipedia.org/wiki/Piastovská_věž_(Těšín) this is real end.'), ['https://cs.wikipedia.org/wiki/Piastovská_věž_(Těšín)']);
	}

	public final function testFindMapyCzApiCoords(): void
	{
		$this->assertSame('48.890900,13.485400', Utils::findMapyCzApiCoords('var center = SMap.Coords.fromWGS84(13.4854,48.8909);')->__toString());
		$this->assertSame('-48.890900,-13.485400', Utils::findMapyCzApiCoords('var center = SMap.Coords.fromWGS84(-13.4854,-48.8909);')->__toString());
		$this->assertSame('48.890900,13.485400', Utils::findMapyCzApiCoords('some text blabla SMap.Coords.fromWGS84(13.4854, 48.8909) some more text')->__toString());
		$this->assertSame('48.890900,13.485400', Utils::findMapyCzApiCoords('some text blabla SMap.Coords.fromWGS84(   13.4854,    48.8909  )')->__toString());
		$this->assertSame('48.890900,13.485400',
			Utils::findMapyCzApiCoords('some text blabla SMap.Coords.fromWGS84(13.4854, 
48.8909) some text')->__toString());
		$this->assertSame('48.890900,13.485400',
			Utils::findMapyCzApiCoords('some text blabla SMap.Coords.fromWGS84(
		13.4854, 
  48.8909
) some text')->__toString());

		$this->assertNull(Utils::findMapyCzApiCoords('some random text'));
		$this->assertNull(Utils::findMapyCzApiCoords('var center = SMap.      Coords.fromWGS84(13.4854,48.8909);'));
	}

	public final function testFindMapyCzApiCoordsInvalid(): void
	{
		$this->expectException(InvalidLocationException::class);
		$this->expectExceptionMessage('Latitude coordinate must be numeric between or equal from -90 to 90 degrees.');
		Utils::findMapyCzApiCoords('some text blabla SMap.Coords.fromWGS84(13.4854, 98.8909)');
	}

	public final function testRecalculateRangeOne(): void
	{
		$this->assertSame(50.0, Utils::recalculateRangeOne(500, 0, 1000));
		$this->assertSame(100.0, Utils::recalculateRangeOne(1000, 0, 1000));
		$this->assertSame(25.0, Utils::recalculateRangeOne(25, 0, 100));
		$this->assertSame(1.0, Utils::recalculateRangeOne(25, 0, 100, 0, 4));
		$this->assertSame(74.09731113956467, Utils::recalculateRangeOne(123_456, 64, 200_000, 0, 120));
		$this->assertSame(5.0, Utils::recalculateRangeOne(5, 5, 5, 0, 10));
	}

	public final function testRecalculateRange(): void
	{
		$this->assertSame([0.0, 50.0, 100.0], Utils::recalculateRange([0, 50, 100]));
		$this->assertSame([0.0, 50.0, 100.0], Utils::recalculateRange([0, 500, 1000]));
		$this->assertSame([50.0, 100.0, 0.0], Utils::recalculateRange([500, 1000, 0]));
		$this->assertSame([0.0, 2.0, 4.0], Utils::recalculateRange([0, 50, 100], 0, 4));
		$this->assertSame([120.03841229193341, 74.09731113956467, 0.03841229193341869], Utils::recalculateRange([200_000, 123_456, 64], 0, 120));
		$this->assertSame([50.0], Utils::recalculateRange([50]));
		$this->assertSame([50.0, 50.0,], Utils::recalculateRange([50, 50]));
	}

	/**
	 * @return array<array{string, string}>
	 */
	public function flagEmojiProvider(): array
	{
		return [
			['🇨🇿', 'CZ'],
			['🇨🇿', 'cz'],
			['🇨🇿', 'cZ'],
			['🇳🇱', 'nl'],
			['🇺🇸', 'Us'],
			['🇨🇭', 'CH'],
		];
	}

	/**
	 * @return array<array{mixed}>
	 */
	public function flagEmojiInvalidProvider(): array
	{
		return [
			['czcz'],
			['Czechia'],
			[''],
			['c'],
			['čr'],
			['ČR'],
			[' cz '],
			['1'],
			['23'],
		];
	}

	/**
	 * @dataProvider flagEmojiProvider
	 */
	public final function testFlagEmojiFromCountryCode(string $expectedEmoji, string $countryCode): void
	{
		$flag = Utils::flagEmojiFromCountryCode($countryCode);
		$this->assertSame($expectedEmoji, $flag);
	}

	/**
	 * @dataProvider flagEmojiInvalidProvider
	 */
	public final function testFlagEmojiFromCountryCodeInvalid(string $input): void
	{
		$this->expectException(\InvalidArgumentException::class);
		Utils::flagEmojiFromCountryCode($input);
	}
}
