<?php

namespace Sw2\DynamicModel\Entity\Reflection\Modifier;

use Nette\Reflection\ClassType;
use Nextras\Orm\Entity\Reflection\EntityMetadata;

/**
 * Interface IModifierBidirectional
 * @package Sw2\DynamicModel\Entity\Reflection\Modifier
 */
interface IModifierBidirectional extends IModifier
{

	/**
	 * @param ClassType $currentReflection
	 */
	public function setCurrentReflection(ClassType $currentReflection);

	/**
	 * Returns entity class names for property modification.
	 * @return string[]
	 */
	public static function getEntityClassNames();

	/**
	 * @param EntityMetadata $entityMetadata
	 * @param array $entityClassesMap
	 */
	public function modifyProperties(EntityMetadata $entityMetadata, array $entityClassesMap);

}
