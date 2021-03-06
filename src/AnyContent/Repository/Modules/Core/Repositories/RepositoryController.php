<?php

namespace AnyContent\Repository\Modules\Core\Repositories;

use AnyContent\Repository\Modules\Events\ContentRecordEvent;
use AnyContent\Repository\Modules\Events\RepositoryEvents;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use AnyContent\Repository\Modules\Core\Application\Application;

use AnyContent\Repository\Modules\Core\Application\BaseController;

class RepositoryController extends BaseController
{

    public static function index(Application $app, Request $request, $repositoryName, $workspace = 'default', $language = 'default', $timeshift = 0)
    {

        if ($request->query->has('timeshift'))
        {
            $timeshift = (int)$request->get('timeshift');
        }

        if ($request->query->has('language'))
        {
            $language = $request->get('language');
        }

        /** @var $repository Repository */
        $repository = $app['repos']->get($repositoryName);
        if ($repository)
        {

            $contentTypesList = $repository->getContentTypesList();

            /** @var ContentTypeInfo $contentTypeInfo */
            foreach ($contentTypesList as $contentTypeName => $contentTypeInfo)
            {
                $manager = $repository->getContentManager($contentTypeName);
                if ($manager)
                {
                    $info = $manager->countRecords($workspace, null, $language, $timeshift);
                    $contentTypeInfo->setCount($info['count']);
                    $contentTypeInfo->setLastChangeContent($info['lastchange']);
                }
            }

            $configTypesList = $repository->getConfigTypesList();
            $manager         = $repository->getConfigManager();

            /** @var ConfigTypeInfo $configTypeInfo */
            foreach ($configTypesList as $configTypeName => $configTypeInfo)
            {
                $result = $manager->getConfig($configTypeName, $workspace, $language, $timeshift);
                if ($result)
                {
                    $configTypeInfo->setLastchangeConfig($result['record']['info']['revision_timestamp']);
                }
            }

            $result = array( 'content' => $contentTypesList, 'config' => $configTypesList, 'files' => true, 'admin' => false );

            return $app->json($result);
        }

        return self::notFoundError($app, self::UNKNOWN_REPOSITORY, $repositoryName);
    }


    public static function cmdl(Application $app, Request $request, $repositoryName, $contentTypeName, $locale = null)
    {

        /** @var $repository Repository */
        $repository = $app['repos']->get($repositoryName);
        if ($repository)
        {

            $cmdl = $repository->getCMDL($contentTypeName);
            if ($cmdl)
            {
                return $app->json(array( 'cmdl' => $cmdl ));
            }

            return self::notFoundError($app, self::UNKNOWN_CONTENTTYPE, $repositoryName, $contentTypeName);
        }

        return self::notFoundError($app, self::UNKNOWN_REPOSITORY, $repositoryName);
    }


    public static function postContentTypeCMDL(Application $app, Request $request, $repositoryName, $contentTypeName, $locale = null)
    {
        if ($request->request->has('cmdl'))
        {
            $cmdl = $request->request->get('cmdl');

            /** @var $manager RepositoryManager */
            $manager = $app['repos'];

            if ($manager->saveContentTypeCMDL($repositoryName, $contentTypeName, $cmdl, $locale))
            {
                return $app->json(true);
            }

            return self::badRequest(self::BAD_REQUEST);
        }

        return self::badRequest($app, self::MISSING_MANDATORY_PARAMETER, 'cmdl');
    }


    public static function deleteContentType(Application $app, Request $request, $repositoryName, $contentTypeName)
    {
        /** @var $manager RepositoryManager */
        $manager = $app['repos'];

        if ($manager->discardContentType($repositoryName, $contentTypeName))
        {
            return $app->json(true);
        }

        return $app->json(false);
    }


    public static function postConfigTypeCMDL(Application $app, Request $request, $repositoryName, $configTypeName, $locale = null)
    {
        if ($request->request->has('cmdl'))
        {
            $cmdl = $request->request->get('cmdl');

            /** @var $manager RepositoryManager */
            $manager = $app['repos'];

            if ($manager->saveConfigTypeCMDL($repositoryName, $configTypeName, $cmdl, $locale))
            {
                return $app->json(true);
            }

            return self::badRequest(self::BAD_REQUEST);
        }

        return self::badRequest($app, self::MISSING_MANDATORY_PARAMETER, 'cmdl');
    }


    public static function deleteConfigType(Application $app, Request $request, $repositoryName, $configTypeName)
    {
        /** @var $manager RepositoryManager */
        $manager = $app['repos'];

        if ($manager->discardConfigType($repositoryName, $configTypeName))
        {
            return $app->json(true);
        }

        return $app->json(false);
    }


    public static function getInfoShortCut(Application $app, Request $request, $repositoryName)
    {
        $url = '/1/' . $repositoryName . '/info';

        return new RedirectResponse($url, 303);
    }


    public static function welcome(Application $app)
    {
        $result = 'Welcome to AnyContent Repository Server. Please specify desired repository.';

        return $app->json($result);
    }


    public static function welcomeShortCut(Application $app)
    {
        $url = '/1';

        return new RedirectResponse($url, 303);
    }

}