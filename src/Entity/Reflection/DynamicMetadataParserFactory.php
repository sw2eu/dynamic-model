<?php

namespace Sw2\DynamicModel\Entity\Reflection;

use Nette\Reflection\ClassType;
use Nextras\Orm\Entity\Reflection\IMetadataParserFactory;
use Sw2\DynamicModel\Entity\Reflection\Modifier\IModifier;
use Sw2\DynamicModel\Entity\Reflection\Modifier\IModifierBidirectional;

/**
 * Class DynamicMetadataParserFactory
 * @package App\Model
 */
class DynamicMetadataParserFactory implements IMetadataParserFactory
{
	/** @var IModifier[] */
	private $modifiers = [];

	/**
	 * Creates metadata parser.
	 * @param  array $entityClassesMap
	 * @return \Nextras\Orm\Entity\Reflection\IMetadataParser
	 */
	public function create(array $entityClassesMap)
	{
		$metadataParser = new DynamicMetadataParser($entityClassesMap);
		foreach ($this->modifiers as $name => $modifier) {
			$metadataParser->addModifier($name, [$modifier, 'apply']);

			if ($modifier instanceof IModifierBidirectional) {
				$metadataParser->addInverseModifier($name, $modifier);
			}
		}
		return $metadataParser;
	}

	/**
	 * @param string $name
	 * @param IModifier $modifier
	 */
	public function addModifier($name, IModifier $modifier)
	{
		$this->modifiers[$name] = $modifier;
	}

}
