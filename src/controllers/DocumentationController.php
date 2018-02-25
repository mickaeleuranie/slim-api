<?php
/**
 * Controller for displaying documentation
 *
 * This file is part of the Slim API package
 *
 * @author Mickaël Euranie <contact@mickaeleuranie.com>
 */

namespace api\controllers;

use api\Api;
use api\exceptions\BadRequestException;
use api\exceptions\ForbiddenException;
use api\exceptions\NotFoundException;
use api\models\UserDocumentationToken;

class DocumentationController extends ApiController
{
    public function accessRules()
    {
        return [
            [
                'allow' => true,
                'actions' => [
                    'index',
                    'view',
                ],
                'roles' => ['?'],
            ],
        ];
    }

    /**
     * Documentation index
     */
    public function index($request, $response, $args)
    {
        return $this->view($request, $response, $args);
        return $this->container->twig->render(
            $response,
            'index.phtml',
            [
                'version' => 1
            ]
        );
    }

    /**
     * View documentation
     */
    public function view($request, $response, $args)
    {
        $data = [];

        // uncomment to use protected documentation
        // if (empty($args['token'])) {
        //     throw new ForbiddenException();
        // }

        // $authorizedToken = UserDocumentationToken::where(['documentation_token' => $args['token']])->first();
        // if (empty($authorizedToken)) {
        //     throw new ForbiddenException();
        // }

        // // get token if user is related
        // if (!empty($authorizedToken->user->token)) {
        //     $data['accessToken'] = $authorizedToken->user->token->access_token;
        // }

        // // add name
        // $data['name'] = $authorizedToken->user->familyProfile->name;

        // // add API key
        // if (!empty($authorizedToken->user->apiKey) && !$authorizedToken->user->apiKey->isEmpty()) {
        //     $data['apiKey'] = $authorizedToken->user->apiKey[0]->key;
        // }

        return $this->container->view->render('docs/index', $data);
    }
}
