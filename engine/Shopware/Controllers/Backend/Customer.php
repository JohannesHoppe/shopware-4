<?php
/**
 * Shopware 4
 * Copyright © shopware AG
 *
 * According to our dual licensing model, this program can be used either
 * under the terms of the GNU Affero General Public License, version 3,
 * or under a proprietary license.
 *
 * The texts of the GNU Affero General Public License with an additional
 * permission and of our proprietary license can be found at and
 * in the LICENSE file you have received along with this program.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * "Shopware" is a registered trademark of shopware AG.
 * The licensing of the program under the AGPLv3 does not imply a
 * trademark license. Therefore any rights, title and interest in
 * our trademarks remain entirely with us.
 */

use Shopware\Models\Customer\Customer as Customer,
    Shopware\Models\Customer\Billing as Billing,
    Shopware\Models\Customer\Shipping as Shipping,
    Shopware\Models\Customer\Debit as Debit,
    Shopware\Models\Attribute\CustomerBilling as BillingAttributes,
    Shopware\Models\Attribute\CustomerShipping as ShippingAttributes,
    Shopware\Models\Customer\PaymentData;

/**
 * Backend Controller for the customer backend module.
 * Displays all customers in an Ext.grid.Panel and allows to delete,
 * add and edit customers. On the detail page the customer data displayed
 * and a list of all done orders shown.
 */
class Shopware_Controllers_Backend_Customer extends Shopware_Controllers_Backend_ExtJs
{
    /**
     * Customer repository. Declared for an fast access to the customer repository.
     * Initialed in the init() function.
     *
     * @var \Shopware\Models\Customer\Repository
     * @access private
     */
    public static $repository = null;

    /**
     * Contains the shopware model manager
     *
     * @var \Shopware\Components\Model\ModelManager
     */
    public static $manager = null;

    /**
     * @var \Shopware\Components\Model\ModelRepository
     */
    protected $groupRepository = null;


    /**
     * @var \Shopware\Models\Shop\Repository
     */
    protected $shopRepository = null;

    /**
     * Helper function to get access to the shop repository.
     * @return \Shopware\Models\Shop\Repository
     */
    private function getShopRepository()
    {
        if ($this->shopRepository === null) {
            $this->shopRepository = Shopware()->Models()->getRepository('Shopware\Models\Shop\Shop');
        }
        return $this->shopRepository;
    }

    /**
     * Helper function to get access to the group repository.
     * @return \Shopware\Components\Model\ModelRepository
     */
    private function getGroupRepository()
    {
        if ($this->groupRepository === null) {
            $this->groupRepository = Shopware()->Models()->getRepository('Shopware\Models\Customer\Group');
        }
        return $this->groupRepository;
    }

    /**
     * Returns the shopware model manager
     *
     * @return Shopware\Components\Model\ModelManager
     */
    protected function getManager()
    {
        if (self::$manager === null) {
            self::$manager = Shopware()->Models();
        }
        return self::$manager;

    }

    /**
     * Helper function to get access on the static declared repository
     *
     * @return null|Shopware\Models\Customer\Repository
     */
    protected function getRepository()
    {
        if (self::$repository === null) {
            self::$repository = Shopware()->Models()->getRepository('Shopware\Models\Customer\Customer');
        }
        return self::$repository;
    }



    /**
     * Registers the different acl permission for the different controller actions.
     *
     * @return void
     */
    protected function initAcl()
    {
        $this->addAclPermission('getList','read', 'no_list_rights', 'You do not have sufficient rights to view the list of customers.');
        $this->addAclPermission('getDetail', 'detail', 'no_detail_rights', 'You do not have sufficient rights to view the customer detail page.');
        $this->addAclPermission('getOrders', 'read', 'no_order_rights', 'You do not have sufficient rights to view customer orders.');
        $this->addAclPermission('getOrderChart', 'read', 'no_order_rights', 'You do not have sufficient rights to view customer orders.');
        $this->addAclPermission('delete', 'delete', 'no_delete_rights', 'You do not have sufficient rights to delete a customers.');
    }

