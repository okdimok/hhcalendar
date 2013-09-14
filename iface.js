$(function(){
	
	function monthSwitch(reload){
		var date =  reload === true ? $('.month-name').data('date') : $(this).data('date');
		$('#container').load('', {'act':'month-load', 'date':date}, bindContainerClicks);
	}
	
	$('#refresh-button-top').on('click.refresh', function(){
		monthSwitch(true);
		});

	function eventEditDialog($td){
		$info = $td.find('.info');
		position = $td.offset();
		$('#event-create').remove();
		var copy = eventEditDialogCopy.clone(true)
		.css({
			'position':'absolute',
			'top':position.top -20,
			'left': position.left + $td.width() + 14
			});
		var form = copy.find('form');
		form.append($('<input type="hidden" name="date"/>')
			.val($td.data('date')));
		form.find('[name=title]').next().after($('<div class="date"/>')
			.html($td.data('russian-date')));
		if ($td.hasClass('event')) {
			['title', 'participants', 'description'].forEach(function(entry){
				var val = $td.find('.'+entry).html();
				if (val) form.find('[name='+entry+']').val(val).addClass("using").addClass("mimic");
				});
		}
		if (form.find('[name=participants]').hasClass('mimic')) form.find('#participants-title').css('display', 'block');
		copy.appendTo('body').show();
		//form.find('input[type=text]:not(.mimic), textarea:not(.mimic)').eq(0).focus();
		

	}

	function bindContainerClicks(){
		$('#to-prev-month, #to-next-month, #to-today').on('click.month-switch', monthSwitch);
		$('td').on('click.create-event',function(){
			$('td').removeClass('selected');
			var $this = $(this);
			$this.addClass('selected');
			eventEditDialog($this);			
		});
	}
	
	bindContainerClicks();

	function autoclear(){
		var $this = $(this);
		$this
		.next().show();
		if ($this.hasClass('mimic')){
			$this.removeClass('mimic');
			return false;
		}
		$this
		.val('')
		.addClass('using');
		return false;
	}

	function bindDialogClicks(){
		$('.dialog .close').on('click.close', function(){
			$(this).parent().hide();
			return false;
		});

		$('input[type=text], textarea')
		.each(function(){
			var $this= $(this);
			$this.data('value', $this.val());
			})
		.after(
			$('<div class="clear-input">⨯</div>')
			.on('click.clear', function(){
				$(this).prev().val('').focus();
			})

		);


		$('input.autoclear, textarea.autoclear')
		.on('focus.autoclear', autoclear)
		.on('blur.autorestore', function(){
				var $this = $(this);
				if (!$this.val()){
					$this.val($this.data('value')).removeClass('using')
					.next().hide();
					$this.on('focus.autoclear', autoclear);
				}
				else {
					$this.off('.autoclear');
				}
				return false;
			});

		$('form').on('submit.clean', function(){
			var $this = $(this);
			$this.find('input[type=text]:not(.using), textarea:not(.using)').val('');
		});

		$('#event-create form [name=action]').on('click.prepare', function(){
			var $this = $(this);
			$this.before($('<input type="hidden" name="action">').val($this.val()))
			.before($('<input type="hidden" name="load-date">').val($('.month-name').data('date')));
			});

		$('#event-create form').on('submit.ajax', function(e){
			$('#container').load('', $(this).serialize(), bindContainerClicks);
			$('#event-create').remove();
			e.preventDefault();
		});
	}
	
	bindDialogClicks();

	var eventEditDialogCopy = $('#event-create').clone(true);
	var fastCreateDialogCopy = $('#fast-create').clone(true);

});
