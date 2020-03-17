
export class SlpAddress {

    /**
     * Check if a BCH address is valid.
     * @param bchAddress The address in CashAddress format starting with 'bitcoincash:'
     */
    public static isValidBchAddress(bchAddress: string): boolean {
        if (!bchAddress)
            return;
        // TODO improve this by checking the actual address format
        bchAddress = bchAddress.trim().toLowerCase();
        if (/^bitcoincash:/.test(bchAddress) === false)
            return false;
        if (bchAddress.length !== 54)
            return false;
        let addressParts = bchAddress.split(":");
        if (addressParts.length !== 2)
            return false;
        return /^[a-z0-9]+$/.test(addressParts[1]) === true;
    }

    /**
     * Check if a SLP address is valid.
     * @param slpAddress The address starting with 'simpleledger:'
     */
    public static isValidSlpAddress(slpAddress: string): boolean {
        if (!slpAddress)
            return;
        // TODO improve this by checking the actual address format
        slpAddress = slpAddress.trim().toLowerCase();
        if (/^simpleledger:/.test(slpAddress) === false)
            return false;
        if (slpAddress.length !== 55)
            return false;
        let addressParts = slpAddress.split(":");
        if (addressParts.length !== 2)
            return false;
        return /^[a-z0-9]+$/.test(addressParts[1]) === true;
    }
}
