<?php

namespace AnyContent\Repository;

use Silex\Application;
use AnyContent\Repository\Service\Config;
use AnyContent\Repository\Service\Database;
use AnyContent\Repository\Service\RepositoryManager;
use AnyContent\Repository\ContentManager;
use AnyContent\Repository\Repository;

//use AnyContent\Client\Record;

class SortRecordsTest extends \PHPUnit_Framework_TestCase
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


    public function testSortRecords()
    {

        $this->app['db']->deleteRepository('example', 'example01');
        $this->app['db']->deleteRepository('example', 'example02');
        $this->app['db']->deleteRepository('example', 'example03');

        /**
         * @var $manager ContentManager
         */
        $manager = $this->repository->getContentManager('example01');

        for ($i = 1; $i <= 10; $i++)
        {
            $record               = array();
            $record['properties'] = array( 'name' => 'New Record ' . $i );
            $id                   = $manager->saveRecord($record);
            $this->assertEquals($i, $id);
        }

        //
        // sort records within following tree list
        //
        // - 1
        //   - 2
        //   - 3
        // - 4
        //   - 5
        //     - 7
        //     - 8
        //   - 6
        //     - 9


        $list   = array();
        $list[] = array( 'id' => 1, 'parent_id' => 0 );
        $list[] = array( 'id' => 2, 'parent_id' => 1 );
        $list[] = array( 'id' => 3, 'parent_id' => 1 );
        $list[] = array( 'id' => 4, 'parent_id' => 0 );
        $list[] = array( 'id' => 5, 'parent_id' => 4 );
        $list[] = array( 'id' => 6, 'parent_id' => 4 );
        $list[] = array( 'id' => 7, 'parent_id' => 5 );
        $list[] = array( 'id' => 8, 'parent_id' => 5 );
        $list[] = array( 'id' => 9, 'parent_id' => 6 );

        $manager->sortRecords($list);

        // subset = parent_id,include_parent_id(default=1),depth(default=null)

        $subset  = '1';
        $records = $manager->getRecords('default', 'default','id ASC', null, 1, $subset);
        $this->assertCount(3,$records);

        $subset  = '4';
        $records = $manager->getRecords('default', 'default','id ASC', null, 1, $subset);
        $this->assertCount(6,$records);

        $subset  = '4,0';
        $records = $manager->getRecords('default', 'default','id ASC', null, 1, $subset);
        $this->assertCount(5,$records);

        $subset  = '4,0,1';
        $records = $manager->getRecords('default', 'default','id ASC', null, 1, $subset);
        $this->assertCount(2,$records);

        $subset  = '5,0';
        $records = $manager->getRecords('default', 'default','id ASC', null, 1, $subset);
        $this->assertCount(2,$records);

        $subset  = '5,1';
        $records = $manager->getRecords('default', 'default','id ASC', null, 1, $subset);
        $this->assertCount(3,$records);

        $subset  = '6,0';
        $records = $manager->getRecords('default', 'default','id ASC', null, 1, $subset);
        $this->assertCount(1,$records);

        $subset  = '6,1';
        $records = $manager->getRecords('default', 'default','id ASC', null, 1, $subset);
        $this->assertCount(2,$records);

        $subset  = '7,-1';
        $records = $manager->getRecords('default', 'default','id ASC', null, 1, $subset);
        $this->assertCount(3,$records);

        $subset  = '8,-1';
        $records = $manager->getRecords('default', 'default','id ASC', null, 1, $subset);
        $this->assertCount(3,$records);

        $subset  = '9,-1';
        $records = $manager->getRecords('default', 'default','id ASC', null, 1, $subset);
        $this->assertCount(3,$records);

        $subset  = '3,-1';
        $records = $manager->getRecords('default', 'default','id ASC', null, 1, $subset);
        $this->assertCount(2,$records);

        $subset  = '4,-1';
        $records = $manager->getRecords('default', 'default','id ASC', null, 1, $subset);
        $this->assertCount(1,$records);
    }

}