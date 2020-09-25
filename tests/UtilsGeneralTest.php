<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../src/bootstrap.php';


final class UtilsGeneralTest extends TestCase
{
	public function testCheckIfValueInHeaderMatchArray(): void {
		$this->assertTrue(\Utils\General::checkIfValueInHeaderMatchArray('image/webp;charset=utf-8', ['image/jpeg', 'image/webp']));
		$this->assertTrue(\Utils\General::checkIfValueInHeaderMatchArray('ImaGE/JpEg; CHarsEt=utF-8', ['image/jpeg', 'image/webp']));
	}
}
