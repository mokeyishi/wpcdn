jQuery(function ( $ ) {
aauicri_info=['Import CDN-Remote Images Plugin v2.1.2 https://atakanau.blogspot.com/2020/10/import-cdn-remote-images-wp-plugin.html']
// import page
if($("#aauicri_page-import").length){
	var aauicriJobCancel = false;
	// import page urllist
	$('input[name="aauicri-urlformat"]:radio').change(function() {
		var text= $(this).parent().text().trim();
		var placeholder = "";
		for(var i=1;i<4;i++)
			placeholder+=text.replace(/]/g,"-"+i+"]")+"\n";
		placeholder+="⁞\n";
		placeholder+=text.replace(/]/g,"-N]")+"\n";
		
		$("#aauicri-urllist").attr("placeholder",placeholder);
	});
	$('input[name="aauicri-urlformat"]:checked').trigger('change');

	$( '#aauicri-importanalyse' ).click( function ( e ) {
		var ajax_url=$(this).data("ajax_url")
		,elem=$(this)
		,spinner=$(this).parent().find('.spinner')
		,urlformat=parseInt($('input[name="aauicri-urlformat"]:checked').val())
		,urllist=$('#aauicri-urllist').val().trim().split("\n")
		,urls=[]
		,urlscheckdp=[]
		,urlsinvalid=[]
		,a,row,valid,tablejob
		;
		$('.aauicri-importnotice').addClass("hidden");
		$('#aauicri-importtable-clone').remove();
		if(urlformat==0){	// 		[URL]
			for(var i = 0; i < urllist.length; i++)
				if( urllist[i].trim() && urls.indexOf(urllist[i]) == -1 )
					urls.push(urllist[i]);
				// else
					// urlsinvalid.push(urllist[i]);
		}
		else if(urlformat==1){	//	[URL],[Width],[Height]
			for(var i = 0; i < urllist.length; i++){
				a=urllist[i].split(",");
				row=a.length >= 3 ? [a.pop().trim(),a.pop().trim(),a.join(",").trim()] : [0,0,0];	//	[Height],[Width],[URL]
				valid = ( row[2]
						&& row[1] == parseInt(row[1])+"" 
						&& row[0] == parseInt(row[0])+"" 
					);
				if( valid && urlscheckdp.indexOf(row[2]) == -1 ){
					urlscheckdp.push(row[2]); 	//	[URL] store for duplicate checking
					urls.push( Array(row[2],parseInt(row[1]),parseInt(row[0])) ); 	//	[URL],[Width],[Height]
				}
				else{
					urlsinvalid.push( Array( urllist[i],'?','?' ) );	//	[URL],[Width],[Height]
				}
			}
		}
		if(urls.length){	// url(s) exist
			// ui info
			elem.text(elem.data('updating'));
			$('#aauicri-urllist').attr('disabled','disabled');
			$('input[name="aauicri-urlformat"]:radio').attr('disabled','disabled');
			tablejob=aauicri_drawjobtable(urls,urlsinvalid,urlformat);
			aauicri_enable_disable(elem,spinner,false)
			$(".aauicri-importprogress-0").removeClass("hidden");
			$(".aauicri-importresult-0").addClass("hidden");
			$(".aauicri-listcount-0").text(urls.length);
			// start ajax for image import
			aauicri_add_to_library_part(ajax_url,urls,elem,spinner,0,0,urlformat,tablejob);	// add images via breaking into pieces
		}
		else{	// no url
			$('.aauicri-importnotice.aauicri-0').show();
			$(".aauicri-importprogress-0").addClass("hidden");
			$(".aauicri-importresult").addClass("hidden");
			$('.aauicri-importnotice.aauicri-0').text(elem.data('msg_nodata'));
		}
	});

	function aauicri_drawjobtable(urls,urlsinvalid,urlformat){
		var tablesrc=$('#aauicri-importtable')
		,table=tablesrc.clone().removeClass('hidden')
		,tr_1=table.find('tr:eq(1)').clone()
		,tr_0=table.find('tr:eq(2)').clone()
		,tr_temp
		;
		table.removeAttr('hidden').removeAttr('id').attr('id','aauicri-importtable-clone');
		table.find('tr:eq(1)').remove();
		table.find('tr:eq(1)').remove();
		if(urlformat==0){	// 		[URL]
			for(var i = 0; i < urls.length; i++){
				tr_temp=tr_1.clone();
				tr_temp.find('td:eq(1)').text(urls[i]);
				table.append(tr_temp);
			}
			for(var i = 0; i < urlsinvalid.length; i++){
				tr_temp=tr_0.clone();
				tr_temp.find('td:eq(1)').text(urlsinvalid[i]);
				table.append(tr_temp);
			}
			$('.tab-pane-aauicri_p0').append(table);
		}
		else if(urlformat==1){	//	[URL],[Width],[Height]
			for(var i = 0; i < urls.length; i++){
				tr_temp=tr_1.clone();
				tr_temp.find('td:eq(1)').text(urls[i][0]);
				tr_temp.find('td:eq(2)').text(urls[i][1]);
				tr_temp.find('td:eq(3)').text(urls[i][2]);
				table.append(tr_temp);
			}
			for(var i = 0; i < urlsinvalid.length; i++){
				tr_temp=tr_0.clone();
				tr_temp.find('td:eq(1)').text(urlsinvalid[i][0]);
				tr_temp.find('td:eq(2)').text(urlsinvalid[i][1]);
				tr_temp.find('td:eq(3)').text(urlsinvalid[i][2]);
				table.append(tr_temp);
			}
			$('.tab-pane-aauicri_p0').append(table);
		}
		else if(urlformat==2){	//	[URL],[Width],[Height],[mime]
			for(var i = 0; i < urls.length; i++){
				tr_temp=tr_1.clone();
				tr_temp.find('td:eq(1)').text(urls[i][0]);
				tr_temp.find('td:eq(2)').text(urls[i][1]);
				tr_temp.find('td:eq(3)').text(urls[i][2]);
				table.append(tr_temp);
			}
			$('.tab-pane-aauicri_p1').append(table);
		}

		return table;
	}
	function aauicri_add_to_library_part(ajax_url,url_list,elem,spinner,count_added,part_no,urlformat,tablejob){	// add images via breaking into pieces
		var block = 10
		,url_list_part = url_list.slice( block*part_no ,block*(part_no+1) ).join("\n")
		,data = {
			req_type: "add_to_media_library"
			,urls: url_list_part
			,single: elem.data("single")
			,multi: elem.data("multi")
			,urlformat: urlformat
		}
		,tabno=urlformat<2?0:1
		,part_start = block*part_no+1
		,part_end = part_start + url_list_part.split("\n").length-1
		;
		$(".aauicri-listblock-"+tabno).text( part_start==part_end ? ( part_end>url_list.length ? url_list.length : part_start ) : part_start + " - " + part_end );
		percent =  parseInt( 100 * part_no / Math.ceil(url_list.length/block) );
		$(".aauicri-addstatuspercent-"+tabno).text( percent );
		progressbar=$(".aauicri-importprogressbar-"+tabno);
		progressbar.css("border-left-width",0);mw=progressbar.width()+27;progressbar.css("border-left-width",parseInt(mw*percent/100)+4);

		if(url_list_part.length){
			$.ajax({
				url: ajax_url,
				type: 'POST',
				dataType: 'json',
				data: data
			}).done(function (params) {

				count_added += aauicri_put_result_to_table(tablejob,params.result.urls,part_start);	// return attached count

				if(aauicriJobCancel){
					elem.text(elem.data('dftext'));
					aauicriJobCancel = false;
					$('#aauicri-urllist').removeAttr('disabled')
					$('input[name="aauicri-urlformat"]:radio').removeAttr('disabled')
					aauicri_enable_disable(elem,spinner,true);
				}
				else
					aauicri_add_to_library_part(ajax_url,url_list,elem,spinner,count_added,part_no+1,urlformat,tablejob);
			}).fail(function () {
				// ui info restore
				elem.text(elem.data('dftext'));
				aauicri_enable_disable(elem,spinner,true);
				$('#aauicri-urllist').removeAttr('disabled')
				$('input[name="aauicri-urlformat"]:radio').removeAttr('disabled')
			});
		}
		else{ // finished
			aauicriJobCancel = false;
			// ui info restore
			elem.text(elem.data('dftext'));
			aauicri_enable_disable(elem,spinner,true);
			$('#aauicri-urllist').removeAttr('disabled')
			$(".aauicri-importresult.aauicri-"+tabno).removeClass("hidden");
			$('input[name="aauicri-urlformat"]:radio').removeAttr('disabled')
			data.single = data.single.replace('%s','$');
			data.multi = data.multi.replace('%s','$');
			var my_sprintf = (str, ...argv) => !argv.length ? str : my_sprintf(str = str.replace(my_sprintf.token||"$", argv.shift()), ...argv);
			$(".aauicri-importresult.aauicri-"+tabno).text(my_sprintf(count_added < 2 ? data.single : data.multi,count_added) + " (" + count_added + "/" + url_list.length + ")" );
		}
	}

	function aauicri_put_result_to_table(table,result,part_start){
		attached = 0;
		for(var i = 0; i < result.length; i++){	//	[URL],[Width],[Height],[mime],[error](,[attachment_id])
			// calculate count added
			if(typeof(result[i][5])=="number")	// attachment_id
				attached++;
			row = table.find('tr:eq('+(part_start+i)+')');
			row.find('td:eq(2)').text( result[i][1] );	// Width
			row.find('td:eq(3)').text( result[i][2] );	// Height
			if( result[i][4] == null )	// error
				row.find('td:eq(0)').removeClass('notice-info').addClass('notice-success').text( '√' );
			else
				row.find('td:eq(0)').removeClass('notice-info').addClass('notice-error').text( result[i][4] );
		}
		return attached;
	}

	// import page cloudinary
	$('input[name="aauicri-fetch_cloudinary"]:radio').change(function(){	// getAll | limit
		var set_limit_cloudinary= parseInt($(this).val());
		if(set_limit_cloudinary)
			$('#aauicri-limit_cloudinary').removeClass("hidden");	// 1: limit	show textbox
		else
			$('#aauicri-limit_cloudinary').addClass("hidden");	// 0: all	hide textbox
		
	});
	$('input[name="aauicri-fetch_cloudinary"]:checked').trigger('change');

	$( '#aauicri-read_cloudinary_start' ).click( function ( e ) {
		$('.aauicri-importnotice').addClass("hidden");
		$(".aauicri-importresult").addClass("hidden");
		$('#aauicri-importtable-clone').remove();
		ajax_url=$(this).data("ajax_url");
		elem=$(this);
		spinner=$(this).parent().find('.spinner');
		aauicri_enable_disable(elem,spinner,false)
		$(".aauicri-importprogress-1").addClass("hidden");
		read_cloudinary_start(ajax_url,elem,spinner)
	});

	$( '.aauicri-canceljob' ).click( function ( e, elem ) {
		aauicriJobCancel = true;
		$(this).attr("disabled","disabled");
	});

	function read_cloudinary_start(ajax_url,elem,spinner){
		var limit_set = parseInt( $('input[name="aauicri-fetch_cloudinary"]:checked').val() )	// 0: all | 1: limit
			,limit = parseInt( $("#aauicri-limit_cloudinary").val() )
			,limit_max = parseInt( $("#aauicri-limit_cloudinary").attr("max") )
		;
		if( !isNaN(limit_max) && (isNaN(limit) || !limit || limit_max<limit) ){
			limit = limit_max;
			$("#aauicri-limit_cloudinary").val(limit);
		}
		$("#aauicri-urls").val("").removeAttr('title');
		var data = {
			req_type: "get_settings_cloudinary"
			,limit: limit_set ? limit : 0
			,https: $("[name=aauicri-protocol]:checked").val()
		};
		$.ajax({
			url: ajax_url,
			type: 'POST',
			dataType: 'json',
			data: data
		}).done(function (params) {
			if(params) {
				if(!params.cloudinary_settings){
					alert(params.msg)
					aauicri_enable_disable(elem,spinner,true)
				}
				else{
					elem.data('cloudinary_settings',params.cloudinary_settings);
					if(aauicriJobCancel){
						aauicriJobCancel = false;
						aauicri_enable_disable(elem,spinner,true);
					}
					else{
						$(".aauicri-addstatuspercent-1").text("?");
						$(".aauicri-listblock-1").text("");
						$(".aauicri-listcount-1").text("");
						$(".aauicri-importprogress-1").removeClass("hidden");
						$(".aauicri-importresult-1").addClass("hidden");
						read_cloudinary_library([],ajax_url,params.cloudinary_settings,'',data.limit,data.https,elem,spinner);
					}
				}
			}
		}).fail(function () {
			aauicri_enable_disable(elem,spinner,true)
		});
	}
	function read_cloudinary_library(urls,ajax_url,cloudinary,page,limit,https,elem,spinner){
		var urlformat=2;data = {
			req_type: "get_library_cloudinary"
			,cloudinary: cloudinary
			,page: page
			,limit: limit
			,https: https
		};
		$.ajax({
			url: ajax_url,
			type: 'POST',
			dataType: 'json',
			data: data
		}).done(function (params) {
			if(params) {
				if( typeof(params.results)=="object" && params.results && typeof(params.results.error) != "undefined" ){	// settings error
					alert('Cloudinary: ' + params.results.error.message);
				}
				else if(params.library){	// store data
					urls=urls.concat(params.library);
					new_block_data="";
					for(var i = 0; i < params.library.length; i++){	//	[URL],[Width],[Height],[mime]
						new_block_data+=params.library[i]+"\n";
					}
					$("#aauicri-urls").val( ( ( page ? $("#aauicri-urls").val() + "" : "" ) + new_block_data ) );
					$("#aauicri-urls").attr('title', $("#aauicri-urls").val().split("\n").length );
					$(".aauicri-listcount-1").text(urls.length);
				}
				if( params.page != "."){	// read next page
					if(aauicriJobCancel){
						aauicriJobCancel = false;
						aauicri_enable_disable(elem,spinner,true);
					}
					else{
						read_cloudinary_library(urls,ajax_url,cloudinary,params.page,limit,https,elem,spinner);
					}
				}
				else{	// finished
					tablejob=aauicri_drawjobtable(urls,false,urlformat);	// urls,urlsinvalid,urlformat
					// start ajax for image import
					aauicri_add_to_library_part(ajax_url,urls,elem,spinner,0,0,urlformat,tablejob);	// add images via breaking into pieces
				}
			}
		}).fail(function (params) {
			alert("Error!");
			aauicri_enable_disable(elem,spinner,true);
		});
	}


	// import page common

}
// import page end
// settings page
else if($("#aauicri_page-settings").length){
	$( '#aauicri-settings_save' ).click( function ( e ) {
		ajax_url=$(this).data("ajax_url");
		elem=$(this);
		spinner=$(this).parent().find('.spinner');
		aauicri_enable_disable(elem,spinner,false)
		settings_save_f(ajax_url,elem,spinner)
	});

	function settings_save_f(ajax_url,elem,spinner){
		var data = {
			req_type: "save_settings"
			,cloud_name: $("#aauicri-cloud_name").val()
			,api_key: $("#aauicri-api_key").val()
			,api_secret: $("#aauicri-api_secret").val()
		};
		$.ajax({
			url: ajax_url,
			type: 'POST',
			dataType: 'json',
			data: data
		}).done(function (params) {
			if(params) {
				if(params.msg)
					alert(params.msg)
			}
			aauicri_enable_disable(elem,spinner,true)
		}).fail(function () {
			aauicri_enable_disable(elem,spinner,true)
		});
	}

	}
