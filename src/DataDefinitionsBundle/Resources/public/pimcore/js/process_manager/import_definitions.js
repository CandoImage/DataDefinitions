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

document.addEventListener('processmanager.ready', function () {
    processmanager.executable.types.importdefinition = Class.create(pimcore.plugin.processmanager.executable.abstractType, {
        getItems: function () {
            pimcore.globalmanager.get('data_definitions_definitions').load();

            return [{
                xtype: 'combo',
                fieldLabel: t('data_definitions_import_definitions'),
                name: 'definition',
                displayField: 'name',
                valueField: 'id',
                store: pimcore.globalmanager.get('data_definitions_definitions'),
                value: this.data.settings.definition,
                allowBlank: false
            }, {
                xtype: 'fieldcontainer',
                fieldLabel: t('data_definitions_processmanager_params'),
                layout: 'hbox',
                width: 500,
                value: this.data.settings.filePath,
                items: [{
                    xtype: "textfield",
                    name: 'params',
                    id: 'data_definitions_processmanager_params',
                    width: 450,
                    value: this.data.settings.params,
                    allowBlank: true
                }, {
                    xtype: "button",
                    text: t('find'),
                    iconCls: "pimcore_icon_search",
                    style: "margin-left: 5px",
                    handler: this.openSearchEditor.bind(this)
                },
                    {
                        xtype: "button",
                        text: t('upload'),
                        cls: "pimcore_inline_upload",
                        iconCls: "pimcore_icon_upload",
                        style: "margin-left: 5px",
                        handler: function (item) {
                            this.uploadDialog();
                        }.bind(this)
                    }]
            }];
        },

        uploadDialog: function () {
            pimcore.helpers.assetSingleUploadDialog("", "path", function (res) {
                try {
                    var data = Ext.decode(res.response.responseText);
                    if (data["id"]) {
                        this.setValue(data["fullpath"]);
                    }
                } catch (e) {
                    console.log(e);
                }
            }.bind(this));
        },

        openSearchEditor: function () {
            pimcore.helpers.itemselector(
                false,
                this.addDataFromSelector.bind(this),
                {
                    type: ["asset"],
                    subtype: {},
                    specific: {}
                }
            );
        },

        addDataFromSelector: function (data) {
            this.setValue(data.fullpath);
        },

        setValue: function (value) {
            var params = '{"file":"var/assets' + value + '"}';
            Ext.getCmp('data_definitions_processmanager_params').setValue(params);
        }
    });
});
