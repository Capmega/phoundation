(function($) {
	$.fn.autosuggest = function(options) {
		var self     = this;
		var loading  = false;

		if (typeof options == "string") {
			selector = options;
			options  = {}
		}

		var defaults = {
			count       : 10,
			minLength   : 2,
			autoSubmit  : true
		};

		// Overwrite default options
		// with user provided ones
		// and merge them into "options".
		var options = $.extend({}, defaults, options);
		var lists   = this.find("div.autosuggest input").siblings("ul");

		//$(lists).on("click", "li", function(e){
		//	self.val($(this).prop('title'));
		//	lists.hide();
		//
		//	if(options.autoSubmit) {
		//		self.closest('form').submit();
		//	}
		// });

// :DELETE:
		////add onhovers
		//$(lists).on("mouseenter", "li", function(e){
		//	$(this).addClass(options.returnClass + '_hover');
		//});
		//
		//$(lists).on("mouseleave", "li", function(e){
		//	$(this).removeClass(options.returnClass + '_hover');
		//});
		$(this).on("mousedown", "div.autosuggest li", function(e){
			$this = $(this);
			$this.closest("div").find("input").val($this.text());
		});

		$(this).on("focus", "div.autosuggest input", function(e){
			$ul = $(this).siblings("ul");

			if($(this).siblings("ul").children().length){
				$ul.addClass("active");
			}
		});

		$(this).on("blur", "div.autosuggest input", function(e){
			$(this).siblings("ul").removeClass("active");
		});

		$(this).on("keydown", "div.autosuggest input", function(e){
console.log("keydown");
			var $this  = $(this);

			switch (e.keyCode) {
				case 38:
					e.stopPropagation();
					e.preventDefault();

					$ul       = $this.siblings("ul")
					$list     = $ul.find("li");
					$selected = $ul.find("li.hover");

					$list.removeClass("hover");

					if (!$selected.length || $selected.is(":first-child")){
						$list.last().addClass("hover");

					}else{
						$selected.prev().addClass("hover");
					}

					return false;

				case 40:
					e.stopPropagation();
					e.preventDefault();

					$ul       = $this.siblings("ul")
					$list     = $ul.find("li");
					$selected = $ul.find("li.hover");

					$list.removeClass("hover");

					if (!$selected.length || $selected.is(":last-child")){
						$list.first().addClass("hover");

					}else{
						$selected.next().addClass("hover");
					}

					return false;

				case 13:
					e.stopPropagation();
					e.preventDefault();

 					$li = $this.closest("div").find("li.hover");

					if ($li.length) {
						$this.val($li.text());
						$li.parent().removeClass("active");
					}

					return false;
			}
		});

		$(this).on("keyup", "div.autosuggest input", function(e){
			switch (e.keyCode) {
				case 38:
					// FALLTHROUGH
				case 40:
					// FALLTHROUGH
				case 13:
					break;

				default:
console.log("keyup");
					var $this  = $(this),
						url    = $this.data("source"),
						value  = $this.val(),
						target = $this.siblings("ul");

					if(value.length >= options.minLength) {
						if(loading == false) {
							loading = true;

							$.post(url, { value: value, count: options.count })
								.success(function(data){
									if(typeof data == "string"){
										// Auto parse json
										data = $.parseJSON(data);
									}

									loading = false;

									if(data.result == "OK" && data.html) {
										target
											.html(data.html)
											.addClass("active");

									} else {
										target.removeClass("active");
									}
								})
								.fail(function(){
									$.flashMessage("Autosuggest failed", "error", 0);
								});
						}

					} else {
						target.removeClass("active");
					}
			}
		});
	}
}(jQuery));
