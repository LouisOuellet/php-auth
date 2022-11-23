class phpAuthCookie {

	#object = null;
	#bsObject = null;
	#session = null;
	#fields = {
		"Your choice on cookies": "Your choice on cookies",
		"We use essential cookies to make sure our application works. We'd also like to set optional cookies to improve the quality and performance of our application and enable personalised features. Change preferences by clicking Cookie Settings. Allow all cookies by clicking Accept." : "We use essential cookies to make sure our application works. We'd also like to set optional cookies to improve the quality and performance of our application and enable personalised features. Change preferences by clicking Cookie Settings. Allow all cookies by clicking Accept.",
		"Learn More": "Learn More",
		"What are Cookies?": "What are Cookies?",
		"Cookies are small pieces of data sent from a website and stored on a visitor's browser. They are typically used to keep track of settings you've selected and actions taken on a site.": "Cookies are small pieces of data sent from a website and stored on a visitor's browser. They are typically used to keep track of settings you've selected and actions taken on a site.",
		"There are two types of cookies:": "There are two types of cookies:",
		"Session (transient) cookies: These cookies are erased when you close your browser, and do not collect information from your computer. They typically store information in the form of a session identification that does not personally identify the user.": "Session (transient) cookies: These cookies are erased when you close your browser, and do not collect information from your computer. They typically store information in the form of a session identification that does not personally identify the user.",
		"Persistent (permanent or stored) cookies: These cookies are stored on your hard drive until they expire (at a set expiration date) or until you delete them. These cookies are used to collect identifying information about the user, such as web surfing behavior or user preferences for a specific site.": "Persistent (permanent or stored) cookies: These cookies are stored on your hard drive until they expire (at a set expiration date) or until you delete them. These cookies are used to collect identifying information about the user, such as web surfing behavior or user preferences for a specific site.",
		"Essentials": "Essentials",
		"Required for the application to work": "Required for the application to work",
		"Performance": "Performance",
		"Cached information use to improve the overall performance": "Cached information use to improve the overall performance",
		"Quality": "Quality",
		"Anonymous information use to improve the quality of the user experience": "Anonymous information use to improve the quality of the user experience",
		"Personalisations": "Personalisations",
		"Information use to personalise the user experience": "Information use to personalise the user experience",
		"Cookie Settings": "Cookie Settings",
		"Accept": "Accept"
	};

	constructor(){
		const self = this;
		if(!self.read('cookiesAccept')){
			self.#offcanvas()
		}
	}
	#encode(object){
		if(object instanceof Object){ return JSON.stringify(object); }
		else { return object; }
	}
	#decode(json){
		if(typeof json === 'string'){
			try { JSON.parse(json); } catch (e) { return json; }
			return JSON.parse(json);
		} else { return json; }
	}
	create(name, value, days = 30){
		var expires;
		if(days){
			var date = new Date();
			date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
			expires = "; expires=" + date.toGMTString();
		} else {
			expires = "";
		}
		document.cookie = encodeURIComponent(name) + "=" + encodeURIComponent(this.#encode(value)) + expires + "; path=/";
	}
	read(name){
		var nameEQ = encodeURIComponent(name) + "=";
		var ca = document.cookie.split(';');
		for (var i = 0; i < ca.length; i++) {
			var c = ca[i];
			while (c.charAt(0) === ' ')
					c = c.substring(1, c.length);
			if (c.indexOf(nameEQ) === 0)
					return this.#decode(decodeURIComponent(c.substring(nameEQ.length, c.length)));
		}
		return null;
	}
	update(name, value, days = 30){
		this.create(name, value, days = 30);
	}
	delete(name){
		this.create(name, "", -1);
	}
	#offcanvas(){
		const self = this;
		if(self.#object == null){
			self.#object = $(document.createElement('div')).addClass('offcanvas offcanvas-bottom h-auto user-select-none').attr('data-bs-backdrop','static').attr('tabindex','-1').attr('id','OffcanvasCookie').attr('aria-labelledby','OffcanvasCookieLabel');
			self.#object.id = self.#object.attr('id');
			self.#object.header = $(document.createElement('div')).addClass('offcanvas-header').appendTo(self.#object);
			self.#object.title = $(document.createElement('h5')).addClass('offcanvas-title fs-2 fw-light').attr('id','OffcanvasCookieLabel').html('<i class="bi-person-lock me-2"></i>'+self.#fields['Your choice on cookies']).appendTo(self.#object.header);
			self.#object.body = $(document.createElement('div')).addClass('offcanvas-body d-flex justify-content-center flex-column p-0').appendTo(self.#object);
			self.#object.form = $(document.createElement('form')).addClass('overflow-auto p-3').attr('method','post').appendTo(self.#object.body);
			self.#object.description = $(document.createElement('p')).html(self.#fields["We use essential cookies to make sure our application works. We'd also like to set optional cookies to improve the quality and performance of our application and enable personalised features. Change preferences by clicking Cookie Settings. Allow all cookies by clicking Accept."]).appendTo(self.#object.form);
			self.#object.learn = $(document.createElement('p')).appendTo(self.#object.form);
			self.#object.learn.more = $(document.createElement('a')).addClass('text-decoration-none').attr('href', '').attr('data-bs-toggle', 'collapse').attr('data-bs-target', '#cookiesCollapseLearn').attr('aria-expanded', 'false').attr('aria-controls', 'cookiesCollapseLearn').html(self.#fields["Learn More"]).appendTo(self.#object.learn);
			self.#object.learn.collapse = $(document.createElement('div')).addClass('collapse').attr('id', 'cookiesCollapseLearn').appendTo(self.#object.form);
			self.#object.learn.collapse.body = $(document.createElement('div')).addClass('card card-body my-3 px-4').appendTo(self.#object.learn.collapse);
			self.#object.learn.collapse.header = $(document.createElement('p')).appendTo(self.#object.learn.collapse.body);
			self.#object.learn.collapse.header.title = $(document.createElement('h4')).html(self.#fields["What are Cookies?"]).appendTo(self.#object.learn.collapse.header);
			self.#object.learn.collapse.row1 = $(document.createElement('p')).html(self.#fields["Cookies are small pieces of data sent from a website and stored on a visitor's browser. They are typically used to keep track of settings you've selected and actions taken on a site."]).appendTo(self.#object.learn.collapse.body);
			self.#object.learn.collapse.row2 = $(document.createElement('p')).html(self.#fields["There are two types of cookies:"]).appendTo(self.#object.learn.collapse.body);
			self.#object.learn.collapse.list = $(document.createElement('ul')).appendTo(self.#object.learn.collapse.body);
			self.#object.learn.collapse.item1 = $(document.createElement('li')).html(self.#fields["Session (transient) cookies: These cookies are erased when you close your browser, and do not collect information from your computer. They typically store information in the form of a session identification that does not personally identify the user."]).appendTo(self.#object.learn.collapse.list);
			self.#object.learn.collapse.item2 = $(document.createElement('li')).html(self.#fields["Persistent (permanent or stored) cookies: These cookies are stored on your hard drive until they expire (at a set expiration date) or until you delete them. These cookies are used to collect identifying information about the user, such as web surfing behavior or user preferences for a specific site."]).appendTo(self.#object.learn.collapse.list);
			self.#object.collapse = $(document.createElement('div')).addClass('collapse').attr('id','cookiesCollapseSettings').appendTo(self.#object.form);
			self.#object.collapse.body = $(document.createElement('div')).addClass('card card-body my-3').appendTo(self.#object.collapse);
			self.#object.collapse.list = $(document.createElement('ul')).addClass('list-group').appendTo(self.#object.collapse.body);
			self.#object.collapse.list.item1 = $(document.createElement('li')).addClass('list-group-item').appendTo(self.#object.collapse.list);
			self.#object.collapse.list.item1.check = $(document.createElement('div')).addClass('form-check form-switch mt-2').appendTo(self.#object.collapse.list.item1);
			self.#object.collapse.list.item1.input = $(document.createElement('input')).addClass('form-check-input').attr('type','checkbox').attr('role','switch').attr('id','cookiesAcceptEssentials').attr('name','cookiesAcceptEssentials').attr('checked','checked').attr('disabled','disabled').appendTo(self.#object.collapse.list.item1.check);
			self.#object.collapse.list.item1.label = $(document.createElement('label')).addClass('form-check-label').attr('for','cookiesAcceptEssentials').html(self.#fields["Essentials"]).appendTo(self.#object.collapse.list.item1.check);
			self.#object.collapse.list.item1.description = $(document.createElement('small')).html(self.#fields["Required for the application to work"]).appendTo(self.#object.collapse.list.item1);
			self.#object.collapse.list.item2 = $(document.createElement('li')).addClass('list-group-item').appendTo(self.#object.collapse.list);
			self.#object.collapse.list.item2.check = $(document.createElement('div')).addClass('form-check form-switch mt-2').appendTo(self.#object.collapse.list.item2);
			self.#object.collapse.list.item2.input = $(document.createElement('input')).addClass('form-check-input').attr('type','checkbox').attr('role','switch').attr('id','cookiesAcceptPerformance').attr('name','cookiesAcceptPerformance').attr('checked','checked').appendTo(self.#object.collapse.list.item2.check);
			self.#object.collapse.list.item2.label = $(document.createElement('label')).addClass('form-check-label').attr('for','cookiesAcceptPerformance').html(self.#fields["Performance"]).appendTo(self.#object.collapse.list.item2.check);
			self.#object.collapse.list.item2.description = $(document.createElement('small')).html(self.#fields["Cached information use to improve the overall performance"]).appendTo(self.#object.collapse.list.item2);
			self.#object.collapse.list.item3 = $(document.createElement('li')).addClass('list-group-item').appendTo(self.#object.collapse.list);
			self.#object.collapse.list.item3.check = $(document.createElement('div')).addClass('form-check form-switch mt-2').appendTo(self.#object.collapse.list.item3);
			self.#object.collapse.list.item3.input = $(document.createElement('input')).addClass('form-check-input').attr('type','checkbox').attr('role','switch').attr('id','cookiesAcceptQuality').attr('name','cookiesAcceptQuality').attr('checked','checked').appendTo(self.#object.collapse.list.item3.check);
			self.#object.collapse.list.item3.label = $(document.createElement('label')).addClass('form-check-label').attr('for','cookiesAcceptQuality').html(self.#fields["Quality"]).appendTo(self.#object.collapse.list.item3.check);
			self.#object.collapse.list.item3.description = $(document.createElement('small')).html(self.#fields["Anonymous information use to improve the quality of the user experience"]).appendTo(self.#object.collapse.list.item3);
			self.#object.collapse.list.item4 = $(document.createElement('li')).addClass('list-group-item').appendTo(self.#object.collapse.list);
			self.#object.collapse.list.item4.check = $(document.createElement('div')).addClass('form-check form-switch mt-2').appendTo(self.#object.collapse.list.item4);
			self.#object.collapse.list.item4.input = $(document.createElement('input')).addClass('form-check-input').attr('type','checkbox').attr('role','switch').attr('id','cookiesAcceptPersonalisations').attr('name','cookiesAcceptPersonalisations').attr('checked','checked').appendTo(self.#object.collapse.list.item4.check);
			self.#object.collapse.list.item4.label = $(document.createElement('label')).addClass('form-check-label').attr('for','cookiesAcceptPersonalisations').html(self.#fields["Personalisations"]).appendTo(self.#object.collapse.list.item4.check);
			self.#object.collapse.list.item4.description = $(document.createElement('small')).html(self.#fields["Information use to personalise the user experience"]).appendTo(self.#object.collapse.list.item4);
			self.#object.controls = $(document.createElement('div')).addClass('d-flex justify-content-around mx-auto my-4').appendTo(self.#object.form);
			self.#object.controls.settings = $(document.createElement('button')).addClass('btn btn-lg shadow btn-light').attr('type','button').attr('name','cookiesSettings').attr('data-bs-toggle','collapse').attr('data-bs-target','#cookiesCollapseSettings').attr('aria-expanded','false').attr('aria-controls','cookiesCollapseSettings').html(self.#fields["Cookie Settings"]).appendTo(self.#object.controls);
			self.#object.controls.accept = $(document.createElement('button')).addClass('btn btn-lg shadow btn-primary').attr('type','submit').attr('name','cookiesAccept').html(self.#fields["Accept"]).appendTo(self.#object.controls);
			self.#object.appendTo('body');
			if(typeof bootstrap !== 'undefined'){
				self.#bsObject = new bootstrap.Offcanvas(self.#object)
				self.#bsObject.show()
			}
		}
	}
}
