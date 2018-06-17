<?php

use MediaWiki\MediaWikiServices;

/**
 * @author Nischayn22
 */

class DocBookExportAPI extends ApiBase {

	static $excludedTags = array( 'h1', 'h2', 'h3', 'h4', 'h5', 'h6' );

	public function __construct( $query, $moduleName ) {
		parent::__construct( $query, $moduleName );
	}

	public function execute() {
		global $wgServer, $wgScriptPath;

		$popts = new ParserOptions();
		$popts->enableLimitReport( false );
		$popts->setIsPreview( false );
		$popts->setIsSectionPreview( false );
		$popts->setEditSection( false );
		$popts->setTidy( true );

		$bookName = $this->getMain()->getVal( 'bookname' );
		$title = Title::newFromText( $bookName );

		$dbr = wfGetDB( DB_SLAVE );
		$propValue = $dbr->selectField( 'page_props', // table to use
			'pp_value', // Field to select
			array( 'pp_page' => $title->getArticleID(), 'pp_propname' => "docbook" ), // where conditions
			__METHOD__
		);
		$options = unserialize( $propValue );
		$book_title = '';
		$book_contents = '<!DOCTYPE book PUBLIC "-//OASIS//DTD DocBook XML V4.1.2//EN" "http://www.oasis-open.org/docbook/xml/4.1.2/docbookx.dtd">
		<book>';

		$all_files = array();
		$docbook_folder = escapeshellcmd( str_replace( ' ', '_', $options['title'] ) );
		$index_terms = array();
		if ( array_key_exists( 'index terms', $options ) ) {
			$index_terms = explode( ",", $options['index terms'] );
		}

		$index_categories = array();
		if ( array_key_exists( 'index term categories', $options ) ) {
			$index_categories = explode( ",", $options['index term categories'] );
		}

		foreach( $index_categories as $index_category ) {
			$categoryMembers = Category::newFromName( $index_category )->getMembers();
			foreach( $categoryMembers as $index_title ) {
				$index_terms[] = $index_title->getText();
			}
		}

		$index_terms_capitalized = array();
		foreach( $index_terms as $index_term ) {
			if ( ucfirst( $index_term ) != $index_term ) {
				$index_terms_capitalized[] = ucfirst( $index_term );
			} else {
				$index_terms_capitalized[] = lcfirst( $index_term );
			}
		}
		$index_terms = array_merge( $index_terms, $index_terms_capitalized );

		if ( array_key_exists( 'cover page', $options ) ) {
			$book_contents .= '<info><cover>' . $this->getDocbookfromWikiPage( $options['cover page'], $popts, $docbook_folder, $index_terms, $all_files ) . '</cover></info>';
		}

		$book_contents .= '<title>' . $options['title'] . '</title>';

		$uploadDir = $this->getUploadDir();
		rrmdir( "$uploadDir/$docbook_folder" );
		mkdir( "$uploadDir/$docbook_folder" );
		mkdir( "$uploadDir/$docbook_folder/images" );

		$xsl_contents = file_get_contents( __DIR__ . '/docbookexport_template.xsl' );
		$docbookXslPath = realpath( __DIR__ .'/docbook-xsl-1.79.1/fo/docbook.xsl' );
		$xsl_contents = str_replace( 'DOCBOOKXSLPLACEHOLDER', $docbookXslPath, $xsl_contents );
		if ( array_key_exists( 'header', $options ) ) {
			$xsl_contents = str_replace( 'HEADERPLACEHOLDER', $options['header'], $xsl_contents );
		}
		if ( array_key_exists( 'footer', $options ) ) {
			$xsl_contents = str_replace( 'FOOTERPLACEHOLDER', $options['footer'], $xsl_contents );
		}

		file_put_contents( "$uploadDir/$docbook_folder/docbookexport.xsl", $xsl_contents );
		$all_files["docbookexport.xsl"] = "$uploadDir/$docbook_folder/docbookexport.xsl";

		$close_tags = array();
		$deep_level = 0;
		$page_structure = explode( "\n", $options['page structure'] );
		$content_started = false;
		foreach( $page_structure as $current_line ) {
			$display_pagename = '';
			$custom_header = '';
			$parts = explode( ' ', $current_line, 2 );
			if ( count( $parts ) < 2 ) {
				continue;
			}
			$identifier = $parts[0];
			$after_identifier = $parts[1];

			if ( $identifier == '*' ) {
			$content_started = true;
				$this_level = $deep_level;
				while( $this_level >= 0 ) {
					if ( array_key_exists( $this_level, $close_tags ) ) {
						$book_contents .= $close_tags[$this_level];
						$close_tags[$this_level] = '';
					}
					$this_level--;
				}
				$book_contents .= '<chapter>';
				if ( !array_key_exists( 0, $close_tags ) ) {
					$close_tags[0] = '';
				}
				$close_tags[0] .= '</chapter>';
			} else if ( $identifier[0] == '*' ) {
				$indent_level = strlen( $identifier ) - 1;
				$deep_level = max( $deep_level, $indent_level );
				$this_level = $deep_level;
				while( $this_level >= $indent_level ) {
					if ( array_key_exists( $this_level, $close_tags ) ) {
						$book_contents .= $close_tags[$this_level];
						$close_tags[$this_level] = '';
					}
					$this_level--;
				}
				$book_contents .= '<section>';
				if ( !array_key_exists( $indent_level, $close_tags ) ) {
					$close_tags[$indent_level] = '';
				}
				$close_tags[$indent_level] .= '</section>';
			} else if ( $identifier == '?' ) {
				$this_level = $deep_level;
				while($this_level >= 0) {
					if (array_key_exists($this_level, $close_tags)) {
						$book_contents .= $close_tags[$this_level];
						$close_tags[$this_level] = '';
					}
					$this_level--;
				}
				if ( !$content_started ) {
					$book_contents .= '<preface>';
				} else {
					$book_contents .= '<appendix>';
				}
				if ( !array_key_exists( 0, $close_tags ) ) {
					$close_tags[0] = '';
				}
				if ( !$content_started ) {
					$close_tags[0] .= '</preface>';
				} else {
					$close_tags[0] .= '</appendix>';
				}
			} else {
				$this->getResult()->addValue( 'result', 'failed', "Unsupported identifier: $identifier used in page structure" );
			}

			$parts = explode( '(', $after_identifier );

			if ( count( $parts ) == 2 ) {
				$wiki_pages = explode( ',', $parts[0] );
				$line_props = explode( ')', $parts[1] )[0];
				$chunks = array_chunk(preg_split('/(=|,)/', $line_props), 2); // See https://stackoverflow.com/a/32768029/1150075
				$line_props = array_combine(array_column($chunks, 0), array_column($chunks, 1));
				if ( array_key_exists( 'title', $line_props ) ) {
					$display_pagename = $line_props['title'];
				}
				if ( array_key_exists( 'header', $line_props ) ) {
					$custom_header = ' header="' . $line_props['header']. '"';
				}
			} else {
				$wiki_pages = explode( ',', $parts[0] );
				$display_pagename = $wiki_pages[0];
			}

			$book_contents .= "<title$custom_header>$display_pagename</title>";
			foreach( $wiki_pages as $wikipage ) {
				$book_contents .= $this->getDocbookfromWikiPage( $wikipage, $popts, $docbook_folder, $index_terms, $all_files );
			}
		}
		$this_level = $deep_level;
		while($this_level >= 0) {
			if (array_key_exists($this_level, $close_tags)) {
				$book_contents .= $close_tags[$this_level];
				$close_tags[$this_level] = '';
			}
			$this_level--;
		}
		$book_contents .= '<index/></book>';

		file_put_contents( "$uploadDir/$docbook_folder/$docbook_folder.xml", $book_contents );
		$all_files[$options['title'] .'.xml'] = "$uploadDir/$docbook_folder/$docbook_folder.xml";

		$outputformat = $this->getMain()->getVal( 'outputformat' );

		$output_filename = '';
		$output_filepath = '';
		$filesize = 0;
		$content_type = '';
		if ( $outputformat == 'docbook' ) {
			$output_filename = $docbook_folder .".zip";
			$output_filepath = "$uploadDir/". $output_filename;
			$zip = new ZipArchive();

			if(file_exists($output_filepath)){
				unlink($output_filepath);
			}
			if ($zip->open($output_filepath, ZipArchive::CREATE)!==TRUE) {
				exit("cannot open <$output_filepath>\n");
			}
			foreach($all_files as $filename => $path) {
				$zip->addFromString($filename, file_get_contents($path));
			}
			$zip->close();
			$filesize = filesize( $output_filepath );
			$content_type = 'application/zip';
		} else if ( $outputformat == 'pdf' ) {
			$output_filename = $docbook_folder .".pdf";
			$output_filepath = "$uploadDir/". $output_filename;

			shell_exec( "xsltproc --output $uploadDir/$docbook_folder/$docbook_folder.fo $uploadDir/$docbook_folder/docbookexport.xsl $uploadDir/$docbook_folder/$docbook_folder.xml" );

			shell_exec( "fop -fo " . "$uploadDir/$docbook_folder/$docbook_folder.fo -pdf $output_filepath" );

			$filesize = filesize( $output_filepath );
			$content_type = 'application/pdf';
		} else {
			$this->getResult()->addValue( 'result', 'failed', 'Invalid output format or not specified');
		}

		if ( $filesize ) {
			ob_clean();
			ob_end_flush();
			header('Content-Disposition: attachment; filename="'. $output_filename . '"');
			header('Content-Type: ' . $content_type);
			header('Content-Length: ' .  $filesize );
			header('Connection: close');
			readfile( $output_filepath );
		} else {
			$this->getResult()->addValue( 'result', 'success', 'Unable to start auto download. Download using this link: ' . $output_filepath );
		}
	}

