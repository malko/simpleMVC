/**
* permit to manage an entire dataset by adding sort capability and pagination to the client side
** to know the list of possible options to pass as third element when initializing tables just look at the dfltOptions property
** possible headers value: array of string or object
*  object can have thoose properties: - label (required) the header cell label
*                                     - width (opt) the column width
*                                     - sortmethod (opt) a function to call for sorting this column this is a basic sample for col 2:
*                                                  function mysort(a,b){ if(a[2]>b[2]) return 1;	if(a[2]<b[2]) return -1; return 0;}
*                                     - unsortable (opt) boolean value set this to true if you don't want the column to be sortable
*
* @since 2006-08-24
* @changelog
*            - 2009-06-24 - add bodyRendering callback option
*            - 2009-04-01 - add saveUserPrefs options to save user preferences by cookies
* @example
* here's a very basic sample usage
* first include this file:
<script language="javascript" type="text/javascript" src="sortTable.js"></script>
* inside the javascript tags (in the header) or in an external js file
<script language="javascript" type="text/javascript">
// declare the dataset (generally done on server side).
myTableId = [
	['john','1976-09','M',78500],
	['marie','1985-12','F',95100],
	['estella','1979-02','F',06380],
	['boby','1981-05','M',78800],
	.... many rows like thoose ...
	['eddy','1978-05','M',84670]
];
window.onload = function(){
	var mytableheaders = [{label:'name',width:'150px'}, {label:'birthday',width:'100px',sortmethod:myDateComparisonFunction}, {label:'gender',width:'20px',unsortable:true}, 'town zip'];
	// myTableId MUST BE the same as the dataset variable name
	sortTable.init('myTableId', mytableheaders);
}
</script>
* inside the body html
<table id='myTableId' cellspacing="0" cellpadding="2" border="1"></table>
* @endexample
*
* and that's all. see the code commentary for more detail on what you can do with this
* For the visual style just use CSS rules will be fine
*/
// Global variable to know which col is sorting
var _SORTINGCOL = false;
sortTable = {
	tables: {}, 	// tables object indexed by tables id
	sortingCol:{},// list of column used for sort indexed by tables ids
	sortingWay:{},// list ordering way used for sort indexed by tables ids
	headers:{},   // headers of tables indexed by table ids
	// list of defaults options for rendering.
	dfltOptions:{
		pageSize: 10,
		pageNb: 1,
		// html to append to header cells to show the sorting order //
		upArrow: ' &uarr;',
		downArrow: ' &darr;',
		saveUserPrefs:true, // whether or not to use cookies to save user prefs
		// list of possible page size users can set manually, put false if you don't want to allow them to change pageSize
		userPageSize: [10,20,30,50,['all','all']],
		/* bodyRendering, rowRendering and cellRendering are some 'post processing' callback function to allow you to make particular job on the table datas.
			the callback function will receive the body, row or cell element as first argument and a javascript object as second argument containing:
			,tid,table as properties,
			for bodyRendering: table, tid and data (body datas)
			for rowRendering: table, tid, rowid and data (row datas)
			for cellRendering: table, tid, rowid, colid and data (cell datas) */
		rowRendering: false,
		cellRendering:false,
		bodyRendering:false,
		/* some template strings for footer rendering all thoose strings can contain
		%pnav(pagenavigation) %psize(nb res by page) %pinput(input for manual page selection) %pnum(current page num)
		%nbres(total of record in dataset) %tid (table id) %psizesel(manual page size selector) %nbpages(total page count)
		%pstart(first record displayed on current page) %pend(last record displayed on current page) */
		footerString: '<span style="float:right;" class="sorttable-pagesize-setting">afficher %psizesel lignes</span><div style="white-space:nowrap;" class="sorttable-pagenav-settings">%pnav</div>', //template string for footer rendering
		pinputStr: 		'<input type="text" value="%pnum" onfocus="this.value=\'\';" onkeydown="if(event.keyCode == 13){sortTable.setPageNb(\'%tid\',this.value);}" size="3" title="jump to page" style="text-align:center;">',
		// template string for page navigation rendering (MUSTN'T CONTAIN %pnav as it will be used to generate replacement string for %pnav)
		navAttrs: {
			first    : '<a href="%lnk" class="pagelnk"><<</a>',
			prev     : '<a href="%lnk" class="pagelnk"><</a>',
			next     : '<a href="%lnk" class="pagelnk">></a>',
			last     : '<a href="%lnk" class="pagelnk">>></a>',
			pages    : '<a href="%lnk" class="pagelnk">%page</a>',
			curpage  : '<b><a href="%lnk" class="pagelnk">%page</a></b>',
			linkSep  : ' ',
			formatStr: ' %first %prev page %pinput / %nbpages %next %last '
			//formatStr: ' %first %prev %5links %next %last '
		},
		// by default when sorting the previous sorted col the datas are just reversed. Setting this to true will force to resort datas instead
		forceResort: false,
		// optionnal function to call to show activity on long operation to the user receive tid and int/false as parameter true when starting activity false when finishing
		// int will be 1 for rendering operation 2 for sorting operations
		showActivity: function(tid,isWorking){
			document.body.style.cursor = isWorking?'wait':'default';
			var loading = document.getElementById('_SORTWORKING');
			if(! loading ){
				loading = document.createElement('div');
				//~ document.getElementsByTagName('BODY')[0].appendChild(loading);
				document.body.appendChild(loading);
				loading.id 							 = '_SORTWORKING';
				loading.style.display    = 'none';
				loading.style.background = '#f0f0ff';
				loading.style.color      = '#ff0000';
				loading.style.position   = 'absolute';
				loading.style.top        = '0px';
				loading.style.left       = '0px';
				loading.style.fontWeight = 'bold';
				loading.innerHTML 			 = '<br /><br />&nbsp;&nbsp;&nbsp;&nbsp;Working ...';
				if( loading.style.opacity !== undefined)
					loading.style.opacity = '0.75';
				else if(loading.style.filter !== undefined)
					loading.style.filter = 'alpha(opacity=75);';
			}
			if(isWorking){
				// get table size and position
				var x = y = 0, e = document.getElementById(tid);
				if(e.tBodies.length) e = e.tBodies[0];
				var w = e.offsetWidth, h = e.offsetHeight;
				while (e!=null){ x+=e.offsetLeft; y+=e.offsetTop; e=e.offsetParent; }
				loading.style.height = h+'px';
				loading.style.width  = w+'px';
				loading.style.left   = x+'px';
				loading.style.top    = y+'px';
			}
			loading.style.display = isWorking?'block':'none';
		}
	},
	cookies:{
		get:function(name){
				var re=new RegExp(name+'=([^;]*);?','gi'), result=re.exec(document.cookie)||[];
				return (result.length>1? unescape(result[1]): false);
		},
		set:function(name, value, expirationTime, path, domain, secure){
				var time=new Date;
				if(expirationTime)
						time.setTime(time.getTime()+(expirationTime*1000));
				document.cookie=name+ '='+ escape(value)+ '; '+
				(!expirationTime? '': '; expires='+time.toUTCString())+
				'; path='+(path?path:'/')+ (!domain? '': '; domain='+domain)+ (!secure? '': '; secure');
		},
		del:function(name, path, domain){
				var value=this.get(name);
				document.cookie=name+ '='+ '; path='+(path?path:'/')+
				(!domain? '': '; domain='+domain)+
				'; expires=Thu, 01-Jan-70 00:00:01 GMT';
				return value;
		}
	},
	// keep trace of each table options
	options:{},
	//-- @private property to determine the sort algorithm to use
	_useQuickSort: 0,

	/**
	* initialize vars for the table
	*/
	init: function(tid,headers,options){
		//-- FIRST TEST THE DEFAULT SORTING METHOD EFFICIENCY --//
		if( this._useQuickSort===0){
			// we test how many comparisons are made to sort this array to determine the efficiency of the default implementation .sort() method
			// here are some known results: IE6=>33, FF1.5=>24, Opera9.0=>18, konqeror3.4=>16
			testarray = [1,2,3,4,5,6,7,8,9];
			testarray.sort(function(a,b){sortTable._useQuickSort++;if(a>b) return 1;if(a<b) return -1;return 0;});
			this._useQuickSort = (this._useQuickSort > 25);
		}

		// setting up internal values for this table
		this.tables[tid]     = document.getElementById(tid);
		this.headers[tid]    = headers;
		this.sortingCol[tid] = false;
		this.sortingWay[tid] = false;

		// manage options
		if( ! options){
			options = this.dfltOptions;
		}else{
			for(var p in this.dfltOptions){
				if( options[p] == undefined){
					options[p] = this.dfltOptions[p];
				}else if(p=='navAttrs'){
					for( var pp in this.dfltOptions[p]){
						if(options[p][pp] == undefined){
							options[p][pp] = this.dfltOptions[p][pp];
						}
					}
				}
			}
		}
		this.options[tid] = options;
		if( options.saveUserPrefs){
			var cpageSize = this.cookies.get('sortTable_'+tid+'pageSize');
			if( cpageSize) this.options[tid].pageSize = cpageSize;
			var cpageNb = this.cookies.get('sortTable_'+tid+'pageNb');
			if( cpageNb) this.options[tid].pageNb = cpageNb;
			var csCol = this.cookies.get('sortTable_'+tid+'sortingCol');
			var csWay = this.cookies.get('sortTable_'+tid+'sortingWay');
			this.sortingWay[tid] = csWay!==false?(csWay=='asc'?'desc':'asc'):false;
		}
		// render the table
		if(options.showActivity){
			options.showActivity(tid,1);
			setTimeout("sortTable._mk_headers('"+tid+"');"+(typeof(csCol)=='string'?"sortTable.sort('"+tid+"','"+csCol+"');":"sortTable._populate('"+tid+"');")+" sortTable._mk_footer('"+tid+"');",50);
		}else{
			this._mk_headers(tid);
			if(typeof(csCol)=='string'){
				this.sort(tid,csCol);
			}else{
				this._populate(tid);
			}
			this._mk_footer(tid);
		}
	},

	/**
	* return the data set associated to the table
	*/
	getDatas: function(tid){
		eval('var datas = '+tid+';');
		return datas;
	},

	/**
	* set the number of viewable rows
	*/
	setPageSize: function(tid,pageSize){
		if(pageSize =='all'){
			pageSize = this.getDatas(tid).length;
			this.options[tid].pageNb = 1;
		}
		this.options[tid].pageSize = pageSize;
		this.setPageNb(tid,this.options[tid].pageNb);
		if( this.options[tid]['saveUserPrefs'] ){
			this.cookies.set('sortTable_'+tid+'pageSize',pageSize);
			this.cookies.set('sortTable_'+tid+'pageNb',this.options[tid].pageNb);
		}
	},

	/**
	* set the page number to show
	*/
	setPageNb: function (tid,pageNb){
		if(isNaN(pageNb)){
			return alert('invalid page number');
		}
		var nbRes     = this.getDatas(tid).length;
		var maxPageNb = Math.ceil(nbRes / this.options[tid].pageSize);
		this.options[tid].pageNb = (pageNb>maxPageNb)?maxPageNb:pageNb;
		if( this.options[tid]['saveUserPrefs'] ){
			this.cookies.set('sortTable_'+tid+'pageNb',this.options[tid].pageNb);
		}
		// refresh display
		if(this.options[tid].showActivity){
			this.options[tid].showActivity(tid,1);
			setTimeout("sortTable._populate('"+tid+"'); sortTable._mk_footer('"+tid+"');",50);
		}else{
			this._populate(tid);
			this._mk_footer(tid);
		}
	},

	/**
	* render header cells
	*/
	_renderTh: function(tid,col){
		// get label
		var label = this.headers[tid][col];
		var unsortable = false;
		if(typeof label == 'object'){
			unsortable = label.unsortable;
			label = label.label;
		}
		if(! label){
			label = '&nbsp;';
			unsortable = true;
		}
		// get existing cell or create it
		var cell = document.getElementById('th_'+col+'_'+tid);
		if(! cell){
			var cell = document.createElement("th");
			cell.id = 'th_'+col+'_'+tid;
			if(! unsortable){
				cell.onclick = function(){sortTable.sort(tid,col);};
				cell.style.cursor = 'pointer';
			}
		}
		// put clean content
		cell.innerHTML = label;
		// add arrow if required
		if( col===this.sortingCol[tid] ){
			var w = this.sortingWay[tid];
			cell.innerHTML += this.options[tid][((this.sortingWay[tid]=='asc')?'downArrow':'upArrow')];
		}
		return cell;
	},

	/**
	* create headers for the table
	*/
	_mk_headers: function(tid){
		var table   = this.tables[tid];
		var headers = this.headers[tid];
		table.deleteTHead();
		var header = table.createTHead();
		var head = header.insertRow(0);
		for(var i = 0; i < this.headers[tid].length; i++){
			var cell = this._renderTh(tid,i);
			head.appendChild(cell);
			if((typeof headers[i] == 'object') && headers[i].width){
				cell.width = headers[i].width;
			}
		}
	},

	/**
	* create the footer wich will contain the pagination
	*/
	_mk_footer: function(tid){
		var table  = this.tables[tid];
		var options= this.options[tid];
		table.deleteTFoot();
		var footer = table.createTFoot();
		var foot   = footer.insertRow(0);
		var cell   = document.createElement('td');
		cell.colSpan = this.headers[tid].length;
		foot.appendChild(cell);

		// prepare some var for further replacement
		//%pnav %psize %pinput %pnum %nbres %tid %psizesel
		var pnav    = this._mk_pageNav(tid);
		var pinput  = options.pinputStr;
		var pnum    = Number(options.pageNb);
		var psize   = Number(options.pageSize);
		var nbres   = this.getDatas(tid).length;
		var nbpages = Math.ceil(nbres / Math.max(1,psize));
		var pstart  = psize*(pnum-1)+1;
		var pend    = Math.min(pnum*psize,nbres);


		// render the pageSize selector
		if(options.userPageSize){
			var psizesel = '<select name="pageselector%tid" id="pageselector%tid" onchange="sortTable.setPageSize(\'%tid\',this.value);">';
			var lb='';var val='';
			var curval = psize==nbres?'all':psize; // used to compare with the all value
			for(var i=0;i<options.userPageSize.length;i++){
				if(typeof options.userPageSize[i] != 'object'){
					val = lb = options.userPageSize[i];
				}else{
					val = options.userPageSize[i][0];
					lb  = options.userPageSize[i][1];
				}
				psizesel += '<option value="'+val+(val==curval?'" selected="selected':'')+'">'+lb+'</option>';
			}
			psizesel += "</select>";
		}

		// render foot string
		var footStr = options.footerString;
		var replaces = ['pnav','pinput','psizesel','pnum','psize','nbres','nbpages','pstart','pend','tid'];
		for( var i=0; i<replaces.length; i++){
			eval('footStr = footStr.replace(/%'+replaces[i]+'/g,'+replaces[i]+');');
		}
		cell.innerHTML = footStr;
		if(options.showActivity){
			options.showActivity(tid,false);
		}
	},

	/**
	* rendering the pagination navigation bar
	* explanation of navAttrs properties:
	*   - firt:  first page link %lnk and %page will be replaced by the link to the page and the number of the page
	*   - prev:  previous page link %lnk and %page will be replaced by the link to the page and the number of the page
	*   - next:  next page link %lnk and %page will be replaced by the link to the page and the number of the page
	*   - last:  last page link %lnk and %page will be replaced by the link to the page and the number of the page
	*   - pages: pages link %lnk and %page will be replaced by the link to the page and the number of the page
	*   - curpage: selected page link %lnk and %page will be replaced by the link to the page and the number of the page
	*   - linkSep: separator between pages links
	*   - formatStr: is used to render the full pagination string
	*                %start, %prev, %next, %last will be replaced respectively by corresponding links
	*                %Nlinks will be replaced by the pages links. N is the number of link to display
	*                including the selected page ex: %5links will show 5 pages links
	*   all keys can also contain a %nbres and %nbpages which will be replaced respectively by
	*   the total amount of result and the total number of pages.
	*   %startView and %endView
	*/
	_mk_pageNav: function(tid){
		var options  = this.options[tid];
		var navAttrs = options.navAttrs;
		var pageNb   = Number(options.pageNb);
		var pageSize = Number(options.pageSize);
		var nbres      = this.getDatas(tid).length;
		var plink    = 'javascript:sortTable.setPageNb(\''+tid+'\',%page);';

		if( nbres <= pageSize || ! ( nbres > 0 && pageSize > 0 && pageNb > 0 ) ){
			return '';
		}
		var nbpages = Math.ceil(nbres / Math.max(1,pageSize));

		// extracting attributes (just for easier coding further (yes i'm lazy))
		for(i in navAttrs){ eval('var '+i+'= navAttrs.'+i+';');	}

		// start & prev link
		if( nbpages > 1 && pageNb != 1){
			first = first.replace(/%lnk/g,plink).replace(/%page/g,1);
			prev  = prev.replace(/%lnk/g,plink).replace(/%page/g,pageNb-1);
		}else{
			first = prev = '';
		}

		// next & end link
		if( pageNb < nbpages ){
			last = last.replace(/%lnk/g,plink).replace(/%page/g,nbpages);
			next = next.replace(/%lnk/g,plink).replace(/%page/g,pageNb+1);
		}else{
			last = next = '';
		}

		//pages links
		var pageLinks = new Array();
		var m = formatStr.match(/%(\d+)?links/)
		if( m ){
			var nblinks = m[1];
			if(nblinks){ // range of pages link
				var delta      = (nblinks%2?(nblinks-1):nblinks)/2;
				var slideStart = Math.max(1,pageNb - delta - ((pageNb + delta) <= nbpages ? 0 : pageNb -(nbpages-delta)) );
				var slideEnd   = Math.min(nbpages,pageNb + delta + (pageNb > delta ? 0: delta - pageNb + 1 ) );
			}else{ // all pages links
				nblinks    ='';
				var slideStart = 1;
				var slideEnd   = nbpages;
			}

			for(var i=slideStart; i<=slideEnd; i++){
				var str = (i == pageNb)?curpage:pages;
				str = str.replace(/%lnk/g,plink);
				str = str.replace(/%page/g,i);
				pageLinks.push(str);
			}
			var links = pageLinks.join(linkSep);
		}else{
			var links = nblinks = '';
		}
		// replace in template string
		if(links)
			formatStr = formatStr.replace(new RegExp('%'+nblinks+'links','g'),links);

		var replaces = ['first','prev','next','last'];//,'links'
		for( var i=0; i<replaces.length; i++){
			eval('formatStr = formatStr.replace(/%'+replaces[i]+'/g,'+replaces[i]+');');
		}

		return formatStr;
	},

	/**
	* populate visible rows
	*/
	_populate: function(tid){
		var table = this.tables[tid];
		var options = this.options[tid];
		if(! table.tBodies.length ){
			var tbody = document.createElement('tbody');
			table.appendChild(tbody);
		}else{
			var tbody = table.tBodies[0];
		}
		// start to delete existing rows
		var oldRows  = tbody.rows;
		if(oldRows.length != 0){
			for(var i=oldRows.length;i>0;i--){
				tbody.deleteRow(i-1);
			}
		}

		// now preparing the viewable rows
		var pageSize = parseInt(options.pageSize);
		var first    = pageSize * (options.pageNb -1);
		var last     = first + pageSize;
		var heads    = this.headers[tid];
		var datas = this.getDatas(tid);
		var bodyDatas = {};

		for(var i=first;i < last; i++){
			if(! datas[i]){
				break;
			}
			var row = tbody.insertRow(i-first);
			for(var col=0; col < heads.length; col++){
				var cell = document.createElement("td");
				cell.innerHTML = datas[i][col];
				row.appendChild(cell);
				if( options.cellRendering ){
					options.cellRendering(cell,{row:row,colid:col,rowid:i,table:table,tid:tid,data:datas[i][col]});
				}
			}
			if( options.rowRendering ){
				options.rowRendering(row,{rowid:i,table:table,tid:tid,data:datas[i]});
			}
			bodyDatas[i]=datas[i];
		}
		if( options.bodyRendering ){
			options.bodyRendering(tbody,{table:table,tid:tid,data:bodyDatas});
		}
	},

	/**
	* call the sort
	*/
	sort: function(tid,col){
		if(this.options[tid].showActivity){
			this.options[tid].showActivity(tid,50);
			setTimeout('sortTable._sort("'+tid+'",'+col+');',5);
		}else{
			this._sort(tid,col);
		}
	},

	_sort:function(tid,col){
		// get some needed infos
		var datas = this.getDatas(tid);
		var o = this.sortingCol[tid];
		var w = this.sortingWay[tid];
		var forceResort = this.options[tid].forceResort;
		// setting new values
		w = ( o == col && w == 'asc')?'desc':'asc';
		this.sortingCol[tid] = col;
		this.sortingWay[tid] = w;

		// render selected header cell
		this._renderTh(tid,col);
		// remove previous arrow if needed
		if(o !== false && o !== col){
			this._renderTh(tid,o);
		}

		// set the sorting col for quick access in the sorting function
		_SORTINGCOL = col;

		//-- optimise sorting on previoulsy sorted cols
		if( o===col && ! forceResort){
			datas.reverse();
		}else{
			// sort the data set
			if(w == 'desc' && ! forceResort ){
				datas.reverse(); // on already sort column only reverse the dataset
			}else{
				if(this._useQuickSort){
					this.quicksort(datas,((typeof this.headers[tid][col] == 'object') && this.headers[tid][col].sortmethod)? this.headers[tid][col].sortmethod:this.__sort);
				}else{
					datas.sort( ((typeof this.headers[tid][col] == 'object') && this.headers[tid][col].sortmethod)? this.headers[tid][col].sortmethod:this.__sort);
				}
				if( w=='desc'){
					datas.reverse();
				}
			}
		}

		// redraw table rows
		this._populate(tid);
		if( this.options[tid]['saveUserPrefs'] ){
			this.cookies.set('sortTable_'+tid+'sortingCol',this.sortingCol[tid]);
			this.cookies.set('sortTable_'+tid+'sortingWay',this.sortingWay[tid]);
		}
		if(this.options[tid].showActivity){
			this.options[tid].showActivity(tid,false);
		}
	},

	/**
	* default sorting method
	*/
	__sort: function (a,b){
		if(a[_SORTINGCOL]>b[_SORTINGCOL]) return 1;
		if(a[_SORTINGCOL]<b[_SORTINGCOL]) return -1;
		return 0;
	},

	/* Quick sort implementation by Owen Griffin
	found at http://blog.owengriffin.com/articles/2006/01/26/javascript-quicksort
	and modified to support user defined comparisons methods.
	THOOSE ARE ONLY REQUIRED AND USED UNDER LACKING BROWSERS (Internet Explorer :( )
	*/
	quicksort: function (a,comp) {
		this._quicksort(a, 0, a.length - 1,comp);
	},
	_quicksort: function (a, lo, hi,comp) {
		//  lo is the lower index, hi is the upper index
		//  of the region of array a that is to be sorted
		var i=lo, j=hi, h;
		var x=a[Math.floor((lo+hi)/2)];
		//  partition
		do{
			if(comp){
				while (comp(a[i],x)==-1) i++;
				while (comp(a[j],x)==1) j--;
			}else{
				while (a[i] < x) i++;
				while (a[j] > x) j--;
			}
			if (i<=j){
				h=a[i]; a[i]=a[j]; a[j]=h;
				i++; j--;
			}
		} while (i<=j);

		//  recursion
		if (lo<j) this._quicksort(a, lo, j,comp);
		if (i<hi) this._quicksort(a, i, hi,comp);
	}
}
