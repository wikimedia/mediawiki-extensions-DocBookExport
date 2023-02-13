<?php
use MediaWiki\MediaWikiServices;

class DocBookExport {

	/**
	 * @param Parser &$parser
	 * @return true
	 */
	public static function onParserSetup( Parser &$parser ) {
		$parser->setHook( 'docbook', 'DocBookExport::parseDocBookSyntaxTagExtension' );
		$parser->setHook( 'footnote', 'DocBookExport::parseFootNoteSyntaxTagExtension' );
		$parser->setFunctionHook( 'docbook', 'DocBookExport::parseDocBookSyntaxParserFunction' );
		$parser->setFunctionHook( 'docbook_index', 'DocBookExport::parseDoocBookIndexParserExtension' );
		return true;
	}

	/**
	 * @param Parser $parser
	 * @return string
	 */
	public static function parseDoocBookIndexParserExtension( Parser $parser ) {
		$options = self::extractOptions( array_slice( func_get_args(), 1 ) );
		$group_by = $options['grouping'];
		$parserOutput = $parser->getOutput();
		if ( method_exists( $parserOutput, 'setPageProperty' ) ) {
			// MW 1.38
			$parserOutput->setPageProperty( 'docbook_index_group_by', $group_by );
		} else {
			$parserOutput->setProperty( 'docbook_index_group_by', $group_by );
		}
		return '';
	}

	/**
	 * @param Parser &$parser
	 * @return array
	 */
	public static function parseDocBookSyntaxParserFunction( Parser &$parser ) {
		$options = self::extractOptions( array_slice( func_get_args(), 1 ) );
		return [ self::parseDocBookSyntax( $parser, $options ), 'noparse' => true, 'isHTML' => true ];
	}

	/**
	 * @param string $input
	 * @param array $args
	 * @param Parser $parser
	 * @param PPFrame $frame
	 * @return string
	 */
	public static function parseFootNoteSyntaxTagExtension( string $input, array $args, Parser $parser, PPFrame $frame ) {
		if ( empty( $input ) ) {
			return '<a class="footnoteref" id="' . $args['name'] . '" href=""></a>';
		} else {
			return '<a class="footnote" id="' . $args['name'] . '" href="' . $parser->recursiveTagParse( $input, $frame ) . '">' . $parser->recursiveTagParse( $input, $frame ) . '</a>';
		}
	}

	/**
	 * @param string $input
	 * @param array $args
	 * @param Parser $parser
	 * @param PPFrame $frame
	 * @return string
	 */
	public static function parseDocBookSyntaxTagExtension( string $input, array $args, Parser $parser, PPFrame $frame ) {
		$args['page structure'] = $input;
		return self::parseDocBookSyntax( $parser, $args );
	}

	/**
	 * @param Parser &$parser
	 * @param array $options
	 * @return string
	 */
	public static function parseDocBookSyntax( Parser &$parser, array $options ) {
		if ( $parser->getTitle() == null ) {
			return "";
		}

		$serialized = serialize( $options );
		$book_name = str_replace( " ", "_", $options['title'] );
		if ( !empty( $options['volumenum'] ) ) {
			$book_name .= '_' . $options['volumenum'];
		}

		$parserOutput = $parser->getOutput();
		if ( method_exists( $parserOutput, 'setPageProperty' ) ) {
			// MW 1.38
			$parserOutput->setPageProperty( md5( 'docbook_' . $book_name ), $serialized );
		} else {
			$parserOutput->setProperty( md5( 'docbook_' . $book_name ), $serialized );
		}

		$linkRenderer = MediaWikiServices::getInstance()->getLinkRenderer();

		$docbook_link = $linkRenderer->makeKnownLink(
				Title::makeTitle( NS_SPECIAL, 'GetDocbook' ),
				"Get Docbook - " . $book_name,
				[],
				[ 'embed_page' => $parser->getTitle()->getText(), 'bookname' => $book_name ]
			);

		$docbook_preview = '';

		$page_structure = explode( "\n", $options['page structure'] );
		foreach ( $page_structure as $current_line ) {
			$parts = explode( ' ', $current_line, 2 );
			if ( count( $parts ) < 2 ) {
				continue;
			}
			$identifier = $parts[0];
			$after_identifier = $parts[1];
			list( $param_content, $parameters ) = SpecialGetDocbook::extractParametersInBrackets( $after_identifier );

			$docbook_preview .= '<br>' . $identifier . " "
				. implode(
					", ",
					array_map( static function ( $pagename ) use ( $linkRenderer ) {
						return $linkRenderer->makeKnownLink(
							Title::newFromText( $pagename ),
							$pagename
						);
					},
					explode( ',', $param_content )
				)
			);
		}

		return $docbook_link . '<br><h3>Content</h3>' . $docbook_preview;
	}

	/**
	 * @param array $options
	 * @return array
	 */
	public static function extractOptions( array $options ) {
		$results = [];

		foreach ( $options as $option ) {
			$pair = explode( '=', $option, 2 );
			if ( count( $pair ) === 2 ) {
				$name = trim( $pair[0] );
				$value = trim( $pair[1] );
				$results[$name] = $value;
			}

			if ( count( $pair ) === 1 ) {
				$name = trim( $pair[0] );
				$results[$name] = true;
			}
		}
		return $results;
	}

}
