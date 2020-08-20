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
 * @author Thiemo Kreuz
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
	 * @var EraParser
	 */
	private $eraParser;

	/**
	 * @see StringValueParser::__construct
	 *
	 * @param MonthNameProvider $monthNameProvider
	 * @param ParserOptions|null $options
	 * @param EraParser|null $eraParser
	 */
	public function __construct(
		MonthNameProvider $monthNameProvider,
		ParserOptions $options = null,
		EraParser $eraParser = null
	) {
		parent::__construct( $options );

		$languageCode = $this->getOption( ValueParser::OPT_LANG );
		$this->monthNumbers = $monthNameProvider->getMonthNumbers( $languageCode );
		$this->isoTimestampParser = new IsoTimestampParser( null, $this->options );
		$this->eraParser = $eraParser ?: new EraParser();
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
		$trimmedValue = trim( $value );
		switch ( $trimmedValue[0] ) {
			case '+':
			case '-':
				// don't let EraParser strip it, we will handle it ourselves
				$newValue = $trimmedValue;
				$eraWasSpecified = false;
				$sign = '';
				break;
			default:
				list( $sign, $newValue ) = $this->eraParser->parse( $trimmedValue );
				if ( $newValue !== $trimmedValue ) {
					$eraWasSpecified = true;
				} else {
					$eraWasSpecified = false;
					$sign = '';
				}
				break;
		}

		// Matches year and month separated by a separator.
		// \p{L} matches letters outside the ASCII range.
		$regex = '/^(-?[\d\p{L}]+)\s*?[\/\-\s.,]\s*(-?[\d\p{L}]+)$/u';
		if ( !preg_match( $regex, $newValue, $matches ) ) {
			throw new ParseException( 'Failed to parse year and month', $value, self::FORMAT_NAME );
		}
		list( , $a, $b ) = $matches;

		// if era was specified, fail on a minus sign
		$intRegex = $eraWasSpecified ? '/^\d+$/' : '/^-?\d+$/';
		$aIsInt = preg_match( $intRegex, $a );
		$bIsInt = preg_match( $intRegex, $b );

		if ( $aIsInt && $bIsInt ) {
			if ( $this->canBeMonth( $a ) ) {
				return $this->getTimeFromYearMonth( $sign . $b, $a );
			} elseif ( $this->canBeMonth( $b ) ) {
				return $this->getTimeFromYearMonth( $sign . $a, $b );
			}
		} elseif ( $aIsInt ) {
			$month = $this->parseMonth( $b );

			if ( $month ) {
				return $this->getTimeFromYearMonth( $sign . $a, $month );
			}
		} elseif ( $bIsInt ) {
			$month = $this->parseMonth( $a );

			if ( $month ) {
				return $this->getTimeFromYearMonth( $sign . $b, $month );
			}
		}

		throw new ParseException( 'Failed to parse year and month', $value, self::FORMAT_NAME );
	}

	/**
	 * @param string $month
	 *
	 * @return int|null
	 */
	private function parseMonth( $month ) {
		foreach ( $this->monthNumbers as $monthName => $i ) {
			if ( strcasecmp( $monthName, $month ) === 0 ) {
				return $i;
			}
		}

		return null;
	}

	/**
	 * @param string $year
	 * @param string $month as a canonical month number
	 *
	 * @return TimeValue
	 */
	private function getTimeFromYearMonth( $year, $month ) {
		if ( $year[0] !== '-' && $year[0] !== '+' ) {
			$year = '+' . $year;
		}

		return $this->isoTimestampParser->parse( sprintf( '%s-%02s-00T00:00:00Z', $year, $month ) );
	}

	/**
	 * @param string $value
	 *
	 * @return bool can the given value be a month?
	 */
	private function canBeMonth( $value ) {
		return $value >= 0 && $value <= 12;
	}

}
