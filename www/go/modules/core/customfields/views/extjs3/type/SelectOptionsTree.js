/* global Ext */

go.modules.core.customfields.type.SelectOptionsTree = function(config){

	config = config || {};

	Ext.apply(config, {
		animate:true,
		autoHeight: true,
		disapled:true,
		enableDrag:true,
		containerScroll: true,
		border: true
	});

	config.tbar=[{
		iconCls: 'ic-add',
		handler:function(){
			var node = this.selModel.getSelectedNode();
			if(!node)
			{
				node = this.getRootNode();
			}

			var newNode = new Ext.tree.AsyncTreeNode({
				text: '',				
				expanded:true,
				children:[]
				//iconCls:'ic-'
			});

			newNode = node.appendChild(newNode);

			this.treeEditor.triggerEdit(newNode);
		},
		scope:this
	},'-',
	{
		iconCls: 'ic-delete',
		handler:function(){
			var node = this.selModel.getSelectedNode();
			if(!node)
			{				
				return false;
			}
			node.destroy();
		},
		scope:this
	}];


	go.modules.core.customfields.type.SelectOptionsTree.superclass.constructor.call(this, config);

	this.treeEditor = new Ext.tree.TreeEditor(
		this,
		new Ext.form.TextField({
			cancelOnEsc:true,
			completeOnEnter:true,
			maskRe:/[^:]/
		}),
		{
			listeners:{
				//complete  : this.afterEdit,
				beforecomplete  : function( editor, value, startValue){
					value=value.trim();
					if(go.util.empty(value)){
						editor.focus();
						return false;
					}
				},
				scope:this
			}
		});
		
	this.setValue([]);
}

Ext.extend(go.modules.core.customfields.type.SelectOptionsTree, Ext.tree.TreePanel, {
	
	setValue : function(options) {
		// set the root node
    var root = new Ext.tree.AsyncTreeNode({
        text: 'Root',
        draggable:false,
        id:'root',
        children: this.apiToTree(options),
				expanded: true
    });

    this.setRootNode(root);
	},
	
	apiToTree : function(options) {
		var me = this;
		options.forEach(function(o) {
			o.expanded = true; //always expand or they won't be submitted and thus deleted on the server!
			o.children = me.apiToTree(o.children);
			o.serverId = o.id;
			delete o.id;
		});
		
		return options;
	},
	
	name: "options",
	
	isFormField: true,
	getName: function () {
		return this.name;
	},
	_isDirty: true,
	isDirty: function () {
		return this._isDirty;
	},
	getValue: function () {
		return this.treeToAPI(this.getRootNode());	
	},
	
	treeToAPI : function(node) {
		var v = [], me = this;
		node.childNodes.forEach(function(child) {
			v.push({
				id: child.attributes.serverId || null,
				text: child.text,
				children: me.treeToAPI(child)
			});
		});
		
		return v;
	},
	
	markInvalid: function (msg) {
		this.getEl().addClass('x-form-invalid');
		Ext.form.MessageTargets.qtip.mark(this, msg);
	},
	clearInvalid: function () {
		this.getEl().removeClass('x-form-invalid');
		Ext.form.MessageTargets.qtip.clear(this);
	},
	validate : function() {
		return true;
	}
});