    /**
     * Disable template engine for all actions
     *
     * @codeCoverageIgnore
     * @return void
     */
    public function preDispatch()
    {
        if (!in_array($this->Request()->getActionName(), array('index', 'load', 'validateEmail'))) {
            $this->Front()->Plugins()->Json()->setRenderer(true);
        }
    }


    /**
     * @var \Shopware\Models\Order\Repository
     */
    protected $orderRepository = null;

    /**
     * @var \Shopware\Models\Payment\Repository
     */
    protected $paymentRepository = null;

    /**
     * @var \Shopware\Models\Dispatch\Repository
     */
    protected $dispatchRepository = null;


    /**
     * @var \Shopware\Models\Country\Repository
     */
    protected $countryRepository = null;

    /**
     * Helper function to get access to the country repository.
     * @return \Shopware\Models\Country\Repository
     */
    private function getCountryRepository()
    {
        if ($this->countryRepository === null) {
            $this->countryRepository = Shopware()->Models()->getRepository('Shopware\Models\Country\Country');
        }
        return $this->countryRepository;
    }
    /**
     * Helper function to get access to the order repository.
     * @return \Shopware\Models\Order\Repository
     */
    private function getOrderRepository()
    {
        if ($this->orderRepository === null) {
            $this->orderRepository = Shopware()->Models()->getRepository('Shopware\Models\Order\Order');
        }
        return $this->orderRepository;
    }

    /**
     * Helper function to get access to the payment repository.
     * @return \Shopware\Models\Payment\Repository
     */
    private function getPaymentRepository()
    {
        if ($this->paymentRepository === null) {
            $this->paymentRepository = Shopware()->Models()->getRepository('Shopware\Models\Payment\Payment');
        }
        return $this->paymentRepository;
    }

    /**
     * Helper function to get access to the dispatch repository.
     * @return \Shopware\Models\Dispatch\Repository
     */
    private function getDispatchRepository()
    {
        if ($this->dispatchRepository === null) {
            $this->dispatchRepository = Shopware()->Models()->getRepository('Shopware\Models\Dispatch\Dispatch');
        }
        return $this->dispatchRepository;
    }


    public function loadStoresAction()
    {
        $orderStatus = $this->getOrderRepository()->getOrderStatusQuery()->getArrayResult();
        $paymentStatus = $this->getOrderRepository()->getPaymentStatusQuery()->getArrayResult();
        $payment = $this->getPaymentRepository()->getPaymentsQuery()->getArrayResult();
        $dispatch = $this->getDispatchRepository()->getDispatchesQuery()->getArrayResult();
        $shop = $this->getShopRepository()->getBaseListQuery()->getArrayResult();
        $country = $this->getCountryRepository()->getCountriesQuery()->getArrayResult();
        $customerGroups = $this->getRepository()->getCustomerGroupsQuery()->getArrayResult();

        $this->View()->assign(array(
            'success' => true,
            'data' => array(
                'orderStatus' => $orderStatus,
                'paymentStatus' => $paymentStatus,
                'payment' => $payment,
                'dispatch' => $dispatch,
                'shop' => $shop,
                'country' => $country,
                'customerGroup' => $customerGroups
            )
        ));
    }



    /**
     * Event listener method which fires when the customer list store is loaded. Returns an array of customer data
     * which displayed in an Ext.grid.Panel. Grants by the limit and start parameter a paging
     * for the customer list data. The filter parameter allows the user a full text search
     * over the displayed fields.
     *
     * @return void
     */
    public function getListAction()
    {
        try {
            //read store parameter to filter and paginate the data.
            $limit = $this->Request()->getParam('limit', 20);
            $offset = $this->Request()->getParam('start', 0);
            $sort = $this->Request()->getParam('sort', array(array('property' => 'customer.id', 'direction' => 'DESC')));
            $filter = $this->Request()->getParam('filter', null);
            $filter = $filter[0]['value'];

            $customerGroup = $this->Request()->getParam('customerGroup', null);

            //get access on the customer repository
            $query = $this->getRepository()->getListQuery($filter, $customerGroup, $sort, $limit, $offset);

            //returns the customer data
            $customers = $query->getArrayResult();

            //returns the total count of the query because getQueryCount and the paginator are to slow with huge data
            $countQuery = $this->getRepository()->getBackendListCountedBuilder($filter, $customerGroup)->getQuery();
            $countResult = $countQuery->getOneOrNullResult(Doctrine\ORM\AbstractQuery::HYDRATE_ARRAY);

            $this->View()->assign(array('success' => true, 'data' => $customers, 'total' => $countResult["customerCount"]));
        } catch (\Doctrine\ORM\ORMException $e) {
            $this->View()->assign(array('success' => false, 'data' => array(), 'message' => $e->getMessage()));
        }
    }

