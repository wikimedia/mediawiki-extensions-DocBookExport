<?php

class DocBookExport {

	public static function onParserSetup( Parser &$parser ) {
		$parser->setHook( 'docbook', 'DocBookExport::parseDocBookSyntaxTagExtension' );
		$parser->setFunctionHook( 'docbook', 'DocBookExport::parseDocBookSyntaxParserFunction' );
		$parser->setFunctionHook( 'footnote', 'DocBookExport::parseFootNoteParserExtension' );
		return true;
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
		global $wgScriptPath, $wgTitle;

        $serialized = serialize( $options );
        $parser->getOutput()->setProperty( 'docbook', $serialized );

		return Linker::linkKnown( Title::makeTitle(NS_SPECIAL, 'GetDocbook'), "Get Docbook", [], [ 'bookname' => $wgTitle->getText() ] );
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