// settings page end

	function aauicri_enable_disable(elem,spinner,status){
		var cancel = elem.parent().find('.aauicri-canceljob');
		
		if(status){ // enable
			elem.removeAttr("disabled").addClass("button-primary");
			$(spinner).css( 'visibility', 'hidden' );
			cancel.addClass("hidden");
			$('.aauicri-infobar').removeClass("anim");
		}
		else{
			elem.attr("disabled","disabled").removeClass("button-primary");
			$(spinner).css( 'visibility', 'visible' );
			cancel.removeClass("hidden").removeAttr("disabled");
			$('.aauicri-infobar').addClass("anim");
		}
	}

	var $tabs = $( '.tabarea-aauicri .nav-tab-wrapper' );
	var $panes = $( '.tabarea-aauicri .tab-content' );

	$tabs.find( 'a' ).on( 'click', function ( event ) {
			var toggle = $( this ).data( 'toggle' );
			$( this ).blur();
			show_tab( toggle );

			// if ( history.pushState ) {
				// history.pushState( null, null, '#' + toggle );
			// }

			return false;
	});

	var show_tab = function ( name ) {
		$tabs.find( '.nav-tab-active' ).removeClass( 'nav-tab-active' );
		$panes.find( '.tab-pane.active' ).removeClass( 'active' );

		$( '#' + name + '-tab' ).addClass( 'nav-tab-active' );
		$( '#' + name + '-pane' ).addClass( 'active' );
	};

});