    /**
     * Event listener method which fires when the customer detail
     * store is loaded. Returns an array with all data about one customer.
     * Expects an customer id as parameter to read the detail data
     * only for one customer.
     *
     * @return void
     */
    public function getDetailAction()
    {
        try {
            $customerId = $this->Request()->getParam('customerID');
            if ($customerId === null || $customerId === 0) {
                $this->View()->assign(array('success' => false, 'message' => 'No customer id passed'));
                return;
            }

            $data = $this->getCustomer($customerId);

            $this->View()->assign(array('success' => true, 'data' => $data, 'total' => 1));

        } catch (\Doctrine\ORM\ORMException $e) {
            $this->View()->assign(array('success' => false, 'data' => array(), 'message' => $e->getMessage()));
        }
    }

    /**
     * Event listener method which fires when the customer order store is loaded.
     * Returns an array of all customer orders to display them in an Ext.grid.Panel.
     * Grants by the limit and start parameter a paging for the customer order data.
     * The filter parameter allows the user a full text search
     * over the displayed fields.
     *
     * @return void.
     */
    public function getOrdersAction()
    {
        try {
            if (!$this->_isAllowed('read', 'order')) {
                /** @var $namespace Enlight_Components_Snippet_Namespace */
                $namespace = Shopware()->Snippets()->getNamespace('backend/customer');

                $this->View()->assign(array(
                    'success' => false,
                    'data' => $this->Request()->getParams(),
                    'message' => $namespace->get('no_order_rights', 'You do not have sufficient rights to view customer orders.'))
                );
                return;
            }

            $customerId = $this->Request()->getParam('customerID');
            if ($customerId === null || $customerId === 0) {
                $this->View()->assign(array('success' => false, 'message' => 'No customer id passed'));
                return;
            }

            $defaultSort = array('0' => array('property' => 'orderTime', 'direction' => 'DESC'));

            $limit = $this->Request()->getParam('limit', 20);
            $offset = $this->Request()->getParam('start', 0);
            $sort = $this->Request()->getParam('sort', $defaultSort);
            $filter = $this->Request()->getParam('filter', null);
            $filter = $filter[0]['value'];

            //get access on the customer getRepository()
            $query = $this->getRepository()->getOrdersQuery($customerId, $filter, $sort, $limit, $offset);

            //returns the total count of the query
            $totalResult = $this->getManager()->getQueryCount($query);

            //returns the customer data
            $orders = $query->getArrayResult();

            $this->View()->assign(array('success' => true, 'data' => $orders, 'total' => $totalResult));
        } catch (\Doctrine\ORM\ORMException $e) {
            $this->View()->assign(array('success' => false, 'data' => array(), 'message' => $e->getMessage()));
        }
    }

    /**
     * Event listener method which fires when the detail page of a customer is loaded.
     * Returns an array of grouped order data to display them in a line chart.
     * @return array Contains all customer orders group by year-month
     */
    public function getOrderChartAction()
    {
        try {
            if (!$this->_isAllowed('read', 'order')) {
                /** @var $namespace Enlight_Components_Snippet_Namespace */
                $namespace = Shopware()->Snippets()->getNamespace('backend/customer');

                $this->View()->assign(array(
                    'success' => false,
                    'data' => $this->Request()->getParams(),
                    'message' => $namespace->get('no_order_rights', 'You do not have sufficient rights to view customer orders.'))
                );
                return;
            }

            //customer id passed?
            $customerId = $this->Request()->getParam('customerID');
            if ($customerId === null || $customerId === 0) {
                $this->View()->assign(array('success' => false, 'message' => 'No customer id passed'));
                return;
            }
            $orders = $this->getChartData($customerId);

            $this->View()->assign(array('success' => true, 'data' => $orders));
        } catch (Exception $e) {
            $this->View()->assign(array('success' => true, 'data' => array(), 'message' => $e->getMessage()));
        }
    }

