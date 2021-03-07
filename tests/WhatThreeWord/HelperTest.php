<?php declare(strict_types=1);

use App\WhatThreeWord\Helper;
use PHPUnit\Framework\TestCase;

final class HelperTest extends TestCase
{
	public function testValidateWords(): void
	{
		$this->assertSame('chladná.naopak.vložit', Helper::validateWords('///chladná.naopak.vložit'));
		$this->assertSame('perkily.salon.receive', Helper::validateWords('///perkily.salon.receive'));
		$this->assertSame('stampedes.foresees.prow', Helper::validateWords('stampedes.foresees.prow'));
		$this->assertSame('a.b.c', Helper::validateWords('a.b.c'));
		$this->assertSame('шейна.читалня.мишле', Helper::validateWords('шейна.читалня.мишле')); // Bulgaria
		$this->assertSame('шейна.читалня.мишле', Helper::validateWords('///шейна.читалня.мишле')); // Bulgaria
		$this->assertSame('井水.组装.湖泊', Helper::validateWords('///井水.组装.湖泊')); // Chinese
		$this->assertSame('井水.组装.湖泊', Helper::validateWords('井水.组装.湖泊')); // Chinese
		$this->assertSame('kobry.sedátko.vývozy', Helper::validateWords('///kobry.sedátko.vývozy'));
		$this->assertSame('kobry.sedátko.vývozy', Helper::validateWords('kobry.sedátko.vývozy'));
		$this->assertSame('útlum.hravost.rohlíky', Helper::validateWords('///útlum.hravost.rohlíky'));
		$this->assertSame('útlum.hravost.rohlíky', Helper::validateWords('útlum.hravost.rohlíky'));
		$this->assertSame('매출.수행.칼국수', Helper::validateWords('///매출.수행.칼국수'));
		$this->assertSame('매출.수행.칼국수', Helper::validateWords('매출.수행.칼국수'));
		// Lowercase
		$this->assertSame('fun.with.code', Helper::validateWords('fun.WITH.code'));
		// Remove space
		$this->assertSame('fun.with.code', Helper::validateWords(" fun.with.code  "));
		$this->assertSame('fun.with.code', Helper::validateWords("\n fun.with.code  "));
		$this->assertSame('fun.with.code', Helper::validateWords("fun.with.code\t"));

		$this->assertNull(Helper::validateWords('/a.b.c'));
		$this->assertNull(Helper::validateWords('//a.b.c'));
		$this->assertNull(Helper::validateWords('a.b.c.d'));
		$this->assertNull(Helper::validateWords('///a.b.c.d'));
		$this->assertNull(Helper::validateWords('//шейна.читалня.мишле')); // Bulgaria
	}

	public function testFindInText(): void
	{
		$words = Helper::findInText('Hello ///smaller.biggest.money there! Random URL https://tomas.palider.cz/ there...');
		$this->assertCount(1, $words);
		$this->assertSame($words[0], 'smaller.biggest.money');

		$words = Helper::findInText('///chladná.naopak.vložit valid words as first occurence in string');
		$this->assertCount(1, $words);
		$this->assertSame($words[0], 'chladná.naopak.vložit');

		$words = Helper::findInText('///井水.组装.湖泊 ///stampedes.foresees.prow and almost valid word which has extra space ///aaa.bbb. ccc ');
		$this->assertCount(2, $words);
		$this->assertSame($words[0], '井水.组装.湖泊');
		$this->assertSame($words[1], 'stampedes.foresees.prow');

		$words = Helper::findInText('///fun.WITH.code///fun.WITH.code valid words as first occurence in string');
		$this->assertCount(2, $words);
		$this->assertSame($words[0], 'fun.with.code');
		$this->assertSame($words[1], 'fun.with.code');

		$words = Helper::findInText('"///fun.WITH.code" \'///fun.WITH.code\' valid words address with special characters at the start and end of them');
		$this->assertCount(2, $words);
		$this->assertSame($words[0], 'fun.with.code');
		$this->assertSame($words[1], 'fun.with.code');

		// No valid words
		$words = Helper::findInText('Some random text without anything special');
		$this->assertCount(0, $words);

		$words = Helper::findInText('Some random text without /// prefix шейна.читалня.мишле');
		$this->assertCount(0, $words);

		$words = Helper::findInText('Valid word but again without chladná.naopak.vložit without prefix');
		$this->assertCount(0, $words);
	}

	public function testCoordsToWords(): void
	{
		$data = Helper::coordsToWords(51.521251, -0.203586);
		$this->assertSame(51.521251, $data->coordinates->lat);
		$this->assertSame(-0.203586, $data->coordinates->lng);
		$this->assertSame('index.home.raft', $data->words);
		$this->assertSame('https://w3w.co/index.home.raft', $data->map);

		$data = Helper::coordsToWords(50.087451, 14.420671);
		$this->assertSame(50.087443, $data->coordinates->lat);
		$this->assertSame(14.420682, $data->coordinates->lng);
		$this->assertSame('paves.fans.piston', $data->words);
		$this->assertSame('https://w3w.co/paves.fans.piston', $data->map);
	}

	public function testWordsToCoords(): void
	{
		$data = Helper::wordsToCoords('index.home.raft');
		$this->assertSame(51.521251, $data->coordinates->lat);
		$this->assertSame(-0.203586, $data->coordinates->lng);
		$this->assertSame('index.home.raft', $data->words);
		$this->assertSame('https://w3w.co/index.home.raft', $data->map);

		$data = Helper::wordsToCoords('paves.fans.piston');
		$this->assertSame(50.087443, $data->coordinates->lat);
		$this->assertSame(14.420682, $data->coordinates->lng);
		$this->assertSame('paves.fans.piston', $data->words);
		$this->assertSame('https://w3w.co/paves.fans.piston', $data->map);

		$data = Helper::wordsToCoords('井水.组装.湖泊');
		$this->assertSame(39.916788, $data->coordinates->lat);
		$this->assertSame(116.397099, $data->coordinates->lng);
		$this->assertSame('井水.组装.湖泊', $data->words);
		$this->assertSame('https://w3w.co/%E4%BA%95%E6%B0%B4.%E7%BB%84%E8%A3%85.%E6%B9%96%E6%B3%8A', $data->map);
	}
}
