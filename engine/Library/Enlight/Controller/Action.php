<?php
/**
 * Enlight
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://enlight.de/license
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@shopware.de so we can send you a copy immediately.
 *
 * @category   Enlight
 * @package    Enlight_Controller
 * @copyright  Copyright (c) 2011, shopware AG (http://www.shopware.de)
 * @license    http://enlight.de/license     New BSD License
 * @version    $Id$
 * @author     $Author$
 */

/**
 * Basic class for each Enlight controller action.
 *
 * The Enlight_Controller_Action is the basic class for the specified controllers. It is responsible
 * for the data access. After the dispatcher is dispatched the controller Enlight_Controller_Action
 * takes care, that the right action is executed.
 *
 * @category   Enlight
 * @package    Enlight_Controller
 * @copyright  Copyright (c) 2011, shopware AG (http://www.shopware.de)
 * @license    http://enlight.de/license     New BSD License
 */
abstract class Enlight_Controller_Action extends Enlight_Class implements Enlight_Hook
{
    /**
     * @var Enlight_Controller_Front Contains an instance of the Enlight_Controller_Front.
     */
    protected $front;

    /**
     * @var Enlight_View_Default Contains an instance of the Enlight_View_Default.
     */
    protected $view;

    /**
     * @var Enlight_Controller_Request_Request Contains an instance of the Enlight_Controller_Request_Request.
     * Will be set in the class constructor. Passed to the class init and controller init function.
     * Required for the forward, dispatch and redirect functions.
     */
    protected $request;

    /**
     * @var Enlight_Controller_Response_Response Contains an instance of the Enlight_Controller_Response_Response.
     * Will be set in the class constructor. Passed to the class init and controller init function.
     * Required for the forward, dispatch and redirect functions.
     */
    protected $response;

    /**
     * @var string Contains the name of the controller.
     */
    protected $controller_name;

    protected $container;


    /**
     * The Enlight_Controller_Action class constructor expects an instance of the
     * Enlight_Controller_Request_Request and an instance of the Enlight_Controller_Response_Response.
     * The response and request instance will be passed to the init events of the class and the controller.
     *
     * @param Enlight_Controller_Request_Request $request
     * @param Enlight_Controller_Response_Response $response
     */
    public function __construct(Enlight_Controller_Request_Request $request,
                                Enlight_Controller_Response_Response $response
    )
    {
        $this->setRequest($request)->setResponse($response);

        $this->controller_name = $this->Front()->Dispatcher()->getFullControllerName($this->Request());

        Enlight_Application::Instance()->Events()->notify(
            __CLASS__ . '_Init',
            array('subject' => $this, 'request' => $this->Request(), 'response' => $this->Response())
        );
        Enlight_Application::Instance()->Events()->notify(
            __CLASS__ . '_Init_' . $this->controller_name,
            array('subject' => $this, 'request' => $this->Request(), 'response' => $this->Response())
        );

        parent::__construct();
    }

    /**
     * Pre dispatch method
     */
    public function preDispatch()
    {
    }

    /**
     * Post dispatch method
     */
    public function postDispatch()
    {
    }

    /**
     * Dispatch action method.
     * After the pre dispatch event notified the internal post dispatch event will executed.
     * After the internal post dispatch executed the post dispatch event is notify.
     *
     * @param $action string
     */
    public function dispatch($action)
    {
        $args = array(
            'subject' => $this,
            'request' => $this->Request(),
            'response' => $this->Response()
        );

        $moduleName = ucfirst($this->Request()->getModuleName());

        Enlight_Application::Instance()->Events()->notify(
            __CLASS__ . '_PreDispatch',
            $args
        );

        Enlight_Application::Instance()->Events()->notify(
            __CLASS__ . '_PreDispatch_' . $moduleName,
            $args
        );

        Enlight_Application::Instance()->Events()->notify(
            __CLASS__ . '_PreDispatch_' . $this->controller_name,
            $args
        );

        $this->preDispatch();

        if ($this->Request()->isDispatched() && !$this->Response()->isRedirect()) {
            $action_name = $this->Front()->Dispatcher()->getFullActionName($this->Request());
            if (!$event = Enlight_Application::Instance()->Events()->notifyUntil(
                __CLASS__ . '_' . $action_name,
                array('subject' => $this)
            )
            ) {
                $this->$action();
            }
            $this->postDispatch();
        }

        // Fire "Secure"-PostDispatch-Events only if:
        // - Request is Dispatched
        // - Response in no Exception
        // - View has template
        if ($this->Request()->isDispatched()
            && !$this->Response()->isException()
            && $this->View()->hasTemplate()
        ) {
            Enlight_Application::Instance()->Events()->notify(
                __CLASS__ . '_PostDispatchSecure_' . $this->controller_name,
                $args
            );

            Enlight_Application::Instance()->Events()->notify(
                __CLASS__ . '_PostDispatchSecure_' . $moduleName,
                $args
            );

            Enlight_Application::Instance()->Events()->notify(
                __CLASS__ . '_PostDispatchSecure',
                $args
            );
        }

        // fire non-secure/legacy-PostDispatch-Events
        Enlight_Application::Instance()->Events()->notify(
            __CLASS__ . '_PostDispatch_' . $this->controller_name,
            $args
        );

        Enlight_Application::Instance()->Events()->notify(
            __CLASS__ . '_PostDispatch_' . $moduleName,
            $args
        );

        Enlight_Application::Instance()->Events()->notify(
            __CLASS__ . '_PostDispatch',
            $args
        );
    }