    /**
     * Select the customer orders grouped by year and month. Can be filtered over the fromDate and toDate parameter.
     * If the date of the first founded order not equals with the fromDate, an empty row will be prepend.
     * If the date of the last founded order  not equals with the fromDate, an empty row will be append.
     * @param $customerId
     * @return array
     */
    private function getChartData($customerId)
    {
        //if a from date passed, format it over the \DateTime object. Otherwise create a new date with today - 1 year
        $fromDate = $this->Request()->getParam('fromDate');
        if (empty($fromDate)) {
            $fromDate = new \DateTime();
            $fromDate->setDate($fromDate->format('Y') - 1, $fromDate->format('m'), $fromDate->format('d'));
        } else {
            $fromDate = new \DateTime($fromDate);
        }
        $fromDateFilter = $fromDate->format('Y-m-d');

        //if a to date passed, format it over the \DateTime object. Otherwise create a new date with today
        $toDate = $this->Request()->getParam('toDate');
        if (empty($toDate)) {
            $toDate = new \DateTime();
        } else {
            $toDate = new \DateTime($toDate);
        }
        $toDateFilter = $toDate->format('Y-m-d');

        $sql= "
            SELECT
                SUM(invoice_amount) as amount,
                DATE_FORMAT(ordertime, '%Y-%m-01') as `date`
            FROM s_order
            WHERE userID = ?
            AND s_order.status NOT IN (-1, 4)
            AND ordertime >= ?
            AND ordertime <= ?
            GROUP by YEAR(ordertime), MONTH(ordertime)
        ";

        //select the orders from the database
        $orders = Shopware()->Db()->fetchAll($sql, array($customerId,$fromDateFilter,$toDateFilter));

        if (!empty($orders)) {
            $first = new \DateTime($orders[0]['date']);
            $last = new \DateTime($orders[count($orders)-1]['date']);

            //to display the whole time range the user inserted, check if the date of the first order equals the fromDate parameter
            if ($fromDate->format('Y-m') !== $first->format('Y-m')) {
                //create a new dummy order with amount 0 and the date the user inserted.
                $fromDate->setDate($fromDate->format('Y'), $fromDate->format('m'), 1);
                $emptyOrder = array('amount' => '0.00', 'date' => $fromDate->format('Y-m-d'));
                array_unshift($orders,$emptyOrder);
            }

            //to display the whole time range the user inserted, check if the date of the last order equals the toDate parameter
            if ($toDate->format('Y-m') !== $last->format('Y-m')) {
                $toDate->setDate($toDate->format('Y'), $toDate->format('m'), 1);
                $orders[] = array('amount' => '0.00', 'date' => $toDate->format('Y-m-d'));
            }
        }
        return $orders;
    }

