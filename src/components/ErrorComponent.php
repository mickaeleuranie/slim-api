<?php

namespace api\components;

use api\Api;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

class ErrorComponent
{
    /**
     * @param ServerRequestInterface $request
     * @return string
     */
    public static function getRequestDetails(ServerRequestInterface $request)
    {
        // remove password from get and post params
        $get = $request->getQueryParams();
        $post = $request->getParsedBody();
        if (!empty($get['password'])) {
            $get['password'] = '****';
        }
        if (!empty($post['password'])) {
            $post['password'] = '****';
        }
        // remove GMAIL password from $_REQUEST
        $requestParams = $request->getServerParams();
        if (isset($requestParams['GMAIL_PASSWORD'])) {
            unset($requestParams['GMAIL_PASSWORD']);
        }
        $details = '
            <br><br>
            ////////////////////////////////////////<br><br>
            URI details :<br>' . '<pre>' . print_r($request->getUri(), true) . '</pre>
            <br><br>
            ////////////////////////////////////////<br><br>
            GET details :<br>' . '<pre>' . print_r($get, true) . '</pre>
            POST details :<br>' . '<pre>' . print_r($post, true) . '</pre>
            <br><br>
            ////////////////////////////////////////<br><br>
            REQUEST details :<br>' . '<pre>' . print_r($requestParams, true) . '</pre>
        ';
        return $details;
    }

    /**
     * Get model error after a failed save()
     * @param mixed $model
     * @return string
     */
    public static function getModelErrorMessage($model): string
    {
        return implode(', ', $model->errors());
    }

    /**
     * Handle error / exception
     * @param mixed Exception | Throwable $error
     * @param string $error Exception
     */
    public static function handle(ServerRequestInterface $request, $error, $category = 'api')
    {
        $message = '';
        $data = [];

        // add user details
        $message .= 'USER details :<br>
            IP: ' . $request->getAttribute('ip_address') . '<br>
            TOKEN: ' . $request->getQueryParam('access_token') . '<br>
        ';

        if (!empty(Api::$user)) {
            $message .= 'USER details :<br>
                ID: ' . Api::$user->id . '<br>
                USERNAME: ' . Api::$user->username . '<br>
                EMAIL: ' . Api::$user->getEmail() . '<br>
            ';
            $data['user_id'] = Api::$user->id;
        }

        // add query details
        $message .= static::getRequestDetails($request);

        // add trace
        $trace = $error->getTrace();
        if ('production' !== getenv('ENV')) {
            $data['trace'] = array_shift($trace);
        }

        // send error mail
        // only if not 404 (too much useless mails)
        $code = !empty($error->getCode()) ? $error->getCode() : 500;
        if (404 !== $code) {
            // add stack trace to email
            if (!empty($error->getTrace())) {
                $message .= json_encode($error->getTrace()[0], JSON_PRETTY_PRINT) . '<br>';
            }
            $to = getenv('DEBUG_EMAIL_RECIPIENT');
            $subject = '[API][ERROR][' . $code . '] - ' . $error->getMessage();
            EmailComponent::send($to, $subject, $message, [], getenv('DEBUG_EMAIL_RECIPIENT'), 'API Debug');
        }

        // log in database
        // format data
        $now = new \DateTime;
        $callParams = explode('/', ltrim($request->getUri()->getPath(), '/'));

        $version = !empty($callParams[0]) ? $callParams[0] : null;
        $controller = !empty($callParams[1]) ? $callParams[1] : null;
        $action = !empty($callParams[2]) ? $callParams[2] : null;

        $data = array_merge($data, [
            'level'      => 'error',
            'code'       => $code,
            'category'   => $category,
            'message'    => $error->getMessage(),
            'details'    => str_replace('<br>', "\n", static::getRequestDetails($request)),
            'ip'         => $request->getAttribute('ip_address'),
            'date'       => $now->format('Y-m-d H:i:s'),
            'referer'    => $request->getUri()->getPath(),
            'controller' => $controller,
            'action'     => $action,
        ]);

        Api::$dblogger->error($data);
    }
}
