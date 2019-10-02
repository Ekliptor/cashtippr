
export interface WebHelpersConfig {
    cookieLifeDays: number;
    cookiePath: string;
    siteUrl: string;

    // DOM ids of elements of cookie consent message
    consentCookieName: string;
    confirmCookiesMsg: string;
    confirmCookiesBtn: string;
}

export class WebHelpers {
    public readonly window: Window;
    public readonly $: JQueryStatic;

    protected config: WebHelpersConfig;
    protected cssSelectors: string[] = [];

    constructor(window: Window, $: JQueryStatic, config: WebHelpersConfig) {
        this.window = window;
        this.$ = $;
        this.config = config;
    }

    public getBrowserLang() {
        return this.window.navigator.language.substr(0, 2).toLowerCase();
    }

    public getCookie(c_name: string) {
        let i, x, y;
        let ARRcookies = this.window.document.cookie.split(";");
        for (i = 0; i < ARRcookies.length; i++)
        {
            x = ARRcookies[i].substr(0, ARRcookies[i].indexOf("="));
            y = ARRcookies[i].substr(ARRcookies[i].indexOf("=") + 1);
            x = x.replace(/^\s+|\s+$/g,"");
            if (x == c_name)
                return decodeURI(y);
        }
        return null;
    }

    public setCookie(name: string, value: any, expireDays: number) {
        let date = new Date();
        date = new Date(date.getTime()+1000*60*60*24* (expireDays ? expireDays : this.config.cookieLifeDays));
        //document.cookie = name + "=" + value + "; expires=" + date.toGMTString() + "; path=" + pageData.cookiePath + "; domain=." + location.host;
        this.window.document.cookie = name + "=" + value + "; expires=" + date.toUTCString() + "; path=" + this.config.cookiePath;
    }

    public removeCookie(name: string) {
        //document.cookie = name + "=; expires=Thu, 02 Jan 1970 00:00:00 UTC; path=" + pageData.cookiePath + "; domain=." + location.host;
        this.window.document.cookie = name + "=; expires=Thu, 02 Jan 1970 00:00:00 UTC; path=" + this.config.cookiePath;
    }

    public checkCookieConsent() {
        this.$(this.config.confirmCookiesBtn).click(() => {
            this.confirmCookies();
        });
        if (this.getCookie(this.config.consentCookieName) !== null)
            this.$(this.config.confirmCookiesMsg).remove(); // we recently showed the cookie confirm message. some pages might still be in browser cache
    }

    public fromBase64(data: string) {
        if (typeof this.window.atob !== "function") {
            this.window.console.error("Base64 decoding is not supported in your browser");
            return "";
        }
        return this.window.atob(data);
    }

    public toBase64(data: string) {
        if (typeof this.window.btoa !== "function") {
            this.window.console.error("Base64 encoding is not supported in your browser");
            return "";
        }
        return this.window.btoa(data);
    }

