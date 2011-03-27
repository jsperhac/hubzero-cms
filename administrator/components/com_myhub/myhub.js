/**
 * @package     hubzero-cms
 * @file        administrator/components/com_myhub/myhub.js
 * @copyright   Copyright 2005-2011 Purdue University. All rights reserved.
 * @license     http://www.gnu.org/licenses/lgpl-3.0.html LGPLv3
 */

//-----------------------------------------------------------
//  Ensure we have our namespace
//-----------------------------------------------------------
if (!HUB) {
	var HUB = {};
}

//-------------------------------------------------------------
// My Hub (singleton)
//-------------------------------------------------------------

HUB.Myhub = {
	baseURL: 'index.php?option=com_myhub&no_html=1',
	
	removeModule: function(el) {
		// remove the list item from its parent list
		el.parentNode.parentNode.parentNode.removeChild(el.parentNode.parentNode);
		
		// get the new serials
		var ids = HUB.Sorts.serialize('', function(element, index){
			return element.getProperty('id').replace('mod_','');
		}).join(';');
	
		var uid = $('uid').value;
		$('serials').value = ids;

		// AJAX: send the serials to the server-side script so it can update the list of available modules
		var myAjax = new Ajax(HUB.Myhub.baseURL+'&task=rebuild&uid='+uid+'&ids='+ids,{update:'available'}).request();

		return false;
	},

	addModule: function(id) {
		if(id) {
			// create and attach a new LIst item to a list
			modList = $('sortcol_1');
			var modItem = document.createElement('div');
			modItem.id = 'mod_'+id;
			modItem.className = 'draggable';
			modList.appendChild(modItem);
			
			// AJAX: send request to server-side script to generate and return contents of new LIst item
			var uid = $('uid').value;
			var myAjax1 = new Ajax(HUB.Myhub.baseURL+'&task=addmodule&uid='+uid+'&id='+id,{update:modItem.id}).request();
			
			// re-apply the sorting script so the new LIst item becoems sortable
			HUB.Sorts.reinitialize();
			
			// get the new serials
			var ids = HUB.Sorts.serialize('', function(element, index){
				return element.getProperty('id').replace('mod_','');
			}).join(';');
			$('serials').value = ids;
			
			// AJAX: send the serials to the server-side script so it can update the list of available modules
			var myAjax2 = new Ajax(HUB.Myhub.baseURL+'&task=rebuild&uid='+uid+'&ids='+ids,{update:'available'}).request();
		}
	},
	
	_setHandles: function(w) {
		var v = w ? 'move' : 'auto';
		var handles = document.getElementsByClassName('handle');
		for (var i=0; i < handles.length; i++)
		{
			handles[i].style.cursor = v;
		}
	}
};

// a global variable to hold our sortable object
// done so the Myhub singleton can access the sortable object easily
HUB.Sorts = null;

window.addEvent('domready', function(){
	HUB.Sorts = new xSortables(['sortcol_0', 'sortcol_1', 'sortcol_2'], {handle:'h3[class=handle]',onComplete:function() {
		var ids = this.serialize('', function(element, index){
			return element.getProperty('id').replace('mod_','');
		}).join(';');
		$('serials').value = ids;
		//var uid = $('uid').value;
		//var myAjax = new Ajax(HUB.Myhub.baseURL+'&task=save&uid='+uid+'&ids='+ids).request();
	}});
	HUB.Myhub._setHandles(1);
});

