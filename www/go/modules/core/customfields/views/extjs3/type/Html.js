Ext.ns("go.modules.core.customfields.type");

go.modules.core.customfields.type.Html = Ext.extend(go.modules.core.customfields.type.Text, {

	name: "Html",

	label: t("HTML"),

	//iconCls: "ic-text",

	/**
	 * Return dialog to edit this type of field
	 * 
	 * @returns {go.modules.core.customfields.FieldDialog}
	 */
	getDialog: function () {
		return new go.modules.core.customfields.type.HtmlDialog();
	},

	/**
	 * Render's the custom field value for the detail views
	 * 
	 * @param {mixed} value
	 * @param {object} data Complete entity
	 * @param {object} customfield Field entity from custom fields
	 * @returns {unresolved}
	 */
	renderDetailView: function (value, data, customfield) {
		return value;
	},

	/**
	 * Returns config oject to create the form field 
	 * 
	 * @param {object} customfield customfield Field entity from custom fields
	 * @param {object} config Extra config options to apply to the form field
	 * @returns {Object}
	 */
	createFormFieldConfig: function (customfield, config) {
		var config = go.modules.core.customfields.type.Html.superclass.createFormFieldConfig.call(this, customfield, config);
		config.xtype = "xhtmleditor";		
		return config;
	},

	getFieldType: function () {
		return "string";
	}


});

go.modules.core.customfields.CustomFields.registerType(new go.modules.core.customfields.type.Html());