    /**
     * Saves a single customer. If no customer id passed,
     * the save function creates a new customer model and persist
     * it by the shopware model manager.
     * The sub models billing, shipping and debit will be filled
     * by the passed parameter arrays billing, shipping and debit.
     */
    public function saveAction()
    {
        $id = $this->Request()->getParam('id', null);
        $paymentId = $this->Request()->getParam('paymentId', null);

        /** @var $namespace Enlight_Components_Snippet_Namespace */
        $namespace = Shopware()->Snippets()->getNamespace('backend/customer');

        //customer id passed? If this is the case the customer was edited
        if (!empty($id)) {
            //check if the user has the rights to update an existing customer
            if (!$this->_isAllowed('update', 'customer')) {
                $this->View()->assign(array(
                    'success' => false,
                    'data' => $this->Request()->getParams(),
                    'message' => $namespace->get('no_edit_rights', 'You do not have sufficient rights to edit a customer.')
                ));
                return;
            }

            $customer = $this->getRepository()->find($id);
            $shipping = $customer->getShipping();
            $billing = $customer->getBilling();
            $debit = $customer->getDebit();

            $paymentData = Shopware()->Models()->getRepository('Shopware\Models\Customer\PaymentData')->findOneBy(
                array('customer' => $customer, 'paymentMean' => $paymentId)
            );
        } else {
            //check if the user has the rights to create a new customer
            if (!$this->_isAllowed('create', 'customer')) {
                $this->View()->assign(array(
                    'success' => false,
                    'data' => $this->Request()->getParams(),
                    'message' => $namespace->get('no_create_rights','You do not have sufficient rights to view create a customer.')
                ));
                return;
            }
            $customer = new Customer();
            $billing = new Billing();
            $shipping = new Shipping();
            $debit = new Debit();
        }

        try {
            if (!$shipping instanceof Shipping) {
                $shipping = new Shipping();
            }
            if (!$debit instanceof Debit) {
                $debit = new Debit();
            }

            $params = $this->Request()->getParams();

            if (!$paymentData instanceof PaymentData && !empty($params['paymentData']) && array_filter($params['paymentData'][0])) {
                $paymentData = new PaymentData();
                $customer->addPaymentData($paymentData);
                $paymentData->setPaymentMean(
                    Shopware()->Models()->getRepository('Shopware\Models\Payment\Payment')->find($paymentId)
                );
            }

            $params = $this->prepareCustomerData($params, $customer, $billing, $shipping, $debit, $paymentData);

            //set parameter to the customer model.
            $customer->fromArray($params);

            $password = $this->Request()->getParam('newPassword',null);

            //encode the password with md5
            if (!empty($password)) {
                $customer->setPassword($password);
            }

            $this->getManager()->persist($customer);
            $this->getManager()->flush();

            $this->View()->assign(array(
                'success' => true,
                'data' => $this->getCustomer($customer->getId())
            ));
        } catch (\Doctrine\ORM\ORMException $e) {
            $this->View()->assign(array(
                'success' => false,
                'data' => $this->Request()->getParams(),
                'message' => $e->getMessage())
            );
        }
    }

    /**
     * Internal helper function to get a single customer
     * @param $id
     * @return array|mixed
     */
    private function getCustomer($id)
    {
        $query = $this->getRepository()->getCustomerDetailQuery($id);

        $data = $query->getOneOrNullResult(Doctrine\ORM\AbstractQuery::HYDRATE_ARRAY);

        //we need to set the billing and shipping attributes to the first array level to load the data into a form panel
        $data[0]['billingAttribute'] = $data[0]['billing']['attribute'];
        $data[0]['shippingAttribute'] = $data[0]['shipping']['attribute'];
        unset($data[0]['billing']['attribute']);
        unset($data[0]['shipping']['attribute']);

        $orderInfo = array(
            'orderCount' => $data['orderCount'],
            'amount' => $data['amount'],
            'shopName' => $data['shopName'],
            'language' => $data['language'],
            'canceledOrderAmount' => $data['canceledOrderAmount']
        );

        $data = array_merge($orderInfo, $data[0]);
        $birthday = $data['billing']['birthday'];

        /** @var $birthday \DateTime */
        if ($birthday instanceof \DateTime) {
            $data['billing']['birthday'] = $birthday->format('d.m.Y');
        }

        return $data;
    }

