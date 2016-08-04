<?php

namespace Sw2\DynamicModel\Bridges\Nette\DI;

use Nette\DI\Compiler;
use Nette\Reflection\ClassType;
use Nette\Utils\Strings;
use Nextras\Orm\Bridges\NetteDI\OrmExtension;
use Nextras\Orm\Bridges\NetteDI\RepositoryLoader;
use Nextras\Orm\Model\MetadataStorage;
use Nextras\Orm\Model\Model;
use Nextras\Orm\Repository\Repository;
use Sw2\DynamicModel\Entity\Reflection\DynamicMetadataParserFactory;
use Sw2\DynamicModel\Model\DynamicModel;

/**
 * Class DynamicOrmExtension
 * @package Sw2\DynamicModel\Bridges\Nette\DI
 */
class DynamicOrmExtension extends OrmExtension
{
	/** @var array */
	public $defaults = [
		'metadataParserFactory' => DynamicMetadataParserFactory::class
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
			$mapperName = $builder->getByType($mapperClass);
			if ($mapperName === NULL) {
				$mapperName = Strings::replace($repositoryName, '~Repository$~', 'Mapper');
				$builder->addDefinition($mapperName)
					->setClass($mapperClass)
					->setArguments(['cache' => '@' . $this->prefix('cache')]);
			} else {
				$builder->getDefinition($mapperName)
					->setArguments(['cache' => '@' . $this->prefix('cache')]);
			}
			$definition->setArguments([
				'mapper' => '@' . $mapperName,
				'dependencyProvider' => '@' . $this->prefix('dependencyProvider')
			]);
			$definition->addSetup('setModel', ['@' . $this->prefix('model')]);
		}
		return $repositories;
	}

	/**
	 * @param string|array $config
	 */
	protected function setupMetadataParserFactory($config)
	{
		$builder = $this->getContainerBuilder();
		if (is_array($config) && empty($config['class'])) {
			$config['class'] = $this->defaults['metadataParserFactory'];
		}
		Compiler::parseService($builder->addDefinition($this->prefix('metadataParserFactory')), $config);
	}

	/**
	 * @param array $repositories
	 */
	protected function setupRepositoryLoader(array $repositories)
	{
		$builder = $this->getContainerBuilder();
		$map = [];
		foreach ($repositories as $name => $className) {
			$map[$className] = $builder->getByType($className);
		}

		$builder = $this->getContainerBuilder();
		$builder->addDefinition($this->prefix('repositoryLoader'))
			->setClass(RepositoryLoader::class)
			->setArguments([
				'repositoryNamesMap' => $map,
			]);
	}

	protected function setupMetadataStorage(array $repositoryConfig)
	{
		$builder = $this->getContainerBuilder();
		$builder->addDefinition($this->prefix('metadataStorage'))
			->setClass(MetadataStorage::class)
			->setArguments([
				'entityClassesMap' => $repositoryConfig[2],
				'cache' => '@' . $this->prefix('cache'),
				'metadataParserFactory' => '@' . $this->prefix('metadataParserFactory'),
				'repositoryLoader' => '@' . $this->prefix('repositoryLoader'),
			]);
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
