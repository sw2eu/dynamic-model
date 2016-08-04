<?php

namespace Sw2\DynamicModel\Entity\Reflection\Modifier;

use Nextras\Orm\Entity\Reflection\PropertyMetadata;

/**
 * Interface IModifier
 * @package Sw2\DynamicModel\Entity\Reflection
 */
interface IModifier
{

	/**
	 * @param PropertyMetadata $property
	 * @param array $args
	 */
	public function apply(PropertyMetadata $property, &$args);

}
