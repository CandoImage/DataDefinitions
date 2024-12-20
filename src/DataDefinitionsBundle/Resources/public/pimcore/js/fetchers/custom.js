/**
 * Data Definitions.
 *
 * LICENSE
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @copyright  Copyright (c) 2016-2019 w-vision AG (https://www.w-vision.ch)
 * @license    https://github.com/w-vision/DataDefinitions/blob/master/gpl-3.0.txt GNU General Public License version 3 (GPLv3)
 */

pimcore.registerNS('pimcore.plugin.datadefinitions.fetchers.definition');

pimcore.plugin.datadefinitions.fetchers.custom = Class.create(pimcore.plugin.datadefinitions.fetchers.abstract, {
    getLayout: function (data, config) {
        return [
            {
                xtype: 'input',
                fieldLabel: 'custom method (in Fetcher class)',
                name: 'customMethod',
                value: Ext.isDefined(data.customMethod) ? data.customMethod : ''
            }
        ];
    }
});
