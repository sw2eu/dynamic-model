<?php

namespace Sw2\DynamicModel\Bridges\Nette\DI;

use Nette\Reflection\ClassType;
use Nette\Utils\Strings;
use Nextras\Orm\Bridges\NetteDI\OrmExtension;
use Nextras\Orm\Entity\Reflection\MetadataParserFactory;
use Nextras\Orm\Model\Model;
use Nextras\Orm\Repository\Repository;
use Sw2\DynamicModel\DynamicModel;

/**
 * Class DynamicOrmExtension
 * @package Sw2\DynamicModel\Bridges\Nette\DI
 */
class DynamicOrmExtension extends OrmExtension
{
	/** @var array */
	public $defaults = [
		'metadataParserFactory' => MetadataParserFactory::class
	];

	public function loadConfiguration()
	{
		$this->config = $this->getConfig() + $this->defaults;
		if (!isset($this->config['model'])) {
			$this->config['model'] = DynamicModel::class;
		}
	}

	public function beforeCompile()
	{
		$repositories = $this->prepareRepositories();
		$repositoriesConfig = Model::getConfiguration($repositories);

		$this->setupCache();
		$this->setupDependencyProvider();
		$this->setupMetadataParserFactory($this->config['metadataParserFactory']);
		$this->setupRepositoryLoader($repositories);
		$this->setupMetadataStorage($repositoriesConfig);
		$this->setupModel($this->config['model'], $repositoriesConfig);
	}

	/**
	 * @return array
	 */
	protected function prepareRepositories()
	{
		$builder = $this->getContainerBuilder();
		$repositories = [];
		foreach ($builder->findByType(Repository::class) as $repositoryName => $definition) {
			$repositoryClass = $definition->getClass();
			$reflection = new ClassType($repositoryClass);
			$name = $reflection->getAnnotation('entity2');
			if ($name === NULL) {
				$name = $this->createEntityName($repositoryClass);
			}
			$repositories[$name] = $repositoryClass;

			$mapperClass = Strings::replace($repositoryClass, '~Repository$~', 'Mapper');
			if (class_exists($mapperClass)) {
				$mapperName = $builder->getByType($mapperClass);
				if ($mapperName === NULL) {
					$mapperName = Strings::replace($repositoryName, '~Repository$~', 'Mapper');
					$builder->addDefinition($mapperName)
						->setClass($mapperClass)
						->setArguments(['cache' => '@' . $this->prefix('cache')]);
				}

				$definition->setArguments([
					'@' . $mapperName,
					'@' . $this->prefix('dependencyProvider'),
				]);
				$definition->addSetup('setModel', ['@' . $this->prefix('model')]);
			}
		}
		return $repositories;
	}

	/**
	 * @param string $repositoryClass
	 * @return string
	 */
	protected function createEntityName($repositoryClass)
	{
		return lcfirst(Strings::replace($this->trimNamespace($repositoryClass), '~Repository$~'));
	}

	/**
	 * Trims namespace part from fully qualified class name
	 *
	 * @param $class
	 * @return string
	 */
	protected function trimNamespace($class)
	{
		$class = explode('\\', $class);
		return end($class);
	}

}