    /**
     * Forward the request to the given controller, module and action with the given parameters.
     *
     * @param string $action
     * @param string $controller
     * @param string $module
     * @param array $params
     */
    public function forward($action, $controller = null, $module = null, array $params = null)
    {
        $request = $this->Request();

        if ($params !== null) {
            $request->setParams($params);
        }
        if ($controller !== null) {
            $request->setControllerName($controller);
            if ($module !== null) {
                $request->setModuleName($module);
            }
        }

        $request->setActionName($action)->setDispatched(false);
    }

    /**
     * Redirect the request. The frontend router will assemble the url.
     *
     * @param string|array $url
     * @param array $options
     */
    public function redirect($url, array $options = array())
    {
        if (is_array($url)) {
            $url = $this->Front()->Router()->assemble($url);
        }
        if (!preg_match('#^(https?|ftp)://#', $url)) {
            if (strpos($url, '/') !== 0) {
                $url = $this->Request()->getBaseUrl() . '/' . $url;
            }
            $uri = $this->Request()->getScheme() . '://' . $this->Request()->getHttpHost();
            $url = $uri . $url;
        }
        $this->Response()->setRedirect($url, empty($options['code']) ? 302 : (int)$options['code']);
    }

    /**
     * Set view instance
     *
     * @param  Enlight_View $view
     * @return Enlight_Controller_Action
     */
    public function setView(Enlight_View $view)
    {
        $this->view = $view;

        return $this;
    }

    /**
     * Set front instance
     *
     * @param  Enlight_Controller_Front $front
     * @return Enlight_Controller_Action
     */
    public function setFront(Enlight_Controller_Front $front = null)
    {
        if ($front === null) {
            $front = Enlight_Application::Instance()->Bootstrap()->getResource('Front');
        }
        $this->front = $front;

        return $this;
    }

    /**
     * Set request instance
     *
     * @param  Enlight_Controller_Request_Request $request
     * @return Enlight_Controller_Action
     */
    public function setRequest(Enlight_Controller_Request_Request $request)
    {
        $this->request = $request;

        return $this;
    }

    /**
     * Set response instance
     *
     * @param  Enlight_Controller_Response_Response $response
     * @return Enlight_Controller_Action
     */
    public function setResponse(Enlight_Controller_Response_Response $response)
    {
        $this->response = $response;

        return $this;
    }

    /**
     * Returns view instance
     *
     * @return Enlight_View_Default
     */
    public function View()
    {
        return $this->view;
    }

    /**
     * Returns front controller
     *
     * @return Enlight_Controller_Front
     */
    public function Front()
    {
        if ($this->front === null) {
            $this->setFront();
        }

        return $this->front;
    }

    /**
     * Returns request instance
     *
     * @return Enlight_Controller_Request_RequestHttp
     */
    public function Request()
    {
        return $this->request;
    }

    /**
     * Returns response instance
     *
     * @return Enlight_Controller_Response_ResponseHttp
     */
    public function Response()
    {
        return $this->response;
    }

    /**
     * Magic caller method
     *
     * @param  string $name
     * @param  array $value
     * @throws Enlight_Controller_Exception
     * @return mixed
     */
    public function __call($name, $value = null)
    {
        if ('Action' == substr($name, -6)) {
            $action = substr($name, 0, strlen($name) - 6);
            throw new Enlight_Controller_Exception(
                'Action "' . $this->controller_name . '_' . $name . '" not found failure',
                Enlight_Controller_Exception::ActionNotFound
            );
        }

        return parent::__call($name, $value);
    }

    /**
     * @return mixed
     */
    public function getContainer()
    {
        return $this->container;
    }

    /**
     * @param mixed $container
     */
    public function setContainer($container)
    {
        $this->container = $container;
    }

    /**
     * @return Enlight_Components_Db_Adapter_Pdo_Mysql
     */
    protected function getDb()
    {
        return $this->getContainer()->get('db');
    }

    /**
     * @return \Shopware\Components\Model\ModelManager
     */
    protected function getModelManager()
    {
        return Enlight()->Models();
    }

    /**
     * @return Enlight_Event_EventManager
     */
    protected function getEventManager()
    {
        return Enlight()->Events();
    }
}
