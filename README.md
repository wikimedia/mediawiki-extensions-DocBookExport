# DocBookExport
A MediaWiki extension to DocBook from wiki pages


#Installation

For installation of this extension you need to have ssh access to your server.

* To install the extension, place the entire 'DocBookExport' directory within your MediaWiki 'extensions' directory
* Just enter the following command in the 'extensions' directory: 'git clone https://bitbucket.org/wikiworksdev/docbookexport.git DocBookExport'
* Add the following line to your LocalSettings.php file: 'wfLoadExtension( 'DocBookExport' );'
* Verify you have this extension installed by visiting the /Special:Version page on your wiki.
* Install pandoc - a dependancy for this extension. See https://pandoc.org/installing.html
* Install extension Figures - It supports defining figures on MediaWiki
* Configure $wgDocBookExportPandocPath to your Pandoc path in case the "pandoc" command doesn't work

#Usage
To create a book define the page structure using the docbook parser function or tag extension. See examples below.

Parser Function Example:

{{#docbook:  
page structure=  
* Buying clothes intro,Buying shoes (title=Buying the right clothes, header=Custom Header)  
** How I buy shoes  
|title=My Guide to Bowling  
|cover page=Cover Contents  
|header=My Guide to Bowling  
|footer=My Guide to Bowling  
|index term categories=index terms  
|index terms=shoe,clothes  
}}  


#Credits
This extension has been written by Nischay Nahata for wikiworks.com and is sponsored by NATO
