/** Spray the items out by simple calculation of absolute top and left
 *
 */
function adjustEm() {
    var docWidth = $(document).width();
    var itemWidth = $('#rssx0').outerWidth() + 10;
    var cols = Math.floor(docWidth/itemWidth);
    var margin = Math.floor((docWidth - itemWidth*cols)/2);
    var tops = []; var sCol;

    var it = $('#chaninfo');
    var tip = it.offset().top + it.outerHeight();
    tip = Math.max(tip,90);

    for (i=0; i<cols; i++) {
        tops[i] = tip; // top of each column
    }

    for (i=0; i<items.length; i++) { 
        sCol = 0;
        for (var s=1; s<cols; s++) {
            if ( tops[s] < tops[sCol]) {
                sCol = s; // find shortest column
            }
        }
        it = $('#rssx'+i);
	it.css({top: tops[sCol], left: margin + itemWidth*sCol});
	tops[sCol] += it.outerHeight() + 10;
    }
}

/** Build item list from json array inside #columns div
 *
 */
function listItems() {
    $('#columns').html(''); // clear

    for (var i=0; i<items.length; i++) {
        var html = '';
        var linkLater = '';
        for (var part in items[i]) {
            if (part == 'link') { // save for end of item
                linkLater = items[i][part];
            }
            else {
                if (part == 'internal_img_url') { // build image tag
                    html = '<img src="' + items[i][part] + '">' + html;
                }
                else { // all other parts simply create a tag of their own
                    html += '<css' + part + '>' 
                        + items[i][part] 
                        + '</css' + part + '>';
                }
            }
        }
        if (linkLater != '') { // if link add it now
            html = '<a target="_blank" href="'+linkLater+'">'
                + html
                + '</a>';
        }
        $($.parseHTML('<rssitem id="rssx' + i + '">' + html + '</rssitem>'))
            .appendTo('#columns');
    }

    startAdjustTimer(60); // relayour until images arrive
}

$(document).ready(function() {
    
    listItems(); // initial load had items defined

    $("#pager").submit(function(event) {
        event.preventDefault();

        $.post( 'pager.php', 
                $(this).serializeArray(),
                function( data ) {
		    if (data.error != '') {
			$('#columns').html(''); // clear screen
			$('#urlerr').html(data.error);
		    }
		    else {
			var chtml = '';
			if (data.channel.imgUrl !== undefined)
			    chtml += '<img src="'+data.channel.imgUrl+'">';
			if (data.channel.title !== undefined)
			    chtml += data.channel.title;
			$('#chaninfo').html(chtml);
			$('#urlerr').html('');
			items = data.items;
			listItems(); // new items arrived
		    }
                }, 'json');

    });

    adjustEm(); // will adjust too soon on chrome, FF good

    // adjust if window resizes
    $(window).resize(function() {
    adjustEm(); // not noticable if already right	
	startAdjustTimer(1); // debounce sizing for a second
    });

    $('[name*="sum"]').change(function() {
	$('#pageurl').submit();
    });

});

// sad hack for deferred image load post ready
var adjustTimer = 0;
var adjustTicks = 0;

function adjustSecs() {
    adjustEm(); // not noticable if already right
    if (adjustTicks-- < 1)
        clearInterval(adjustTimer);
}

function startAdjustTimer(ticks) {
    adjustTimer = setInterval(function(){adjustSecs()}, 250);
    adjustTicks = Math.max(adjustTicks,ticks*4); // take longer not less
}


