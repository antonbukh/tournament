<?php
/**
 * @link	  http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Application;

use Zend\Db\Adapter\AdapterInterface;
use Zend\Db\ResultSet\ResultSet;
use Zend\Db\TableGateway\TableGateway;
use Zend\ModuleManager\Feature\ConfigProviderInterface;

class Module implements ConfigProviderInterface
{
	const VERSION = '3.0.3-dev';

	public function getConfig()
	{
		return include __DIR__ . '/../config/module.config.php';
	}

	public function getServiceConfig()
	{
		return [
			'factories' => [
				Model\TeamTable::class => function($container) {
					$tableGateway = $container->get(Model\TeamTableGateway::class);
					return new Model\TeamTable($tableGateway);
				},
				Model\TeamTableGateway::class => function ($container) {
					$dbAdapter = $container->get(AdapterInterface::class);
					$resultSetPrototype = new ResultSet();
					$resultSetPrototype->setArrayObjectPrototype(new Model\Team());
					return new TableGateway('tb1001_team', $dbAdapter, null, $resultSetPrototype);
				},
				Model\MatchTable::class => function($container) {
					$tableGateway = $container->get(Model\MatchTableGateway::class);
					return new Model\MatchTable($tableGateway);
				},
				Model\MatchTableGateway::class => function ($container) {
					$dbAdapter = $container->get(AdapterInterface::class);
					$resultSetPrototype = new ResultSet();
					$resultSetPrototype->setArrayObjectPrototype(new Model\Match());
					return new TableGateway('tb1003_match', $dbAdapter, null, $resultSetPrototype);
				},
			],
		];
	}
}
