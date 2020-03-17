import {AbstractModule} from "../AbstractModule";
import {CashTippr} from "../CashTippr";
import {SlpSDK} from "../SlpSDK";
import {ReaderTokens} from "../structs/ReaderTokens";

export class AdminControls extends AbstractModule {
    protected static readonly QUERY_TOKEN_INTERVAL_MS = 1500;
    protected static readonly BCH_BALANCE_LOW = 0.0005;

    protected slpSDK: SlpSDK = null;
    protected distributingTokens: boolean = false;
    protected queryPayoutsTimerID: number = 0;
    protected walletImportOpen = false;

    constructor(cashtippr: CashTippr) {
        super(cashtippr);
    }

    public init() {
        if (this.cashtippr.$("body").attr("class").indexOf("cashtippr") === -1)
            return; // not our plugin settings page

        this.cashtippr.getTooltips().initToolTips();
        this.cashtippr.$(this.cashtippr.window.document).ready(($) => {
            this.enableAdblockSettings();
            this.enableSlpPressForPosts(this.cashtippr.window.document.getElementById("cashtippr_settings[enable_specific_pages]"), true);
            this.enableSlpPressForPosts(this.cashtippr.window.document.getElementById("cashtippr_settings[enable_specific_posts]"), false);
        });
        this.cashtippr.$("#cashtippr_settings\\[detect_adblock\\]").on("change", (event) => {
            this.enableAdblockSettings();
        });

        // SLP Press
        if (this.cashtippr.$("#cashtippr_settings\\[create_new_wallet\\]").length !== 0)
            this.slpSDK = new SlpSDK(this.cashtippr);
        this.toggleWalletCreateControls();
        this.toggleSendingTokenState();
        if (this.cashtippr.getAdminConfig().adminSlpWallet) {
            this.displayWalletBalance(this.cashtippr.getAdminConfig().adminSlpWallet);
            this.displayBchWalletBalance();
        }
        this.cashtippr.$("#cashtippr_settings\\[enable_specific_pages\\]").on("change", (event) => {
            this.enableSlpPressForPosts(event.target, true);
        });
        this.cashtippr.$("#cashtippr_settings\\[enable_specific_posts\\]").on("change", (event) => {
            this.enableSlpPressForPosts(event.target, false);
        });

        this.cashtippr.$("#cashtippr_settings\\[create_new_wallet\\]").on("click", (event) => {
            if (this.isTokenConfigured() === false) {
                this.cashtippr.window.alert(this.cashtippr.getAdminConfig().distributeTokens.tr.configureToken);
                return;
            }
            this.createWallet();
        });
        this.cashtippr.$("#cashtippr_settings\\[import_wallet\\]").on("click", (event) => {
            if (this.isTokenConfigured() === false) {
                this.cashtippr.window.alert(this.cashtippr.getAdminConfig().distributeTokens.tr.configureToken);
                return;
            }
            this.cashtippr.$("#ct-wallet-import-controls").fadeToggle("slow");
            this.walletImportOpen = !this.walletImportOpen;
            if (this.walletImportOpen === true)
                this.cashtippr.$(".ct-import-wallet-input").prop("required", true);
            else
                this.cashtippr.$(".ct-import-wallet-input").removeProp("required");
        });
        //this.cashtippr.$("#ct-input-slp-admin-wallet-form").on("submit", (event) => { // already inside a form
        this.cashtippr.$("#ct-btn-import-wallet").on("click", (event) => {
            // event.preventDefault();
            this.importWallet(this.cashtippr.$("#ct-wallet-address-import").val(), this.cashtippr.$("#ct-wallet-passphrase-import").val());
        });
        this.cashtippr.$("#cashtippr_settings\\[delete_wallet\\]").on("click", async (event) => {
            if (this.cashtippr.window.confirm(this.cashtippr.getAdminConfig().distributeTokens.tr.confirmDeleteWallet) !== true)
                return;
            await this.deleteWallet();
            this.toggleWalletCreateControls();
        });
        this.cashtippr.$("#cashtippr_settings\\[start_distribution\\]").on("click", (event) => {
            this.distributingTokens = true;
            this.toggleSendingTokenState();
            this.scheduleQueryTokenDistribution();
            this.cashtippr.$(this.cashtippr.window).bind("beforeunload", (e) => {
                return this.cashtippr.getAdminConfig().distributeTokens.tr.laavePage; // message not shown by modern browsers
            });
        });
        this.cashtippr.$("#cashtippr_settings\\[stop_distribution\\]").on("click", (event) => {
            this.distributingTokens = false;
            this.toggleSendingTokenState();
            clearTimeout(this.queryPayoutsTimerID);
            this.cashtippr.$(this.cashtippr.window).unbind("beforeunload");
        });
    }

    // ################################################################
    // ###################### PRIVATE FUNCTIONS #######################

    protected enableAdblockSettings() {
        const enabled = this.cashtippr.$("#cashtippr_settings\\[detect_adblock\\]").is(":checked") === true;
        const adblockSettingsSelector = "#cashtippr_settings\\[adblockDisable\\], #cashtippr_settings\\[adblock_page\\], #cashtippr_settings\\[adblockNoConflict\\]";
        if (enabled === false)
            this.cashtippr.$(adblockSettingsSelector).prop("disabled", true);
        else
            this.cashtippr.$(adblockSettingsSelector).removeProp("disabled");
    }

