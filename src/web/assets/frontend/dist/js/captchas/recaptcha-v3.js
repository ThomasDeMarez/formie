!function(){"use strict";var e=()=>{let e=!1;const t=[];return{resolved(){return e},resolve:i=>{if(!e){e=!0;for(let e=0,r=t.length;e<r;e++)t[e](i)}},promise:{then:i=>{e?i():t.push(i)}}}};const t=Object.prototype.hasOwnProperty;function i(){let i=arguments.length>0&&void 0!==arguments[0]&&arguments[0];const r=e();return window.recaptchaRenderers||(window.recaptchaRenderers=[]),window.recaptchaRenderers.push(r),{notify(){for(let e=0,t=window.recaptchaRenderers.length;e<t;e++)window.recaptchaRenderers[e].resolve()},wait(){return r.promise},render(e,t,r){this.checkCaptchaLoad(),this.wait().then((()=>{r(i?window.grecaptcha.enterprise.render(e,t):window.grecaptcha.render(e,t))}))},reset(e){void 0!==e&&(this.assertLoaded(),i?this.wait().then((()=>window.grecaptcha.enterprise.reset(e))):this.wait().then((()=>window.grecaptcha.reset(e))))},execute(e){void 0!==e&&(this.assertLoaded(),i?this.wait().then((()=>window.grecaptcha.enterprise.execute(e))):this.wait().then((()=>window.grecaptcha.execute(e))))},executeV3(e){if(void 0!==e)return this.assertLoaded(),window.grecaptcha.execute(e)},checkCaptchaLoad(){t.call(window,"grecaptcha")&&t.call(window.grecaptcha,"render")&&this.notify()},assertLoaded(){if(!r.resolved())throw new Error("ReCAPTCHA has not been loaded")}}}const r=i();i(!0);"undefined"!=typeof window&&(window.formieRecaptchaOnLoadCallback=r.notify);class o{constructor(){let e=arguments.length>0&&void 0!==arguments[0]?arguments[0]:{};this.initialized=!1,this.$form=e.$form,this.form=this.$form.form,this.isVisible=!1;const t=new IntersectionObserver((e=>{0==e[0].intersectionRatio?(this.isVisible=!1,this.initialized&&this.onHide()):(this.isVisible=!0,this.initialized&&this.onShow())}),{root:this.$form});setTimeout((()=>{"function"==typeof this.getPlaceholders&&this.getPlaceholders().forEach((e=>{t.observe(e)}))}),500)}onShow(){}onHide(){}createInput(){const e=document.createElement("div");return this.$placeholder.innerHTML="",this.$placeholder.appendChild(e),e}}window.FormieCaptchaProvider=o;const a=function(e){let t=arguments.length>1&&void 0!==arguments[1]?arguments[1]:null;return t||(t=Math.random().toString(36).substr(2,5)),`${e}.${t}`};window.FormieRecaptchaV3=class extends o{constructor(){let e=arguments.length>0&&void 0!==arguments[0]?arguments[0]:{};super(e),this.$form=e.$form,this.form=this.$form.form,this.siteKey=e.siteKey,this.badge=e.badge,this.language=e.language,this.loadingMethod=e.loadingMethod,this.recaptchaScriptId="FORMIE_RECAPTCHA_SCRIPT",this.initialized=!0}getPlaceholders(){return this.$placeholders=this.$form.querySelectorAll("[data-recaptcha-placeholder]")}onShow(){this.initCaptcha()}onHide(){this.onAfterSubmit(),this.form.removeEventListener(a("onFormieCaptchaValidate","RecaptchaV3")),this.form.removeEventListener(a("onAfterFormieSubmit","RecaptchaV3"))}initCaptcha(){if(document.getElementById(this.recaptchaScriptId))(function(e){let t=arguments.length>1&&void 0!==arguments[1]?arguments[1]:1e5;const i=Date.now(),r=function(o,a){window[e]?o(window[e]):t&&Date.now()-i>=t?a(new Error("timeout")):setTimeout(r.bind(this,o,a),30)};return new Promise(r)})("grecaptcha").then((()=>{this.renderCaptcha()}));else{const e=document.createElement("script");e.id=this.recaptchaScriptId,e.src=`https://www.recaptcha.net/recaptcha/api.js?onload=formieRecaptchaOnLoadCallback&render=explicit&hl=${this.language}`,"async"!==this.loadingMethod&&"asyncDefer"!==this.loadingMethod||(e.async=!0),"defer"!==this.loadingMethod&&"asyncDefer"!==this.loadingMethod||(e.defer=!0),e.onload=()=>{this.renderCaptcha()},document.body.appendChild(e)}this.$placeholders.length?(this.form.addEventListener(this.$form,a("onFormieCaptchaValidate","RecaptchaV3"),this.onValidate.bind(this)),this.form.addEventListener(this.$form,a("onAfterFormieSubmit","RecaptchaV3"),this.onAfterSubmit.bind(this))):console.error("Unable to find any ReCAPTCHA placeholders for [data-recaptcha-placeholder]")}renderCaptcha(){this.$placeholder=null;let e=null;this.$form.form.formTheme&&(e=this.$form.form.formTheme.$currentPage);const{hasMultiplePages:t}=this.$form.form.settings;if(this.$placeholders.forEach((t=>{e&&e.contains(t)&&(this.$placeholder=t)})),t||null!==this.$placeholder||(this.$placeholder=this.$placeholders[0]),null===this.$placeholder)return void(null===e&&console.log("Unable to find ReCAPTCHA placeholder for [data-recaptcha-placeholder]"));const i=this.$form.querySelector('[name="g-recaptcha-response"]');i&&i.remove(),r.render(this.createInput(),{sitekey:this.siteKey,badge:this.badge,size:"invisible",callback:this.onVerify.bind(this),"expired-callback":this.onExpired.bind(this),"error-callback":this.onError.bind(this)},(e=>{this.recaptchaId=e}))}onValidate(e){this.$form.form.formTheme||(e.preventDefault(),this.form.submitAction=this.$form.querySelector('[name="submitAction"]').value||"submit"),"submit"===this.form.submitAction&&null!==this.$placeholder&&(e.detail.invalid||(e.preventDefault(),this.submitHandler=e.detail.submitHandler,r.execute(this.recaptchaId)))}onVerify(e){this.submitHandler&&this.submitHandler.validatePayment()&&this.submitHandler.validateCustom()&&this.submitHandler.submitForm()}onAfterSubmit(e){setTimeout((()=>{this.renderCaptcha()}),300)}onExpired(){console.log("ReCAPTCHA has expired - reloading."),r.reset(this.recaptchaId)}onError(e){console.error("ReCAPTCHA was unable to load")}}}();