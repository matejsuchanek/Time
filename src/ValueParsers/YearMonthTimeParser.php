<?php

namespace ValueParsers;

use DataValues\TimeValue;

/**
 * A parser that accepts various date formats with month precision. Prefers month/year order when
 * both numbers are valid months, e.g. "12/10" is December 2010. Should be called before
 * YearTimeParser when you want to accept both formats, because strings like "1 999" may either
 * represent a month and a year or a year with digit grouping.
 *
 * @since 0.8.4
 *
 * @license GPL-2.0+
 * @author Addshore
 * @author Thiemo Mättig
 *
 * @todo match BCE dates in here
 */
class YearMonthTimeParser extends StringValueParser {

	const FORMAT_NAME = 'year-month';

	/**
	 * @var int[] Array mapping localized month names to month numbers (1 to 12).
	 */
	private $monthNumbers;

	/**
	 * @var ValueParser
	 */
	private $isoTimestampParser;

	/**
	 * @see StringValueParser::__construct
	 *
	 * @param MonthNameProvider $monthNameProvider
	 * @param ParserOptions|null $options
	 */
	public function __construct(
		MonthNameProvider $monthNameProvider,
		ParserOptions $options = null
	) {
		parent::__construct( $options );

		$languageCode = $this->getOption( ValueParser::OPT_LANG );
		$this->monthNumbers = $monthNameProvider->getMonthNumbers( $languageCode );
		$this->isoTimestampParser = new IsoTimestampParser( null, $this->options );
	}

	/**
	 * @see StringValueParser::stringParse
	 *
	 * @param string $value
	 *
	 * @throws ParseException
	 * @return TimeValue
	 */
	protected function stringParse( $value ) {
		//Matches Year and month separated by a separator, \p{L} matches letters outside the ASCII range
		if ( !preg_match( '/^([\d\p{L}]+)\s*[\/\-\s.,]\s*([\d\p{L}]+)$/', trim( $value ), $matches ) ) {
			throw new ParseException( 'Failed to parse year and month', $value, self::FORMAT_NAME );
		}
		list( , $a, $b ) = $matches;

		$aIsInt = preg_match( '/^\d+$/', $a );
		$bIsInt = preg_match( '/^\d+$/', $b );

		if ( $aIsInt && $bIsInt ) {
			$parsed = $this->parseYearMonthTwoInts( $a, $b );
			if ( $parsed ) {
				return $parsed;
			}
		}

		if ( $aIsInt || $bIsInt ) {
			if ( $aIsInt ) {
				$year = $a;
				$month = trim( $b );
			} else {
				$year = $b;
				$month = trim( $a );
			}

			$parsed = $this->parseYearMonth( $year, $month );
			if ( $parsed ) {
				return $parsed;
			}
		}

		throw new ParseException( 'Failed to parse year and month', $value, self::FORMAT_NAME );
	}

	/**
	 * If we have 2 integers parse the date assuming that the larger is the year
	 * unless the smaller is not a 'legal' month value
	 *
	 * @param string|int $a
	 * @param string|int $b
	 *
	 * @return TimeValue|bool
	 */
	private function parseYearMonthTwoInts( $a, $b ) {
		if ( !preg_match( '/^\d+$/', $a ) || !preg_match( '/^\d+$/', $b ) ) {
			return false;
		}

		if ( !$this->canBeMonth( $a ) && $this->canBeMonth( $b ) ) {
			return $this->getTimeFromYearMonth( $a, $b );
		} elseif ( $this->canBeMonth( $a ) ) {
			return $this->getTimeFromYearMonth( $b, $a );
		}

		return false;
	}

	/**
	 * If we have 1 int and 1 string then try to parse the int as the year and month as the string
	 * Check for both the full name and abbreviations
	 *
	 * @param string|int $year
	 * @param string $month
	 *
	 * @return TimeValue|bool
	 */
	private function parseYearMonth( $year, $month ) {
		foreach ( $this->monthNumbers as $monthName => $i ) {
			if ( strcasecmp( $monthName, $month ) === 0 ) {
				return $this->getTimeFromYearMonth( $year, $i );
			}
		}

		return false;
	}

	/**
	 * @param string $year
	 * @param string $month
	 *
	 * @return TimeValue
	 */
	private function getTimeFromYearMonth( $year, $month ) {
		return $this->isoTimestampParser->parse( sprintf( '+%d-%02d-00T00:00:00Z', $year, $month ) );
	}

	/**
	 * @param string|int $value
	 *
	 * @return bool can the given value be a month?
	 */
	private function canBeMonth( $value ) {
		return $value >= 0 && $value <= 12;
	}

}