	public function getDocbookfromWikiPage( $wikipage, $popts, $docbook_folder, $index_terms, &$all_files ) {
		global $wgDocBookExportPandocPath;
		$placeholderId = 0;
		$footnotes = array();
		$xreflabels = array();

		$titleObj = Title::newFromText( $wikipage );
		$pageObj = new WikiPage( $titleObj );

		$content = $pageObj->getContent( Revision::RAW );
		if ( !$content ) {
			return '';
		}
		$wikitext = $content->getNativeData();

		preg_match_all( '/<ref ?.*>(.*)<\/ref>/', $wikitext, $matches );

		if ( count( $matches[1] ) > 0 ) {
			$footnotes = $matches[1];
			$wikitext = preg_replace_callback(
				'/<ref ?.*>(.*)<\/ref>/',
				function( $matches ) use ( &$placeholderId ) {
					return 'PLACEHOLDER-' . ++$placeholderId;
				},
				$wikitext
			);
			$content = new WikitextContent( $wikitext );
		}

		$parser_output = $content->getParserOutput( $titleObj, null, $popts );
		if ( !$parser_output ) {
			return '';
		}

		$page_html = $parser_output->getText();

		$dom = new DOMDocument();
		libxml_use_internal_errors(true);
		$dom->loadHtml('<html>' . $page_html . '</html>');
		libxml_clear_errors();

		foreach( self::$excludedTags as $tag ) {
			foreach( $dom->getElementsByTagName( $tag ) as $node ) {
				$node->parentNode->removeChild( $node );
			}
		}

		foreach( $dom->getElementsByTagName( 'figure' ) as $node ) {
			$xreflabels[] = $node->getAttribute( 'xreflabel' );
		}

		$temp_file = tempnam(sys_get_temp_dir(), 'docbook_html');
		if ( !file_put_contents( $temp_file, $dom->saveHTML() ) ) {
			return '';
		}
		$cmd = escapeshellcmd( $wgDocBookExportPandocPath ) . " ". $temp_file . " -f html -t docbook 2>&1";
		$pandoc_output = shell_exec( $cmd );

		if ( !$pandoc_output ) {
			return '';
		}

		$doc = new DOMDocument();
		$doc->loadXML( '<root xmlns:xlink="http://www.w3.org/1999/xlink">' . $pandoc_output . '</root>' );

		foreach( $doc->getElementsByTagName( 'figure' ) as $node ) {
			$label = array_shift( $xreflabels );
			$node->setAttribute( 'xreflabel', $label );
			$node->setAttribute( 'id', $label );
			$node->appendChild( $doc->createElement( 'title', $label ) );
		}

		foreach( $doc->getElementsByTagName( 'imagedata' ) as $node ) {
			$file_url = $node->getAttribute( 'fileref' );
			$filename = basename( $file_url );
			$file_path = wfFindFile( $filename )->getLocalRefPath();
			$uploadDir = $this->getUploadDir();
			file_put_contents( "$uploadDir/$docbook_folder/images/$filename", file_get_contents( $file_path ) );
			$node->setAttribute( 'fileref', "images/$filename" );
			$all_files["images/$filename"] = "$uploadDir/$docbook_folder/images/$filename";
		}

		foreach( $doc->getElementsByTagName( 'link' ) as $node ) {
			if ( $node->hasAttribute( 'role' ) && $node->getAttribute( 'role' ) == 'xref' ) {
				$label = str_replace( '_', ' ', explode( '#', $node->getAttribute( 'xlink:href' ) )[1] );
				$xrefNode = $doc->createElement( 'xref' );
				$xrefNode->setAttribute( 'linkend', $label );
				$node->parentNode->replaceChild( $xrefNode , $node );
			} else if ( $node->hasAttributeNS( 'http://www.w3.org/1999/xlink', 'href' ) ) {
				$href = $node->getAttributeNS( 'http://www.w3.org/1999/xlink', 'href' );
				$page_name = basename( $href );
				if ( Title::newFromText( $page_name )->exists() ) {
					$linkNode = $doc->createElement( 'link' );
					$linkNode->setAttribute( 'linkend', "page-" . $page_name );
					$linkNode->nodeValue = $node->nodeValue;
					$node->parentNode->replaceChild( $linkNode , $node );
				}
			}
		}

		foreach( $doc->getElementsByTagName( 'para' ) as $node ) {
			if ( $node->hasChildNodes() ) {
				foreach($node->childNodes as $node) {
					if ($node->nodeName == '#text') {
						foreach( $index_terms as $index_term ) {
							$index_term = trim($index_term);
							$node->nodeValue = str_replace( $index_term , "INDEXPLACEHOLDERBEGIN" . $index_term . "INDEXPLACEHOLDEREND" . $index_term , $node->nodeValue );
						}
					}
				}
			}
		}

		if ( $doc->getElementsByTagName( 'root' )->length > 0 ) {
			$pandoc_output = '';
			foreach ( $doc->getElementsByTagName( 'root' )->item(0)->childNodes as $node ) {
			   $pandoc_output .= $doc->saveXML( $node );
			}
		}

		$pandoc_output = str_replace( "INDEXPLACEHOLDERBEGIN", "<indexterm><primary>", $pandoc_output );
		$pandoc_output = str_replace( "INDEXPLACEHOLDEREND", "</primary></indexterm>", $pandoc_output );

		$placeholderId = 0;
		foreach( $footnotes as $footnote ) {
			$pandoc_output = str_replace( 'PLACEHOLDER-' . ++$placeholderId, '<footnote><para>' . $footnote . '</para></footnote>', $pandoc_output );
		}
		return '<anchor id="page-'. str_replace( ' ', '_', $wikipage ) .'" />' . $pandoc_output;
	}

	/**
	 * Get DocBookExport's custom upload directory (within $wgUploadDirectory).
	 * @return string Full filesystem path with no trailing slash.
	 */
	protected function getUploadDir() {
		$uploadDirectory = MediaWikiServices::getInstance()
			->getMainConfig()
			->get( 'UploadDirectory' );
		$uploadDir = $uploadDirectory . '/DocBookExport';
		if ( !is_dir( $uploadDir ) ) {
			mkdir( $uploadDir );
		}
		$uploadDirFull = rtrim( realpath( $uploadDir ), DIRECTORY_SEPARATOR );
		if ( !is_dir( $uploadDirFull ) ) {
			throw new Exception( "Unable to create directory: $uploadDir" );
		}
		return $uploadDirFull;
	}
}

function rrmdir($dir) { 
   if (is_dir($dir)) { 
     $objects = scandir($dir); 
     foreach ($objects as $object) { 
       if ($object != "." && $object != "..") { 
         if (is_dir($dir."/".$object))
           rrmdir($dir."/".$object);
         else
           unlink($dir."/".$object); 
       } 
     }
     rmdir($dir);
   } 
}
