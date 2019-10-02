import {CashTippr} from "./CashTippr";
import {WebHelpers} from "./WebHelpers";


export class AbstractModule {
    protected cashtippr: CashTippr;
    protected webHelpers: WebHelpers;

    constructor(cashtippr: CashTippr, webHelpers: WebHelpers = null) {
        this.cashtippr = cashtippr;
        this.webHelpers = webHelpers ? webHelpers : this.cashtippr.getWebHelpers();
    }

    // ################################################################
    // ###################### PRIVATE FUNCTIONS #######################
}
