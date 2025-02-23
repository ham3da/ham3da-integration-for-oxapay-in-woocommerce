(() => {
    "use strict";
    const e = window.wp.element,
        t = window.wc.wcBlocksRegistry,
        a = window.wp.i18n,
        n = window.wc.wcSettings,
        o = window.wp.htmlEntities;
    var l;
    const i = (0, n.getPaymentMethodData)("HAM3INFO_OxaPay_Gateway", {}),
        c = () => (0, o.decodeEntities)(i.description || ""),
        r = {
            name: "HAM3INFO_OxaPay_Gateway",
            label: (0, e.createElement)("img", {
                src: `${i.icon}`,
                alt: (0, o.decodeEntities)(i.title || (0, a.__)("OxaPay", "ham3da-integration-for-oxapay-in-woocommerce"))
            }),
            placeOrderButtonLabel: i.OrderButtonLabel,
            content: (0, e.createElement)(c, null),
            edit: (0, e.createElement)(c, null),
            canMakePayment: () => !0,
            ariaLabel: (0, o.decodeEntities)((null == i ? void 0 : i.title) || (0, a.__)("Payment via OxaPay", "ham3da-integration-for-oxapay-in-woocommerce")),
            supports: {
                features: null !== (l = i.supports) && void 0 !== l ? l : []
            }
        };
    (0, t.registerPaymentMethod)(r);
})();