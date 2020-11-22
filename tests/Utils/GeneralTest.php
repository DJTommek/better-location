<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class GeneralTest extends TestCase
{
	public function testCheckIfValueInHeaderMatchArray(): void
	{
		$this->assertTrue(\App\Utils\General::checkIfValueInHeaderMatchArray('image/webp;charset=utf-8', ['image/jpeg', 'image/webp']));
		$this->assertTrue(\App\Utils\General::checkIfValueInHeaderMatchArray('ImaGE/JpEg; CHarsEt=utF-8', ['image/jpeg', 'image/webp']));
	}
}
