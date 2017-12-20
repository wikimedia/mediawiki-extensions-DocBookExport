<?php

class DocBookExport {

	public static function onParserSetup( Parser &$parser ) {
		$parser->setHook( 'docbook', 'DocBookExport::parseDocBookSyntaxTagExtension' );
		$parser->setFunctionHook( 'docbook', 'DocBookExport::parseDocBookSyntaxParserFunction' );
		return true;
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

		$api_download_link = $wgScriptPath . '/api.php?action=getdocbook&outputformat=docbook&bookname='. $wgTitle->getText();
		$api_download_pdf_link = $wgScriptPath . '/api.php?action=getdocbook&outputformat=pdf&bookname='. $wgTitle->getText();

		return '<a href="' . $api_download_link .'">Generate Docbook</a>
			<br/>
			<a href="' . $api_download_pdf_link .'">Generate PDF</a>
			';
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