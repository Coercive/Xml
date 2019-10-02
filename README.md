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
$cleaner = new XmlCleaner();

# You can add some options
$cleaner = new XmlCleaner([
    XmlCleaner::OPTION_DECODE => [ ['&', '&amp;'] ],
    XmlCleaner::OPTION_DELETE_DOCTYPE => true,
    XmlCleaner::OPTION_DELETE_PARASITIC => true
]);

# Load the file
$cleaner->loadFile('path/filename.xml');

# OR Load the string content
$cleaner->loadString('<?xml ... ?><article>some_text</article> ... ');

# Clean the file
$cleaner->clean();

# Get the content
$xmlCleanedContent = $cleaner->get();

# You can chain
$xmlCleanedContent = (new XmlCleaner)->loadFile('path/filename.xml')->clean()->get();
```
XmlToArray
```php
#
# SECOND : transform file to array
#
use \Coercive\Utility\Xml\XmlToArray;

# Load ToArray converter
$toArray = new XmlToArray($xmlCleanedContent);

# You can add some options
$toArray = new XmlToArray($xmlCleanedContent, [
    XmlToArray::XML_OPTION_CASE_FOLDING => 0,
    XmlToArray::XML_OPTION_SKIP_WHITE => 0,
    XmlToArray::XML_OPTION_TARGET_ENCODING => 'UTF-8'
]);

# Parse to array when you're ready
$toArray->parse();

# Get the array content
$xml = $toArray->get();

# You can chain
$xml = (new XmlToArray($xmlCleanedContent))->parse()->get();
```
ExtractArray
```php
#
# THIRD : extract array
#
use \Coercive\Utility\Xml\ExtractArray;

# Load array extractor
$extractArray = new ExtractArray($xml);

# You can add some options
$extractArray->setRoot('/example/subexample');
$extractArray->setDelimiter('/');
$extractArray->setRequired(false);

# Get your content (destructive array system)
$title = $extractArray->get('title');
$authors = $extractArray->get('authors');
$article = $extractArray->get(); /* take all others */

# You have two extraction options :

# Get all elements
$listAll = $extractArray->get('example', true);

# Get all elements at the foreground of setted root
$listAllForeground = $extractArray->get('example', true, true); 

```
FormatML
```php
#
# FOURTH : format your ML
#
use \Coercive\Utility\Xml\FormatML;

# Load ML Format
$formatML = new FormatML($aTitle);

# You can add some format options
$formatML = new FormatML($aTitle, [
    'bold' => '<b>' . FormatML::CONTENT . '</b>',
    'italic' => '<i>' . FormatML::CONTENT . '</i>',
    'link-www' => function($content, $attributes, $parents) {
        $color = 'blue';
        if(in_array('quote', $parents)) {
            $color = 'red';
        }
        $target = $attributes['cible'] ?? '#';
        return '<a style="color: '.$color.';" href="'.$target.'">' . $content . '</a>';
    }
], [
    FormatML::OPTION_ALL_TAGS_REQUIRED => false,
    FormatML::OPTION_SKIP_MISSING_TAGS_CONTENT => false
]);

# Retrieve formated content
$formatedTitle = $formatML->get();
```
