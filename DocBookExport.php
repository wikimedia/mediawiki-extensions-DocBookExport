<?php

class DocBookExport {

	public static function onParserSetup( Parser &$parser ) {
		$parser->setHook( 'docbook', 'DocBookExport::parseDocBookSyntaxTagExtension' );
		$parser->setFunctionHook( 'docbook', 'DocBookExport::parseDocBookSyntaxParserFunction' );
		$parser->setFunctionHook( 'footnote', 'DocBookExport::parseFootNoteParserExtension' );
		$parser->setFunctionHook( 'docbook_index', 'DocBookExport::parseDoocBookIndexParserExtension' );
		return true;
	}

	public static function parseDoocBookIndexParserExtension( $parser ) {
		$options = self::extractOptions( array_slice( func_get_args(), 1 ) );
		$group_by = $options['grouping'];
        $parser->getOutput()->setProperty( 'docbook_index_group_by', $group_by );
		return '';
	}

	public static function parseFootNoteParserExtension( $parser ) {
		$options = self::extractOptions( array_slice( func_get_args(), 1 ) );
		$footnote_para = $options['para'];

		$output = '<a class="footnote" href="'. urlencode( $footnote_para ) .'"></a>';
		return array( $output, 'noparse' => true, 'isHTML' => true );
	}

	public static function parseDocBookSyntaxParserFunction( &$parser ) {
		$options = self::extractOptions( array_slice( func_get_args(), 1 ) );
		return array( self::parseDocBookSyntax( $parser, $options ), 'noparse' => true, 'isHTML' => true );
	}

	public static function parseDocBookSyntaxTagExtension( $input, array $args, Parser $parser, PPFrame $frame ) {
		$args['page structure'] = $input;
		return self::parseDocBookSyntax( $parser, $args );
	}

	public static function parseDocBookSyntax( &$parser, $options ) {
		global $wgScriptPath;

		if ( $parser->getTitle() == null ) {
			return "";
		}
        $serialized = serialize( $options );
		$book_name = str_replace( " ", "_", $options['title'] );
		if ( !empty( $options['volumenum'] ) ) {
			$book_name .= '_' . $options['volumenum'];
		}

        $parser->getOutput()->setProperty( md5( 'docbook_' . $book_name ), $serialized );
		
		$docbook_link = Linker::linkKnown( Title::makeTitle(NS_SPECIAL, 'GetDocbook'), "Get Docbook - " . $book_name, [], [ 'embed_page' => $parser->getTitle()->getText(), 'bookname' => $book_name ] );

		$docbook_preview = '';

		$page_structure = explode( "\n", $options['page structure'] );
		foreach( $page_structure as $current_line ) {
			$parts = explode( ' ', $current_line, 2 );
			if ( count( $parts ) < 2 ) {
				continue;
			}
			$identifier = $parts[0];
			$after_identifier = $parts[1];
			list( $param_content, $parameters ) = SpecialGetDocbook::extractParametersInBrackets( $after_identifier );

			$docbook_preview .= '<br>' . $identifier . " " . implode(
				", ",
				array_map( function( $pagename ) {
					return Linker::linkKnown( Title::newFromText( $pagename ), $pagename );
				}, explode( ',', $param_content ) )
			);
		}

		return $docbook_link . '<br><h3>Content</h3>' . $docbook_preview;
	}

	public static function extractOptions( array $options ) {
		$results = array();

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

?>