    private function prepareCustomerData($params, $customer, $billing, $shipping, $debit, $paymentData)
    {
        if (!empty($params['groupKey'])) {
            $params['group'] = $this->getGroupRepository()->findOneBy(array('key' => $params['groupKey']));
        } else {
            unset($params['group']);
        }

        if (!empty($params['shopId'])) {
            $params['shop'] = Shopware()->Models()->find('Shopware\Models\Shop\Shop', $params['shopId']);
        } else {
            unset($params['shop']);
        }

        if (!empty($params['languageId'])) {
            $params['languageSubShop'] = Shopware()->Models()->find('Shopware\Models\Shop\Shop', $params['languageId']);
        } else {
            unset($params['languageSubShop']);
        }


        if (!empty($params['priceGroupId'])) {
            $params['priceGroup'] = Shopware()->Models()->find('Shopware\Models\Customer\PriceGroup', $params['priceGroupId']);
        } else {
            $params['priceGroup'] = null;
        }

        //If a different payment method is selected, it must also be placed in the "paymentPreset" so that the risk management that does not reset.
        if ($customer->getPaymentId() !== $params['paymentId']) {
            $params['paymentPreset'] = $params['paymentId'];
        }

        if (empty($id) && empty($params['shipping'][0]["firstName"]) && empty($params['shipping'][0]["lastName"])) {
            //shipping params are empty use the billing ones
            $params['shipping'][0] = $params['billing'][0];
        }

        if (!empty($params['paymentData']) && $paymentData) {
            $paymentData->fromArray(array_shift($params['paymentData']));
        }
        unset($params['paymentData']);

        /**
         * Temporary support for deprecated s_user_debit table
         * Can be removed after the table is removed
         */
        if($paymentData && $paymentData->getPaymentMean()->getName() == 'debit') {
            $debitData = array(
                'account' => $paymentData->getAccountNumber(),
                'accountHolder' => $paymentData->getAccountHolder(),
                'bankName' => $paymentData->getBankName(),
                'bankCode' => $paymentData->getBankCode()
            );
            $params['debit'] = $debitData;
        }

        $params['billing'] = $params['billing'][0];
        $params['shipping'] = $params['shipping'][0];
        $params['attribute'] = $params['attribute'][0];
        $params['billing']['attribute'] = $params['billingAttribute'][0];
        $params['shipping']['attribute'] = $params['shippingAttribute'][0];

        return $params;
    }


    /**
     * Deletes a single customer or an array of customers from the database.
     * Expects a single customer id or an array of customer ids which placed in the parameter customers
     */
    public function deleteAction()
    {
        try {
            //get posted customers
            $customers = $this->Request()->getParam('customers', array(array('id' => $this->Request()->getParam('id'))));

            //iterate the customers and add the remove action
            foreach ($customers as $customer) {
                $entity = $this->getRepository()->find($customer['id']);
                $this->getManager()->remove($entity);
            }
            //Performs all of the collected actions.
            $this->getManager()->flush();

            $this->View()->assign(array(
                'success' => true,
                'data' => $this->Request()->getParams())
            );
        } catch (Exception $e) {
            $this->View()->assign(array(
                'success' => false,
                'data' => $this->Request()->getParams(),
                'message' => $e->getMessage())
            );
        }
    }

    /**
     * Validates the inserted email address
     */
    public function validateEmailAction()
    {
        Shopware()->Plugins()->Controller()->ViewRenderer()->setNoRender();
        $mail = $this->Request()->value;

        $query = $this->getRepository()->getValidateEmailQuery($mail, $this->Request()->param,$this->Request()->subshopId);

        $customer = $query->getArrayResult();

        if (empty($customer) && preg_match('/[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,4}/i', $mail)) {
            echo true;
        } else {
            echo false;
        }
    }

    /**
     *
     */
    public function performOrderAction()
    {
        $userId = $this->Request()->getParam('id');
        $sql = 'SELECT id, email, password, subshopID, language FROM s_user WHERE id = ?';
        $user = Shopware()->Db()->fetchRow($sql, array($userId));

        if (!empty($user['email'])) {
            $repository = Shopware()->Models()->getRepository('Shopware\Models\Shop\Shop');
            $shop = $repository->getActiveById($user['language']);
            $shop->registerResources(Shopware()->Bootstrap());
            Shopware()->Session()->Admin = true;

            Shopware()->System()->_POST = array(
                'email' => $user['email'],
                'passwordMD5' => $user['password'],
            );
            Shopware()->Modules()->Admin()->sLogin(true);
        }

        $url = $this->Front()->Router()->assemble(array(
            'module' => 'frontend',
            'controller' => 'index',
            'appendSession' => true
        ));

        $this->Response()->setCookie('shop', $shop->getId(), 0, $shop->getBasePath());

        $this->Response()->setCookie('session-' .  $shop->getId(), '', time() - 3600, '/');

        $this->redirect($url);
    }
}