    /**
     * Populate a html template
     * @param text {String}: The html template (or just normal text with variables)
     * @param variables {Object}: the key-value pairs with variables names and their content to be set in text
     * @param safeHtml {boolean, default false}: don't escape html characters if set to true
     * @returns {String} the translated html
     */
    public translate(text: string, variables: any, safeHtml: boolean = false) {
        if (typeof text !== "string") {
            try {
                // @ts-ignore
                text = text.toString();
            }
            catch (e) {
                this.log("Text to translate is not a string");
                return text;
            }
        }
        let start = 0, end = 0;
        while ((start = text.indexOf("{", start)) !== -1)
        {
            if (start > 0 && text.charAt(start-1) === "\\") { // escaped javascript code beginning
                start++;
                continue;
            }
            end = text.indexOf("}", start);
            if (end === -1) {
                this.log("Can not find end position while translating HTML");
                break;
            }
            let placeHolder = text.substring(start+1, end);
            let translation = null;
            if (placeHolder.substring(0, 3) === "tr:") {
                let key = placeHolder.substring(3);
                //translation = this.tr(key.toUpperCase());
                translation = this.tr(key);
            }
            else if (typeof variables === "object") {
                let textPiece = variables[placeHolder];
                if (typeof textPiece !== "undefined") {
                    if (typeof safeHtml === "boolean" && safeHtml)
                        translation = textPiece;
                    else
                        translation = this.escapeOutput(textPiece);
                }
            }
            if (translation !== null) {
                let reg = new RegExp("\\{" + placeHolder + "\\}", "g");
                text = text.replace(reg, translation);
            }
            else if (placeHolder.match("^[A-Za-z0-9_]+$") !== null) {
                this.log("No translation found for place holder: " + placeHolder);
                let reg = new RegExp("\\{" + placeHolder + "\\}", "g");
                text = text.replace(reg, "MISSING: " + this.escapeOutput(placeHolder));
            }
            else
                start += placeHolder.length;
        }
        text = text.replace(/\\\\\\{/, "{");
        return text;
    }

    public escapeOutput(text: string, convertNewlines: boolean = true) {
        if (typeof text !== "string")
            return text;
        text = text.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
        if (typeof convertNewlines === "undefined" || convertNewlines === true)
            text = text.replace(/\r?\n/g, "<br>");
        return text;
    }

    public escapeRegex(str: string) {
        return str.replace(/[\-\[\]\/\{\}\(\)\*\+\?\.\\\^\$\|]/g, "\\$&");
    }

    public tr(key: string) {
        //if (typeof language[key] === "undefined")
        //return "MISSING: " + key;
        //return language[key];
        //return i18next.t(key) // will just print the key if it doesn't exist and debug is disabled
        return key; // we don't have client-side translation support on this WP plugin yet. just return the key // TODO do we need this if we make our plugin use more ajax?
    }

    public log(args) {
        if (//typeof pageData.debugLog !== "boolean" || pageData.debugLog === false || // TODO add in CashTipprConfig
            typeof this.window.console !== "object" || typeof this.window.console.log !== "function")
            return;
        this.window.console.log(arguments);
    }

    public isAppleIOS() {
        // https://stackoverflow.com/questions/9038625/detect-if-device-is-ios
        return /iPad|iPhone|iPod/.test(this.window.navigator.userAgent) && !(this.window as any).MSStream;
    }

    public getAllCssSelectors(cached = true): string[] {
        if (cached === true && this.cssSelectors.length !== 0)
            return this.cssSelectors;

        this.cssSelectors = [];
        try {
            const originRegex = new RegExp("^" + this.escapeRegex(this.window.document.location.origin), "i");
            for (let i = 0; i < this.window.document.styleSheets.length; i++)
            {
                const sheet: any = this.window.document.styleSheets[i];
                if (sheet.href && originRegex.test(sheet.href) === false)
                    continue; // can't access it
                if (sheet.rules) {
                    for (let u = 0; u < sheet.rules.length; u++)
                    {
                        if (sheet.rules[u].selectorText)
                            this.cssSelectors.push(sheet.rules[u].selectorText);
                    }
                }
                if (sheet.imports) {
                    for (let x = 0; x < sheet.imports.length; x++)
                    {
                        for (let u = 0; u < sheet.imports[x].rules.length; u++)
                        {
                            if (sheet.imports[x].rules[u].selectorText)
                                this.cssSelectors.push(sheet.imports[x].rules[u].selectorText);
                        }
                    }
                }
            }
        }
        catch (err) {
            this.window.console.error("Error getting CSS selectors", err);
        }
        return this.cssSelectors;
    }

    public isExistingCssSelector(selector: string): boolean {
        const selectors = this.getAllCssSelectors();
        for (let i = 0; i < selectors.length; i++)
        {
            if (selectors[i] === selector) // css props case insensitive, class names in HTML case sensitive
                return true;
        }
        return false;
    }

    // ################################################################
    // ###################### PRIVATE FUNCTIONS #######################

    protected confirmCookies() {
        /* // no data stored in session
        this.getApi('/json/cookies', function(data) {
            if (data.error)
                //Hlp.showMsg(i18next.t('errorSending'), 'danger');
                console.error("Error sending data");
            else {
            }
        })
        */
        this.$(this.config.confirmCookiesMsg).remove();
        this.setCookie(this.config.consentCookieName, "1", this.config.cookieLifeDays);
    }

    public getApi(path: string, data?: any, callback?: (data: any, textStatus: string, jqXHR: JQueryXHR) => any, dataType?: string) {
        if (typeof data === "function") {
            callback = data;
            data = null;
        }
        else if (data === undefined)
            data = null;
        let url = path;
        if (url.toLowerCase().indexOf("http") !== 0)
            url = this.config.siteUrl + url;
        return this.$.get(url, data, (data, textStatus, jqXHR) => {
            callback(data, textStatus, jqXHR);
        }, dataType);
    }
}