    // SLP Press
    protected enableSlpPressForPosts(checkboxElement: Element, pages: boolean) {
        const enabled = this.cashtippr.$(checkboxElement).is(":checked") === true;
        const settingsSelector = pages === true ? "#cashtippr_settings\\[slp_press_pages\\]" : "#cashtippr_settings\\[slp_press_posts\\]";
        if (enabled === false)
            this.cashtippr.$(settingsSelector).prop("disabled", true);
        else
            this.cashtippr.$(settingsSelector).removeProp("disabled");
    }

    protected async createWallet() {
        let wallet = await this.slpSDK.createWallet();
        await this.storeWallet(wallet.address, wallet.addressSlp, wallet.mnemonic);
        this.cashtippr.$("#ct-wallet-create-result").fadeIn("slow");
        this.cashtippr.$("#ct-slp-address").val(wallet.addressSlp);
        this.cashtippr.$("#ct-wallet-address-bch").val(wallet.address);
        this.cashtippr.$("#ct-wallet-passphrase").val(wallet.mnemonic);
        this.toggleWalletCreateControls();
        this.displayWalletBalance(wallet.address);
        this.displayBchWalletBalance();
    }

    protected async importWallet(address: string, passphrase: string) {
        if (await this.slpSDK.loginToWallet(address, passphrase, true) === false) {
            this.cashtippr.window.alert(this.cashtippr.getAdminConfig().distributeTokens.tr.invalidPassphrase);
            return;
        }
        const addresses = await this.slpSDK.getWalletAddresses(passphrase, 1);
        await this.storeWallet(addresses.address, address, passphrase);
        this.cashtippr.getAdminConfig().distributeTokens.admin.addressBch = addresses.address;
        this.cashtippr.$("#ct-import-message").text(this.cashtippr.getAdminConfig().distributeTokens.tr.importOk);
        this.toggleWalletCreateControls();
        this.displayWalletBalance(address);
        this.displayBchWalletBalance();
    }

    protected async displayWalletBalance(address: string) {
        let balance = await this.slpSDK.getTokenBalance(address, this.cashtippr.getAdminConfig().distributeTokens.id);
        this.cashtippr.$("#ct-balance-amount").text(balance); // TODO token decimals?
        if (balance < this.cashtippr.getAdminConfig().distributeTokens.amountLow)
            this.cashtippr.$("#ct-slp-balance").addClass("ct-slp-low-balance");
    }

    protected async displayBchWalletBalance() {
        //let balance = await this.slpSDK.getBalanceByKey(this.cashtippr.getAdminConfig().distributeTokens.admin.mnemonic); // always display 1st BCH address balance
        let balance = await this.slpSDK.getBalance(this.cashtippr.getAdminConfig().distributeTokens.admin.addressBch);
        this.cashtippr.$("#ct-bch-amount").text(balance.toFixed(8));
        if (balance < AdminControls.BCH_BALANCE_LOW)
            this.cashtippr.$("#ct-bch-balance").addClass("ct-slp-low-balance");
    }

    protected storeWallet(bchAddress: string, slpAddress: string, mnemonic: string) {
        return new Promise<void>((resolve, reject) => {
            let params = {
                address: slpAddress,
                addressBch: bchAddress,
                passphrase: mnemonic,
                n: this.cashtippr.getAdminConfig().distributeTokens.admin.token
            }
            this.webHelpers.postApi("/wp-json/cashtippr-slp-press/v1/add-create-wallet", params, (data) => {
                if (data.error === true) {
                    this.cashtippr.window.console.error("CashTippr: Error adding admin SLP wallet:", data);
                    return reject({txt: "error adding admin wallet", res: data});
                }
                this.cashtippr.getAdminConfig().adminSlpWallet = slpAddress;
                resolve();
            });
        });
    }

    protected deleteWallet() {
        return new Promise<void>((resolve, reject) => {
            let params = {
                n: this.cashtippr.getAdminConfig().distributeTokens.admin.token
            }
            this.webHelpers.postApi("/wp-json/cashtippr-slp-press/v1/delete-wallet", params, (data) => {
                if (data.error === true) {
                    this.cashtippr.window.console.error("CashTippr: Error deleting admin SLP wallet:", data);
                    return reject({txt: "error deleting admin wallet", res: data});
                }
                this.cashtippr.getAdminConfig().adminSlpWallet = undefined;
                resolve();
            });
        });
    }

    protected toggleWalletCreateControls() {
        const conf = this.cashtippr.getAdminConfig();
        if (conf.adminSlpWallet == "" || conf.adminSlpWallet === undefined) {
            this.cashtippr.$("#ct-address-balances").fadeOut("slow");
            this.cashtippr.$("#cashtippr_settings\\[create_new_wallet\\], #cashtippr_settings\\[import_wallet\\]").parent().fadeIn("slow");
            this.cashtippr.$("#cashtippr_settings\\[delete_wallet\\]").parent().fadeOut("slow");
        }
        else {
            this.cashtippr.$("#ct-address-balances").fadeIn("slow");
            this.cashtippr.$("#cashtippr_settings\\[create_new_wallet\\], #cashtippr_settings\\[import_wallet\\]").parent().fadeOut("slow");
            this.cashtippr.$("#cashtippr_settings\\[delete_wallet\\]").parent().fadeIn("slow");
        }
    }

