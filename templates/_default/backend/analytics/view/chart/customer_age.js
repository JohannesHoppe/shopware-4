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
 * Analytics CustomerAge Chart
 *
 * @category   Shopware
 * @package    Analytics
 * @copyright  Copyright (c) shopware AG (http://www.shopware.de)
 *
 */
//{namespace name=backend/analytics/view/main}
//{block name="backend/analytics/view/chart/customer_age"}
Ext.define('Shopware.apps.Analytics.view.chart.CustomerAge', {
    extend: 'Shopware.apps.Analytics.view.main.Chart',
    alias: 'widget.analytics-chart-customer_age',
    legend: {
        position: 'right'
    },
    axes: [
        {
            type: 'Numeric',
            minimum: 0,
            grid: true,
            position: 'bottom',
            fields: ['age'],
            title: '{s name=chart/customer_age/age/title}Age{/s}'
        },
        {
            type: 'Numeric',
            position: 'left',
            fields: ['percent'],
            title: '{s name=chart/customer_age/percent/title}Percentage{/s}'
        }
    ],

    initComponent: function () {
        var me = this;

        me.series = [
            me.createLineSeries(
                {
                    yField: 'percent',
                    xField: 'age'
                },
                {
                    renderer: function (storeItem) {
                        var text = '{s name=chart/customer_age/age/tip/title}Age{/s}: ';
                        text += Ext.util.Format.number(storeItem.get('age'));
                        text += '<br>' + '&nbsp;{s name=chart/customer_age/percent/tip/title}Percent{/s}: ';
                        text += Ext.util.Format.number(storeItem.get('percent')) + '%';

                        this.setTitle(text);
                    }
                }
            ),
        ];

        me.callParent(arguments);
    }
});
//{/block}