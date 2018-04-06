# DocBookExport
A MediaWiki extension to DocBook from wiki pages


#Installation

For installation of this extension you need to have ssh access to your server.

* To install the extension, place the entire 'DocBookExport' directory within your MediaWiki 'extensions' directory
* Just enter the following command in the 'extensions' directory: 'git clone https://bitbucket.org/wikiworksdev/docbookexport.git DocBookExport'
* Add the following line to your LocalSettings.php file: 'wfLoadExtension( 'DocBookExport' );'
* Verify you have this extension installed by visiting the /Special:Version page on your wiki.

#Install Dependencies

* Install pandoc.
	* See https://github.com/jgm/pandoc/releases for the latest version
	* For Ubuntu use the following commands, replace the version number as per the latest release.
	* sudo wget https://github.com/jgm/pandoc/releases/download/2.0.5/pandoc-2.0.5-1-amd64.deb
	* sudo dpkg -i pandoc-2.0.5-1-amd64.deb
* Configure $wgDocBookExportPandocPath to your Pandoc path in case the "pandoc" command doesn't work
* Install extension Figures - It supports defining figures in MediaWiki
* To download DocBook files in Zip format install the ZipArchive php extension.
	* See https://stackoverflow.com/q/3872555/1150075
* To download DocBook in PDF format install the xsltproc and fop
	* See https://askubuntu.com/a/462343 and https://www.howtoinstall.co/en/ubuntu/trusty/fop


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