    protected toggleSendingTokenState() {
        if (this.distributingTokens === false) {
            this.cashtippr.$("#ct-sending-activity").fadeOut("slow");
            this.cashtippr.$("#cashtippr_settings\\[start_distribution\\]").prop("disabled", false);
            this.cashtippr.$("#cashtippr_settings\\[stop_distribution\\]").prop("disabled", true);
        }
        else {
            this.cashtippr.$("#ct-sending-activity").fadeIn("slow");
            this.cashtippr.$("#cashtippr_settings\\[start_distribution\\]").prop("disabled", true);
            this.cashtippr.$("#cashtippr_settings\\[stop_distribution\\]").prop("disabled", false);
        }
    }

    protected isTokenConfigured(): boolean {
        let conf = this.cashtippr.getConfig().distributeTokens; // only available outside WP-Admin
        if (conf === undefined)
            conf = this.cashtippr.getAdminConfig().distributeTokens;
        return conf.id != "" && conf.ticker != "";
    }

    protected async sendTokens(tokens: ReaderTokens[]): Promise<void> {
        if (tokens.length === 0)
            return;
        const conf = this.cashtippr.getAdminConfig().distributeTokens;
        const ticker = conf.ticker;
        let errors = false;
        for (let i = 0; i < tokens.length; i++)
        {
            this.addLogLine(`Sending ${tokens[i].amount} ${ticker} to ${tokens[i].address} ...` , false);
            let sent = await this.slpSDK.sendToken(conf.admin.mnemonic, tokens[i].address, conf.id, tokens[i].amount);
            if (sent === true) {
                this.addLogLine(" Done", true);
                await this.notifyTokenSent(tokens[i]);
            }
            else {
                this.addLogLine(" Error", true);
                errors = true;
            }
        }
        if (errors === true) {
            this.cashtippr.$("#ct-send-token-error").fadeIn("slow");
            throw new Error("Error broadcasting TX to send tokens");
        }
        else {
            this.cashtippr.$("#ct-send-token-error").fadeOut("slow");
            this.displayWalletBalance(this.cashtippr.getAdminConfig().adminSlpWallet);
            this.displayBchWalletBalance();
        }
    }

    protected notifyTokenSent(token: ReaderTokens) {
        return new Promise<void>((resolve, reject) => {
            let params = {
                id: token.id,
                auth: this.cashtippr.getAdminConfig().slpDistributeNonce
            }
            this.webHelpers.getApi("/wp-json/cashtippr-slp-press/v1/notify-sent", params, async (data) => {
                if (data.error === true) {
                    return reject({txt: "Error notifying sent tokens", res: data});
                }
                //data.data[0].ok === true
                resolve();
            });
        })
    }

    protected queryTokenDistribution() {
        return new Promise<void>((resolve, reject) => {
            clearTimeout(this.queryPayoutsTimerID);
            let params = {
                auth: this.cashtippr.getAdminConfig().slpDistributeNonce
            }
            this.webHelpers.getApi("/wp-json/cashtippr-slp-press/v1/query-distribution", params, async (data) => {
                if (data.error === true) {
                    this.scheduleQueryTokenDistribution();
                    this.cashtippr.window.console.error("CashTippr: Error deleting admin SLP wallet:", data);
                    return reject({txt: "error deleting admin wallet", res: data});
                }
                let tokens = [];
                for (let i = 0; i < data.data.length; i++)
                    tokens.push(ReaderTokens.fromJson(data.data[i]));
                try {
                    await this.sendTokens(tokens);
                    this.scheduleQueryTokenDistribution();
                }
                catch (err) {
                    this.scheduleQueryTokenDistribution(20*1000);
                    this.cashtippr.window.console.error("Error sending tokens", err);
                }
                resolve();
            }).fail((err) => {
                this.cashtippr.window.console.error("CashTippr: Error querying SLP tokens to distribute:", err);
                this.scheduleQueryTokenDistribution();
            });
        })
    }

    protected scheduleQueryTokenDistribution(delayMs: number = 0) {
        clearTimeout(this.queryPayoutsTimerID);
        if (this.distributingTokens === false)
            return; // user pressed "stop" during request
        this.queryPayoutsTimerID = setTimeout(() => {
            this.queryTokenDistribution();
        }, AdminControls.QUERY_TOKEN_INTERVAL_MS + delayMs)
    }

    protected addLogLine(line: string, lineBreak: boolean = false) {
        if (lineBreak === true)
            line += "\r\n";
        this.cashtippr.$("#ct-distribution-log").append(line);
        this.cashtippr.$("#ct-distribution-log").scrollTop(this.cashtippr.$("#ct-distribution-log")[0].scrollHeight);
    }
}
