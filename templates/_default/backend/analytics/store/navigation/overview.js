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

/**
 * Analytics Overview Store
 *
 * @category   Shopware
 * @package    Analytics
 * @copyright  Copyright (c) shopware AG (http://www.shopware.de)
 *
 */
Ext.define('Shopware.apps.Analytics.store.navigation.Overview', {
    extend: 'Ext.data.Store',
    alias: 'widget.analytics-store-navigation-overview',
    remoteSort: true,
    fields: [
        { name: 'date', type: 'date', dateFormat: 'timestamp' },
        'orderCount',
        'revenue',
        'cancelledOrders',
        'totalConversion',
        'totalVisits',
        'newCustomers',
        'visitors',
        'clicks'
    ],
    proxy: {
        type: 'ajax',
        url: '{url controller=analytics action=getOverview}',
        reader: {
            type: 'json',
            root: 'data',
            totalProperty: 'total'
        }
    }
});
