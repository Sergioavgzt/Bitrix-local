if(!window.BX.FormSubmit) {
	BX.FormSubmit = function() {
		var target = BX.proxy_context,			
			form = BX.findParent(target, {"tag" : "form"}),	
			alert = BX.findChild(form, {"className": "alert"}, true, false),		
			captchaWord = BX.findChild(form, {"attribute": {"name": "CAPTCHA_WORD"}}, true, false),
			captchaImg = BX.findChild(form, {"tagName": "img"}, true, false),
			captchaSid = BX.findChild(form, {"attribute": {"name": "CAPTCHA_SID"}}, true, false),
			formInput,
			formTextarea,
			data = [];

		formInput = BX.findChildren(form, {"tag" : "input"}, true);
		if(!!formInput && 0 < formInput.length) {
			for(i = 0; i < formInput.length; i++) {
				data[formInput[i].getAttribute("name")] = formInput[i].value;
			}
		}

		formTextarea = BX.findChildren(form, {"tag": "textarea"}, true);
		if(!!formTextarea && 0 < formTextarea.length) {
			for(i = 0; i < formTextarea.length; i++) {
				data[formTextarea[i].getAttribute("name")] = formTextarea[i].value;
			}
		}
		
		
		BX.ajax({
			url: form.getAttribute("action"),
			data: data,
			method: "POST",
			dataType: "json",		
			onsuccess: function(data) {
				if(!!data.success) {
document.getElementById("contact_pb_form").reset();
					if(!!alert)
						BX.adjust(alert, {html: "<span class='alertMsg good'><i class='fa fa-check'></i><span class='text'>" + data.success.text + "</span></span>"});
					BX.adjust(target, {props: {disabled: true}});
				} else if(!!data.error) {
					if(!!alert)
						BX.adjust(alert, {html: "<span class='alertMsg bad'><i class='fa fa-exclamation-triangle'></i><span class='text'>" + data.error.text + "</span></span>"});
					if(!!data.error.captcha_code && data.error.captcha_code != "") {
						if(!!captchaWord)
							captchaWord.value = "";
						if(!!captchaImg)
							BX.adjust(captchaImg, {props: {"src": "/bitrix/tools/captcha.php?captcha_sid=" + data.error.captcha_code}});
						if(!!captchaSid)
							captchaSid.value = data.error.captcha_code;
					}
				}		
				
			}
		});
	}
}

var countImg = 0;
setCookie("count", countImg, 10000);

function delBlock(el) {
    countImg = getCookie("count")
    countImg--;
    setCookie("count", countImg, 10000);
    console.log(countImg);
    $(el).parent().remove()
    event.stopPropagation();
}

function getCookie(name) {
    return BX.getCookie(name);
}

function setCookie(name, value, time) {
    BX.setCookie(name, value, {expires: time});
}
