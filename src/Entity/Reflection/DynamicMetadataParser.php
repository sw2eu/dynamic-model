<?php

namespace Sw2\DynamicModel\Entity\Reflection;

use Nextras\Orm\Entity\Reflection\MetadataParser;
use Nextras\Orm\Entity\Reflection\PropertyMetadata;
use Sw2\DynamicModel\Entity\Reflection\Modifier\IModifierBidirectional;

/**
 * Class DynamicMetadataParser
 * @package Sw2\DynamicModel\Entity\Reflection
 */
class DynamicMetadataParser extends MetadataParser
{
	/** @var IModifierBidirectional[] */
	private $inverseModifier = [];

	/**
	 * @param string $name
	 * @param IModifierBidirectional $modifier
	 */
	public function addInverseModifier($name, IModifierBidirectional $modifier)
	{
		$this->inverseModifier[$name] = $modifier;
	}

	protected function loadProperties(& $fileDependencies)
	{
		parent::loadProperties($fileDependencies);
		foreach ($this->inverseModifier as $inverseModifier) {
			if (in_array($this->metadata->className, $inverseModifier::getEntityClassNames())) {
				call_user_func([$inverseModifier, 'modifyProperties'], $this->metadata, $this->entityClassesMap);
			}
		}
	}

	/**
	 * @param PropertyMetadata $property
	 * @param string $modifier
	 * @param array $args
	 */
	protected function processPropertyModifier(PropertyMetadata $property, $modifier, array $args)
	{
		if (isset($this->inverseModifier[$modifier])) {
			$this->inverseModifier[$modifier]->setCurrentReflection($this->currentReflection);
		}
		parent::processPropertyModifier($property, $modifier, $args);
	}


}
