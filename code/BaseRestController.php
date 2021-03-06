<?php

/**
 * Base class for the rest resource controllers.
 */
class BaseRestController extends Controller {

    /**
     * @var int
     */
    protected static $default_limit = 20;

    /**
     * @var int
     */
    protected static $default_offset = 0;

    /**
     * @param SS_HTTPRequest $request
     * @return int
     */
    protected static function offset($request) {
        $offset = (int)$request->getVar('offset');
        if($offset && is_int($offset) && $offset >= 0) {
            return $offset;
        } else {
            return static::$default_offset;
        }
    }

    /**
     * @param SS_HTTPRequest $request
     * @return int
     */
    protected static function limit($request) {
        $limit = (int)$request->getVar('limit');
        if($limit && is_int($limit) && $limit >= 0) {
            return $limit;
        } else {
            return static::$default_limit;
        }
    }
    /**
     * handleAction implementation for rest controllers. This handles the requested action differently then the standard
     * implementation.
     *
     * @param SS_HTTPRequest $request
     * @param string $action
     * @return HTMLText|SS_HTTPResponse
     */
    protected function handleAction($request, $action) {
        foreach($request->latestParams() as $k => $v) {
            if($v || !isset($this->urlParams[$k])) $this->urlParams[$k] = $v;
        }
        // set the action to the request method / for developing we could use an additional parameter to choose another method
        $action = $this->getMethodName($request);
        $this->action = $action;
        $this->requestParams = $request->requestVars();

        $className = get_class($this);
        // create serializer
        $serializer = SerializerFactory::create();
        if(!$this->hasMethod($action)) {
            // method couldn't found on controller
            return new SS_HTTPResponse("Action '$action' isn't available on class $className.", 404);
        }

        try {
            $res = $this->extend('beforeCallActionHandler', $request, $action);
            if ($res) {
                return reset($res);
            }
            // perform action
            $actionRes = $this->$action($request);
            $res = $this->extend('afterCallActionHandler', $request, $action);
            if ($res) {
                return reset($res);
            }

            // set content type
            $this->getResponse()->addHeader('Content-Type', $serializer->contentType());
            return $serializer->serialize($actionRes);
        } catch(RestUserException $ex) {
            // a user exception was caught
            $response = $this->getResponse();
            $response->addHeader('Content-Type', $serializer->contentType());
            $response->setStatusCode("404");

            $body = [
                'message' => $ex->getMessage(),
                'code' => $ex->getCode()
            ];

            $response->setBody($serializer->serialize($body));
            return $response;
        } catch(RestSystemException $ex) {
            // a system exception was caught
            $response = $this->getResponse();
            $response->addHeader('Content-Type', $serializer->contentType());
            $response->setStatusCode("500");

            $body = [
                'message' => $ex->getMessage(),
                'code' => $ex->getCode()
            ];

            if(Director::isDev()) {
                $body = array_merge($body, [
                    'file' => $ex->getFile(),
                    'line' => $ex->getLine(),
                    'trace' => $ex->getTrace()
                ]);
            }

            $response->setBody($serializer->serialize($body));
            return $response;
        } catch(Exception $ex) {
            // an unexpected exception was caught
            $response = $this->getResponse();
            $response->addHeader('Content-Type', $serializer->contentType());
            $response->setStatusCode("500");

            $body = [
                'message' => $ex->getMessage(),
                'code' => $ex->getCode()
            ];

            if(Director::isDev()) {
                $body = array_merge($body, [
                    'file' => $ex->getFile(),
                    'line' => $ex->getLine(),
                    'trace' => $ex->getTrace()
                ]);
            }   

            $response->setBody($serializer->serialize($body));
            return $response;
        }
    }

    /**
     * Returns the http method for this request. If the current environment is a development env, the method can be
     * changed with a `method` variable.
     *
     * @param SS_HTTPRequest $request the current request
     * @return string the used http method as string
     */
    private function getMethodName($request) {
        $method = '';
        if(Director::isDev() && ($varMethod = $request->getVar('method'))) {
            if(in_array(strtoupper($varMethod), array('GET','POST','PUT','DELETE','HEAD'))) {
                $method = $varMethod;
            }
        } else {
            $method = $request->httpMethod();
        }
        return strtolower($method);
    }

    /**
     * Serializes the given data into a json string.
     *
     * @param array $data
     * @return string
     */
    private function serialize($data) {
        return json_encode($data);
    }
}