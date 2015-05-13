<?php
namespace Redaxscript\Filter;

use DOMDocument;
use Redaxscript\Db;

/**
 * children class to filter html
 *
 * @since 2.4.0
 *
 * @category Redaxscript
 * @package Filter
 * @author Henry Ruhs
 */

class Html implements FilterInterface
{
	/**
	 * array of tags
	 *
	 * @var array
	 */

	protected $_allowedTags = array(
		'div',
		'em',
		'h1',
		'h2',
		'h3',
		'h4',
		'h5',
		'h6',
		'li',
		'p',
		'ol',
		'span',
		'sub',
		'sup',
		'table',
		'td',
		'tr',
		'strong',
		'ul'
	);

	/**
	 * array of attributes
	 *
	 * @var array
	 */

	protected $_allowedAttributes = array(
		'class',
		'id'
	);

	/**
	 * sanitize the html
	 *
	 * @since 2.4.0
	 *
	 * @param string $html target html
	 * @param boolean $filter optional filter nodes
	 *
	 * @return string
	 */

	public function sanitize($html = null, $filter = true)
	{
		$charset = Db::getSettings('charset');
		$html = mb_convert_encoding($html, 'html-entities', $charset);
		$html = html_entity_decode($html, ENT_QUOTES, $charset);
		$doc = $this->_createDocument($html);
		$doc = $this->_cleanDocument($doc);

		/* filter nodes */

		if ($filter === true)
		{
			/* disable errors */

			libxml_use_internal_errors(true);

			/* clean tags and attributes */

			$doc = $this->_stripTags($doc);
			$doc = $this->_stripAttributes($doc);

			/* clear errors */

			libxml_clear_errors();
		}

		/* collect output */

		$output = trim($doc->saveHTML());
		return $output;
	}

	/**
	 * create the document
	 *
	 * @since 2.4.0
	 *
	 * @param string $html target html
	 *
	 * @return DOMDocument
	 */

	public function _createDocument($html = null)
	{
		$doc = new DOMDocument();
		$doc->loadHTML($html);
		return $doc;
	}

	/**
	 * clean the document
	 *
	 * @since 2.4.0
	 *
	 * @param DOMDocument $doc target document
	 *
	 * @return DOMDocument
	 */

	protected function _cleanDocument(DOMDocument $doc)
	{
		/* clean document */

		if (isset($doc->firstChild) && $doc->firstChild->nodeType === XML_DOCUMENT_TYPE_NODE)
		{
			/* remove doctype */

			$doc->removeChild($doc->firstChild);

			/* remove tags */

			if (isset($doc->firstChild->firstChild->firstChild) && $doc->firstChild->firstChild->tagName === 'body')
			{
				$doc->replaceChild($doc->firstChild->firstChild->firstChild, $doc->firstChild);
			}
		}
		return $doc;
	}

	/**
	 * strip the tags
	 *
	 * @since 2.4.0
	 *
	 * @param object $node target node
	 *
	 * @return object
	 */

	protected function _stripTags($node = null)
	{
		foreach ($node->childNodes as $childNode)
		{
			if ($childNode->nodeType === XML_ELEMENT_NODE)
			{
				if (!in_array($childNode->tagName, $this->_allowedTags))
				{
					$childNode->parentNode->removeChild($childNode);
				}
				if ($childNode->hasChildNodes())
				{
					$this->_stripTags($childNode);
				}
			}
		}
		return $node;
	}

	/**
	 * strip the attributes
	 *
	 * @since 2.4.0
	 *
	 * @param object $node target node
	 *
	 * @return object
	 */

	protected function _stripAttributes($node = null)
	{
		return $node;
	}
}
