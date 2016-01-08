$( "#searchSelectValue" ).select2();
$('#searchValue').val($( "#searchSelectValue" ).val());
$( "#searchSelectValue" ).change(function(){
	$('#searchValue').val($( "#searchSelectValue" ).val());
	$('.searchTagLoad').show();
	if($( "#searchSelectValue" ).val() == null) {
		$('.headTitleBold h2').text('""');
	}else{
		$('.headTitleBold h2').text('"' + $( "#searchSelectValue" ).val() + '"');
	}
	var url = $('#searchURLAjax').val();
	queryData = $('#searchValue').val();
	if(queryData == ''){
		queryData = 'nonetaghere';
	}
	$.ajax({
		type : "get",
        dataType : "json",
        url : url,
        data: {
        	query: queryData,
        },
        success: function(response){
        	console.log(response.result);
        	$('.formcontentTags').empty();
        	if(response.result.length === 0){
        		var wholeSearchContent = '';
        	}else{
        		var wholeSearchContent = '<div class="searchTagLoad"></div><div class="searchSeparator"></div><div class="searchList">';
        	}
        	var searchList = '';
        	var image = '';
        	var description = '';
        	for(var i = 0; i< response.result.length; i++){
        		if(response.page_thumb[i] != null){
        			var image = '<div class="SearchImage">' +
	                    '<a href="' + response.product_links[i] + '">'+
	                    	'<img src="'+ response.page_thumb[i] +'" alt="' + response.result[i].pageName + '">' +
						'</a>' +
					'</div>';
        		}else{
        			image = '';
        		}
        		if(response.result[i].description.length > 100 ){
					description = '<a href="' + response.product_links[i] + '">'+ response.result[i].description.substring(0, 100) +'...</a>';
				}else{
					description = '<a href="' + response.product_links[i] + '">'+ response.result[i].description.substring(0, 100) +'</a>';
				}
	        	
	        	searchList += '<div class="searchSingle">' +
	        		image +
					'<div class="singleSearchContent">' +
		            	 description +
	                '</div>' +
	            '</div>' +
	            '<div class="searchSeparator"></div>';
           }
           wholeSearchContent += searchList + '</div>';
           $('.formcontentTags').append(wholeSearchContent);
           if(response.pagination != null){
           		$('.ccm-pagination-wrapper').remove();
           		$('.searchList').append(response.pagination);
           		var searchURL = window.location.href;
           		var ajaxsearchURL = searchURL.substring(0,searchURL.indexOf("?"));
           		$('.ccm-pagination-wrapper li a').each(function(){
           			wrongSearchURL = $(this).attr('href');
           			querySearchURL = wrongSearchURL.substring(wrongSearchURL.indexOf("?"));
           			$(this).attr('href', ajaxsearchURL + querySearchURL);
           		});
           }else{
           		$('.ccm-pagination-wrapper').remove();
           }
           $('.formcontentTags').append();
           $('.searchTagLoad').hide();
           $('.headTitleLight h2').text(response.total_number + " Suchresultate");
        },
        error: function(error){
        	console.log(error);
        	$('.searchTagLoad').hide();
        }, 
        fail: function(error){
        	console.log(error);
        	$('.searchTagLoad').hide();
        }, 
	});
});
