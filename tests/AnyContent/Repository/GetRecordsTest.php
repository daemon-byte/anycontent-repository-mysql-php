<?php

namespace AnyContent\Repository;

use Silex\Application;
use AnyContent\Repository\Service\Config;
use AnyContent\Repository\Service\Database;
use AnyContent\Repository\Service\RepositoryManager;
use AnyContent\Repository\Service\ContentManager;
use AnyContent\Repository\Repository;

//use AnyContent\Client\Record;

class GetRecordsTest extends \PHPUnit_Framework_TestCase
{

    protected $app;

    /** @var $repository Repository */
    protected $repository;


    public function setUp()
    {

        $app           = new Application();
        $app['config'] = new Config($app);
        $app['repos']  = new RepositoryManager($app);
        $app['db']     = new Database($app);

        $this->app = $app;

        $this->repository = $this->app['repos']->get('example');

    }


    public function testGetRecords()
    {

        $this->app['db']->deleteRepository('example', 'example01');
        $this->app['db']->deleteRepository('example', 'example02');
        $this->app['db']->deleteRepository('example', 'example03');

        /**
         * @var ContentManager
         */
        $manager = $this->repository->getContentManager('example01');

        for ($i = 1; $i <= 5; $i++)
        {
            $record               = array();
            $record['properties'] = array( 'name' => 'New Record ' . $i );
            $id                   = $manager->saveRecord($record);
            $this->assertEquals($i, $id);
        }


        $records = $manager->getRecords();

        var_dump($records);
    }



}