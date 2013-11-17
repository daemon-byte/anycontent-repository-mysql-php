<?php

namespace AnyContent\Repository\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Silex\Application;

use AnyContent\Repository\Controller\BaseController;

use AnyContent\Repository\Repository;
use AnyContent\Repository\RepositoryException;

use CMDL\Util;

class ContentController extends BaseController
{

    public static function post(Application $app, Request $request, $repositoryName, $contentTypeName, $workspace = 'default', $clippingName = 'default', $language = 'none')
    {

        $record = false;

        if ($request->request->has('record'))
        {
            $record = $request->get('record');
            $record = json_decode($record, true);
        }

        if ($record)
        {

            /** @var $repository Repository */
            $repository = $app['repos']->get($repositoryName);
            if ($repository)
            {
                $manager = $repository->getContentManager($contentTypeName);

                try
                {
                    $id = $manager->saveRecord($record, $workspace, $clippingName, $language);
                }
                catch (RepositoryException $e)
                {
                    return self::badRequest($app, 'Bad Request - ' . $e->getMessage());
                }

                return $app->json($id);
            }

            return self::notFoundError($app, self::UNKNOWN_REPOSITORY, $repositoryName);
        }

        return self::badRequest($app);
        //return $app->json('true');
    }


    public static function getOne(Application $app, Request $request, $repositoryName, $contentTypeName, $id, $workspace = 'default', $clippingName = 'default', $language = 'none', $timeshift = 0)
    {

        /** @var $repository Repository */
        $repository = $app['repos']->get($repositoryName);
        if ($repository)
        {

            $manager = $repository->getContentManager($contentTypeName);

            if ($manager)
            {
                try
                {

                    if ($request->query->has('timeshift'))
                    {
                        $timeshift = (int)$request->get('timeshift');
                    }

                    $record = $manager->getRecord($id, $clippingName, $workspace, $language, $timeshift);

                    return $app->json($record);
                }
                catch (RepositoryException $e)
                {
                    return self::notFoundError($app, self::RECORD_NOT_FOUND, $repositoryName, $contentTypeName, $id);
                }
            }
            else
            {
                return self::notFoundError($app, self::UNKNOWN_CONTENTTYPE, $repositoryName, $contentTypeName);
            }

        }

        return self::notFoundError($app, self::UNKNOWN_REPOSITORY, $repositoryName);
    }


    public static function getMany(Application $app, Request $request, $repositoryName, $contentTypeName, $workspace = 'default', $clippingName = 'default', $language = 'none', $timeshift = 0, $orderBy = 'id ASC', $limit = null, $page = 1, $subset = null, $filter = null)
    {
        /** @var $repository Repository */
        $repository = $app['repos']->get($repositoryName);
        if ($repository)
        {
            $manager = $repository->getContentManager($contentTypeName);

            if ($manager)
            {
                if ($request->query->has('timeshift'))
                {
                    $timeshift = (int)$request->get('timeshift');
                }

                if ($request->query->has('order'))
                {

                    if ($request->get('order') == 'property')
                    {
                        $properties = explode(',', $request->get('properties'));

                        $orderBy = '';
                        foreach ($properties as $property)
                        {

                            if ($manager->hasProperty(Util::generateValidIdentifier($property), $clippingName))
                            {

                                if (substr(trim($property), -1) == '-')
                                {
                                    $orderBy .= 'property_' . Util::generateValidIdentifier($property) . ' DESC, ';
                                }
                                else
                                {
                                    $orderBy .= 'property_' . Util::generateValidIdentifier($property) . ' ASC, ';
                                }
                            }
                            else
                            {
                                return self::badRequest($app, self::UNKNOWN_PROPERTY, $repositoryName, $contentTypeName, $clippingName, $property);
                            }
                        }
                        $orderBy .= ' id ASC';

                    }
                    else
                    {

                        switch ($request->get('order'))
                        {
                            case
                                'id':
                                $orderBy = 'id ASC';

                                break;
                            case
                                'id-':
                                $orderBy = 'id DESC';

                                break;
                            case
                                'name':
                                $orderBy = 'property_name ASC, id ASC';
                                break;
                            case
                                'name-':
                                $orderBy = 'property_name DESC, id ASC';
                                break;
                            case
                                'pos':
                                $orderBy = 'position ASC, id ASC';
                                break;
                            case
                                'pos-':
                                $orderBy = 'position DESC, id ASC';
                                break;
                            case
                                'change':
                                $orderBy = 'lastchange_timestamp ASC, id ASC';
                                break;
                            case
                                'change-':
                                $orderBy = 'lastchange_timestamp DESC, id DESC';
                                break;
                            case
                                'creation':
                                $orderBy = 'creation_timestamp ASC, id ASC';
                                break;
                            case
                                'creation-':
                                $orderBy = 'creation_timestamp DESC, id DESC';
                                break;
                            case
                                'status':
                                $orderBy = 'property_status ASC, id ASC';
                                break;
                            case
                                'status-':
                                $orderBy = 'property_status ASC, id ASC';
                                break;

                        }
                    }
                }

                if ($request->query->has('limit'))
                {
                    $limit = (int)$request->get('limit');

                    if ($request->query->has('page'))
                    {
                        $page = (int)$request->get('page');
                    }
                }

                $records = $manager->getRecords($clippingName, $workspace, $language, $timeshift, $orderBy, $limit, $page, $subset, $filter);

                return $app->json($records);
            }
            else
            {
                return self::notFoundError($app, self::UNKNOWN_CONTENTTYPE, $repositoryName, $contentTypeName);
            }

        }

        return self::notFoundError($app, self::UNKNOWN_REPOSITORY, $repositoryName);
    }

}