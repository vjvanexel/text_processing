<?php
/**
 * Created by PhpStorm.
 * User: vjvan
 * Date: 1/26/2017
 * Time: 15:00
 */
namespace Text\Processing;

use Text\Processing\Model\Text;
use Text\Processing\Model\OriginalWord;
use Text\Processing\Model\TextsTableGateway;
use Zend\Db\Adapter\AdapterInterface;
use Zend\Db\ResultSet\ResultSet;
use Zend\Db\TableGateway\TableGateway;
use Zend\ModuleManager\Feature\ConfigProviderInterface;

class Module implements ConfigProviderInterface
{
    public function getConfig()
    {
        return include __DIR__ . '/../config/module.config.php';
    }

    public function getServiceConfig()
    {
        return [
            'factories' => [
                Model\TextsTable::class => function($container) {
                    $textsTableGateway = $container->get(Model\TextsTableGateway::class);
                    $originalWordsTableGateway = $container->get(Model\OriginalContentTableGateway::class);
                    $translationSectionTableGateway = $container->get(Model\TranslationSectionTableGateway::class);
                    return new Model\TextsTable($textsTableGateway, $originalWordsTableGateway, $translationSectionTableGateway);
                },
                Model\TextsTableGateway::class => function($container) {
                    $dbAdapter = $container->get(AdapterInterface::class);
                    $resultSetPrototype = new ResultSet();
                    $resultSetPrototype->setArrayObjectPrototype(new Model\Text());
                    return new TableGateway('texts', $dbAdapter, null, $resultSetPrototype);
                },
                Model\OriginalContentTableGateway::class => function($container) {
                    $dbAdapter = $container->get(AdapterInterface::class);
                    $resultSetPrototype = new ResultSet();
                    $resultSetPrototype->setArrayObjectPrototype(new Model\OriginalWord());
                    return new TextsTableGateway('texts', $dbAdapter, null, $resultSetPrototype);
                },
                Model\TranslationSectionTableGateway::class => function($container) {
                    $dbAdapter = $container->get(AdapterInterface::class);
                    $resultSetPrototype = new ResultSet();
                    $resultSetPrototype->setArrayObjectPrototype(new Model\Text());
                    return new TableGateway('translat_sect', $dbAdapter, null, $resultSetPrototype);
                },
                Model\PlacesTableGateway::class => function($container) {
                    $dbAdapter = $container->get(AdapterInterface::class);
                    return new TextsTableGateway('places', $dbAdapter);
                },
                Model\referencesTableGateway::class => function($container) {
                    $dbAdapter = $container->get(AdapterInterface::class);
                    return new TextsTableGateway('text_refs', $dbAdapter);
                }
            ],
            'services' => []
        ];
    }

    public function getControllerConfig()
    {
        return [
            'factories' => [
                Controller\TextController::class => function($container) {
                    return new Controller\TextController(
                        $container->get(Model\TextsTable::class),
                        $container->get(Model\PlacesTableGateway::class),
                        $container->get(Model\referencesTableGateway::class)
                    );
                },
            ],
        ];
    }
}