
export type OrderStatus = "pending" | "paid";

/**
 * Class representing properties to check if a WooCommerce order has been paid.
 */
export class Order {
    public readonly id: number; // the unique ID for this order
    public readonly nonce: string; // a nonce to authenticate REST API requests
    public readonly status: OrderStatus;
    public readonly bchAmount: number; // amount requested (total order value)
    public readonly bchAmountReceived: number;
    public readonly qrcode: string; // an image link to the QR code with the (remaining) amount to be paid
    public readonly uri: string; // the payment URI

    constructor() {
    }

    public calculateRemaningAmount(): number {
        return Math.max(0.0, this.bchAmount - this.bchAmountReceived);
    }

    // ################################################################
    // ###################### PRIVATE FUNCTIONS #######################

}
