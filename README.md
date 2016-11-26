Coercive Utility Xml
====================

The Xml Utility allows you to easily import, clean, extract, reformat Xml files.

Get
---
```
composer require coercive/xml
```

Usage
-----
XmlCleaner
```php
#
# FIRST : clean your xml file
#
use \Coercive\Utility\Xml\XmlCleaner;

# Load cleaner
$oCleaner = new XmlCleaner();

# You can add some options
$oCleaner = new XmlCleaner([
    XmlCleaner::OPTION_DECODE => [ ['&', '&amp;'] ],
    XmlCleaner::OPTION_DELETE_DOCTYPE => true,
    XmlCleaner::OPTION_DELETE_PARASITIC => true
]);

# Load the file
$oCleaner->loadFile('path/filename.xml');

# OR Load the string content
$oCleaner->loadString('<?xml ... ?><article>some_text</article> ... ');

# Clean the file
$oCleaner->clean();

# Get the content
$sXmlCleanedContent = $oCleaner->get();

# You can chain
$sXmlCleanedContent = (new XmlCleaner)->loadFile('path/filename.xml')->clean()->get();
```
XmlToArray
```php
#
# SECOND : transform file to array
#
use \Coercive\Utility\Xml\XmlToArray;

# Load ToArray converter
$oToArray = new XmlToArray($sXmlCleanedContent);

# You can add some options
$oToArray = new XmlToArray($sXmlCleanedContent, [
    XmlToArray::XML_OPTION_CASE_FOLDING => 0,
    XmlToArray::XML_OPTION_SKIP_WHITE => 0,
    XmlToArray::XML_OPTION_TARGET_ENCODING => 'UTF-8'
]);

# Parse to array when you're ready
$oToArray->parse();

# Get the array content
$aXml = $oToArray->get();

# You can chain
$aXml = (new XmlToArray($sXmlCleanedContent))->parse()->get();
```
ExtractArray
```php
#
# THIRD : extract array
#
use \Coercive\Utility\Xml\ExtractArray;

# Load array extractor
$oExtractArray = new ExtractArray($aXml);

# You can add some options
$oExtractArray = new ExtractArray($aXml, [
    self::ROOT => '/example/subexample',
    self::ROOT_DELIMITER => '/',
    self::ROOT_REQUIRED => false
]);

# Get your content (destructive array system)
$aTitle = $oExtractArray->get('title');
$aAuthors = $oExtractArray->get('authors');
$aArticle = $oExtractArray->get(); /* take all others */
```
FormatML
```php
#
# FOURTH : format your ML
#
use \Coercive\Utility\Xml\FormatML;

# Load ML Format
$oFormatML = new FormatML($aTitle);

# Add format options
$oFormatML = new FormatML($aTitle, [
    'bold' => '<b>' . FormatML::CONTENT . '</b>',
    'italic' => '<i>' . FormatML::CONTENT . '</i>',
    'link-www' => function($sContent, $aAttributes) {
        $sCible = isset($aAttributes['cible']) ? $aAttributes['cible'] : '#';
        return '<a href="'.$sCible.'">' . $sContent . '</a>';
    }
], [
    FormatML::OPTION_ALL_TAGS_REQUIRED => false,
    FormatML::OPTION_SKIP_MISSING_TAGS_CONTENT => false
]);

# Retrieve formated content
$sFormatedTitle = $oFormatML->get();
```
