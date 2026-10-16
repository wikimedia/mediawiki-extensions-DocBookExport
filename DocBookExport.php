<?php
use MediaWiki\Linker\LinkRenderer;
use MediaWiki\Title\Title;

class DocBookExport implements
	\MediaWiki\Parser\Hook\ParserFirstCallInitHook
{

	public function __construct(
		private readonly LinkRenderer $linkRenderer,
	) {
	}

	/**
	 * @param Parser $parser
	 * @return bool|void True or no return value to continue or false to abort
	 */
	public function onParserFirstCallInit( $parser ) {
		$parser->setHook( 'docbook', $this->parseDocBookSyntaxTagExtension( ... ) );
		$parser->setHook( 'footnote', $this->parseFootNoteSyntaxTagExtension( ... ) );
		$parser->setFunctionHook( 'docbook', $this->parseDocBookSyntaxParserFunction( ... ) );
		$parser->setFunctionHook( 'docbook_index', $this->parseDoocBookIndexParserExtension( ... ) );
	}

	/**
	 * @param Parser $parser
	 * @return string
	 */
	public function parseDoocBookIndexParserExtension( Parser $parser ) {
		$options = $this->extractOptions( array_slice( func_get_args(), 1 ) );
		$group_by = $options['grouping'];
		$parserOutput = $parser->getOutput();
		$parserOutput->setPageProperty( 'docbook_index_group_by', $group_by );
		return '';
	}

	/**
	 * @param Parser &$parser
	 * @return array
	 */
	public function parseDocBookSyntaxParserFunction( Parser &$parser ) {
		$options = $this->extractOptions( array_slice( func_get_args(), 1 ) );
		return [ $this->parseDocBookSyntax( $parser, $options ), 'noparse' => true, 'isHTML' => true ];
	}

	/**
	 * @param string $input
	 * @param array $args
	 * @param Parser $parser
	 * @param PPFrame $frame
	 * @return string
	 */
	public function parseFootNoteSyntaxTagExtension( string $input, array $args, Parser $parser, PPFrame $frame ) {
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
	public function parseDocBookSyntaxTagExtension( string $input, array $args, Parser $parser, PPFrame $frame ) {
		$args['page structure'] = $input;
		return $this->parseDocBookSyntax( $parser, $args );
	}

	/**
	 * @param Parser &$parser
	 * @param array $options
	 * @return string
	 */
	public function parseDocBookSyntax( Parser &$parser, array $options ) {
		if ( $parser->getTitle() == null ) {
			return "";
		}

		$serialized = serialize( $options );
		$book_name = str_replace( " ", "_", $options['title'] );
		if ( !empty( $options['volumenum'] ) ) {
			$book_name .= '_' . $options['volumenum'];
		}

		$parserOutput = $parser->getOutput();
		$parserOutput->setPageProperty( md5( 'docbook_' . $book_name ), $serialized );

		$docbook_link = $this->linkRenderer->makeKnownLink(
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
			[ $param_content, $parameters ] = SpecialGetDocbook::extractParametersInBrackets( $after_identifier );

			$docbook_preview .= '<br>' . $identifier . " "
				. implode(
					", ",
					array_map( function ( $pagename ) {
						return $this->linkRenderer->makeKnownLink(
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
	public function extractOptions( array $options ) {
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
