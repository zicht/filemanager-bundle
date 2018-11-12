<?php
/**
 * @copyright Zicht Online <http://www.zicht.nl>
 */

namespace Zicht\Bundle\FileManagerBundle\Controller;

use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Provides some methods to communicate with Imagine e.g. Clear cache for a file
 *
 * @package Zicht\Bundle\FileManagerBundle\Controller
 */
class ImagineController extends Controller
{
    /**
     * Remove thumbnail for given path
     *
     * @param Request $request
     * @return JsonResponse
     *
     * @Route("/clear-thumbnail")
     */
    public function clearThumbnailAction(Request $request)
    {
        $path = $request->get('path');
        $filter = $request->get('filter');
        $response = [
            'error' => false,
            'success' => false
        ];

        if ($path && $filter) {
            /** @var CacheManager $cacheManager */
            $cacheManager = $this->get('liip_imagine.cache.manager');
            // Filter is purposely left out on the remove call, it should remove all the caches for the current given file.
            $cacheManager->remove($path);
            $cacheManager->getBrowserPath($path, $filter);
            $response['success'] = true;
            $response['url']  = $cacheManager->generateUrl($path, $filter);
        } else {
            $response['error'] = 'No "path" or "filter" provided';
        }

        return new JsonResponse($response);
    }
}
