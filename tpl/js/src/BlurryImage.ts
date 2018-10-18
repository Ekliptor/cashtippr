import {CashTippr} from "./CashTippr";

/**
 * The class for displaying blurry images.
 * We bundle it together with all our JavaScript instead of with the addon because
 * a 2nd JS WebPack project would create unnecessary dependency + bootstrap overhead.
 */
export class BlurryImage {
    protected cashtippr: CashTippr;

    constructor(cashtippr: CashTippr) {
        this.cashtippr = cashtippr;
        // TODO implement events this class (and other addons) can listen to so that we don't have to call functions in here directly
    }

    // ################################################################
    // ###################### PRIVATE FUNCTIONS #######################

}