<?php

namespace DataValues\Time\Parsers\Tests;

use ValueParsers\Test\StringValueParserTest;

/**
 * @covers DataValues\Time\Parsers\EraParser
 *
 * @group DataValue
 * @group DataValueExtensions
 *
 * @licence GNU GPL v2+
 * @author Adam Shorland
 */
class EraParserTest extends StringValueParserTest {

	/**
	 * @return string
	 */
	protected function getParserClass() {
		return 'DataValues\Time\Parsers\EraParser';
	}

	/**
	 * @return bool
	 */
	protected function requireDataValue() {
		return false;
	}

	public function validInputProvider() {
		return array(
			array( '+100', array( '+', '100' ) ),
			array( '-100', array( '-', '100' ) ),
			array( '   -100', array( '-', '100' ) ),
			array( '100BC', array( '-', '100' ) ),
			array( '100 BC', array( '-', '100' ) ),
			array( '100 BCE', array( '-', '100' ) ),
			array( '100 AD', array( '+', '100' ) ),
			array( '100 A. D.', array( '+', '100' ) ),
			array( '   100   B.   C.   ', array( '-', '100' ) ),
			array( '   100   Common   Era   ', array( '+', '100' ) ),
			array( '100 CE', array( '+', '100' ) ),
			array( '100CE', array( '+', '100' ) ),
			array( '+100', array( '+', '100' ) ),
			array( '100 Common Era', array( '+', '100' ) ),
			array( '100Common Era', array( '+', '100' ) ),
			array( '100 Before Common Era', array( '-', '100' ) ),
			array( '1 July 2013 Before Common Era', array( '-', '1 July 2013' ) ),
			array( 'June 2013 Before Common Era', array( '-', 'June 2013' ) ),
			array( '10-10-10 Before Common Era', array( '-', '10-10-10' ) ),
			array( 'FooBefore Common Era', array( '-', 'Foo' ) ),
			array( 'Foo Before Common Era', array( '-', 'Foo' ) ),
		);
	}

	public function invalidInputProvider() {
		return array(
			array( '-100BC' ),
			array( '-100AD' ),
			array( '-100CE' ),
			array( '+100BC' ),
			array( '+100AD' ),
			array( '+100CE' ),
			array( '+100 Before Common Era' ),
			array( '+100 Common Era' ),
		);
	}

}